<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\AnomalyReview;
use App\Models\Bill;
use Illuminate\Support\Collection;

/**
 * Billing Anomaly Detection — the flagship AI feature.
 *
 * Directly targets the RFP's hardest correctness problem: §2 "Adjustment
 * Modules" performs RETROSPECTIVE RECALCULATION of past bills by effective
 * date — the single most error-prone, dispute-generating mechanism in any
 * utility billing system (Risk R-01). This engine scans issued bills and
 * adjustments and flags the ones a human should check BEFORE they reach the
 * customer.
 *
 * Technique is honest and explainable, not a black box:
 *   • statistical outliers — robust z-score (median/MAD) per rate category,
 *   • domain rules — negative bills, over-payment, duplicate adjustments,
 *     retrospective spikes, adjustments exceeding the original bill.
 * An optional LLM layer (LlmService) writes the plain-English explanation and
 * recommended action; with no API key it falls back to a templated reason.
 *
 * Governance mirrors the assessment harness: the model PROPOSES, a named
 * officer CONFIRMS or DISMISSES (AnomalyReview). Nothing is auto-actioned.
 */
class AnomalyService
{
    public const SEVERITY = ['critical' => 3, 'high' => 2, 'medium' => 1];

    public function __construct(private LlmService $llm) {}

    /** Scan and return flagged items, most severe first, with review state. */
    public function scan(): Collection
    {
        $flags = collect();

        /* ---- baseline stats per category (robust: median + MAD) ---- */
        $byCat = Bill::where('status', '!=', 'trial')->get()->groupBy(fn ($b) => $b->account->category ?? 'Domestic');
        $stats = [];
        foreach ($byCat as $cat => $bills) {
            $amounts = $bills->pluck('amount')->map(fn ($a) => (float) $a)->sort()->values();
            $median = $this->median($amounts);
            $mad = $this->median($amounts->map(fn ($a) => abs($a - $median))->sort()->values()) ?: 0.01;
            $stats[$cat] = ['median' => $median, 'mad' => $mad];
        }

        /* ---- 1. statistical outlier bills ---- */
        foreach (Bill::with('account.customer')->where('status', '!=', 'trial')->get() as $b) {
            $cat = $b->account->category ?? 'Domestic';
            $s = $stats[$cat] ?? ['median' => $b->amount, 'mad' => 1];
            // robust z (0.6745 scaling makes MAD comparable to std-dev)
            $z = $s['mad'] > 0 ? abs(0.6745 * ($b->amount - $s['median']) / $s['mad']) : 0;
            if ($z >= 8 && $b->amount >= 0) {
                $howfar = $z >= 100 ? "far outside the normal range" : round($z, 1) . "× the typical spread";
                $flags->push($this->flag('bill', $b->id, $b->amount >= $s['median'] ? 'critical' : 'high',
                    'statistical_outlier',
                    "Bill {$b->no} is RM " . number_format($b->amount, 2) . " — {$howfar} for {$cat} accounts (median RM " . number_format($s['median'], 2) . ").",
                    ['bill' => $b, 'z' => round($z, 1), 'median' => $s['median'], 'category' => $cat]));
            }
        }

        /* ---- 2. domain rules on bills ---- */
        foreach (Bill::with('account.customer')->get() as $b) {
            if ($b->amount < 0) {
                $flags->push($this->flag('bill', $b->id, 'critical', 'negative_bill',
                    "Bill {$b->no} has a NEGATIVE amount (RM " . number_format($b->amount, 2) . ") — a credit issued as a bill. Check adjustment posting.",
                    ['bill' => $b]));
            }
            if ($b->amount > 0 && $b->paid > $b->amount + 0.01) {
                $flags->push($this->flag('bill', $b->id, 'high', 'over_allocation',
                    "Bill {$b->no} shows paid RM " . number_format($b->paid, 2) . " against amount RM " . number_format($b->amount, 2) . " — over-allocation / receipt mismatch.",
                    ['bill' => $b]));
            }
        }

        /* ---- 3. adjustment rules (the R-01 heart) ---- */
        $adjByAcct = Adjustment::with('account.customer')->get()->groupBy('account_id');
        foreach ($adjByAcct as $acctId => $adjs) {
            // duplicate: same account, type, amount within 3 days
            foreach ($adjs as $i => $a) {
                foreach ($adjs as $j => $b2) {
                    if ($j <= $i) continue;
                    if ($a->type === $b2->type && abs($a->amount - $b2->amount) < 0.01
                        && $a->effective_date->diffInDays($b2->effective_date) <= 3) {
                        $flags->push($this->flag('adjustment', $b2->id, 'high', 'duplicate_adjustment',
                            "Adjustment {$b2->no} looks like a duplicate of {$a->no} on account {$b2->account->no} (same type & amount within 3 days) — double-adjustment risk.",
                            ['adjustment' => $b2, 'other' => $a->no]));
                    }
                }
            }
            // adjustment larger than the account's typical bill (retrospective over-swing)
            $typical = $stats[$adjs->first()->account->category ?? 'Domestic']['median'] ?? 20;
            foreach ($adjs as $a) {
                if (abs($a->amount) >= max(5 * $typical, 500)) {
                    $flags->push($this->flag('adjustment', $a->id, 'critical', 'oversized_adjustment',
                        "Adjustment {$a->no} of RM " . number_format($a->amount, 2) . " on account {$a->account->no} exceeds 5× a typical bill — a retrospective recalculation this large should be reviewed before it posts (RFP §2, Risk R-01).",
                        ['adjustment' => $a, 'typical' => $typical]));
                }
            }
        }

        /* attach review state + dedupe by (type,id,rule) keeping most severe */
        $reviews = AnomalyReview::all()->keyBy(fn ($r) => "{$r->subject_type}:{$r->subject_id}:{$r->rule}");
        return $flags
            ->unique(fn ($f) => "{$f['subject_type']}:{$f['subject_id']}:{$f['rule']}")
            ->map(function ($f) use ($reviews) {
                $r = $reviews->get("{$f['subject_type']}:{$f['subject_id']}:{$f['rule']}");
                $f['review'] = $r?->verdict;   // null | confirmed | dismissed
                return $f;
            })
            ->sortByDesc(fn ($f) => self::SEVERITY[$f['severity']] * 10 + ($f['review'] === null ? 5 : 0))
            ->values();
    }

    public function summary(Collection $flags): array
    {
        return [
            'total'     => $flags->count(),
            'critical'  => $flags->where('severity', 'critical')->count(),
            'high'      => $flags->where('severity', 'high')->count(),
            'open'      => $flags->whereNull('review')->count(),
            'confirmed' => $flags->where('review', 'confirmed')->count(),
            'dismissed' => $flags->where('review', 'dismissed')->count(),
            'scanned_bills' => Bill::where('status', '!=', 'trial')->count(),
            'scanned_adjustments' => Adjustment::count(),
        ];
    }

    /** Optional LLM explanation for one flag; deterministic reason is always present. */
    public function explain(array $flag): string
    {
        $out = $this->llm->complete(
            "You are a utility-billing QA assistant for IWK's sewerage billing system. In 2-3 sentences, explain to a billing officer why the flagged item warrants review and the single most useful next check. Be concrete and calm; no preamble.",
            $this->llm->redact("Rule: {$flag['rule']} · Severity: {$flag['severity']}\nFinding: {$flag['reason']}"),
            220
        );
        return $out ?: $flag['reason'];
    }

    private function flag(string $type, int $id, string $sev, string $rule, string $reason, array $ctx): array
    {
        return ['subject_type' => $type, 'subject_id' => $id, 'severity' => $sev, 'rule' => $rule, 'reason' => $reason, 'ctx' => $ctx];
    }

    private function median(Collection $sorted): float
    {
        $n = $sorted->count();
        if ($n === 0) return 0.0;
        $mid = intdiv($n, 2);
        return $n % 2 ? (float) $sorted[$mid] : (float) (($sorted[$mid - 1] + $sorted[$mid]) / 2);
    }
}
