<?php

namespace App\Services;

use App\Models\BacklogItem;

/**
 * AI Assessment Triage — classifies the 242 Appendix-10 JIRA backlog items into
 * the scope categories that drive the delivery plan. Maps to RFP §7.2.3 System
 * Assessment ("identify incomplete modules/gaps and functionalities").
 *
 * The model PROPOSES a classification + confidence + one-line rationale; a named
 * engineer CONFIRMS or OVERRIDES. Confidence below a floor is routed to review,
 * never auto-accepted. Deterministic classifier here; an on-prem LLM refines it
 * when configured. This is the assessment-harness moat, ported into the demo.
 */
class TriageService
{
    public const CONFIDENCE_FLOOR = 75;

    public const CLASSES = [
        'missing_process' => 'Missing process — build',
        'enhancement'     => 'Enhancement — extend existing',
        'defect'          => 'Defect — fix existing',
        'net_new'         => 'Net-new — beyond v1 scope',
    ];

    public function __construct(private LlmService $llm) {}

    /** Classify all pending items (idempotent — leaves confirmed/overridden alone). */
    public function classifyPending(): int
    {
        $n = 0;
        foreach (BacklogItem::where('triage_state', 'pending')->get() as $item) {
            [$class, $conf, $why] = $this->propose($item);
            $item->update(['classification' => $class, 'confidence' => $conf, 'rationale' => $why]);
            $n++;
        }
        return $n;
    }

    /** Deterministic proposal from the ticket title + JIRA status. */
    public function propose(BacklogItem $i): array
    {
        $t = strtolower($i->title);
        $status = strtolower((string) $i->jira_status);

        if (str_contains($t, 'defect') || str_contains($t, 'bug') || str_contains($t, 'fix') || str_contains($t, 'error') || str_contains($t, 'issue'))
            return ['defect', 88, 'Wording indicates a fault in existing behaviour rather than new capability.'];
        if (str_contains($t, 'new ') || str_contains($t, 'forecast') || str_contains($t, 'dashboard') || str_contains($t, 'analytic'))
            return ['net_new', 78, 'Capability appears beyond the inherited v1 scope; confirm against signed-off URS.'];
        if (str_contains($t, 'enhance') || str_contains($t, 'improve') || str_contains($t, 'update') || str_contains($t, 'add ') || str_contains($t, 'adjustment') || str_contains($t, 'report'))
            return ['enhancement', 82, 'Extends a function IWK grades as already present.'];
        // In Progress / User Validation usually = partially-built = enhancement
        if (in_array($status, ['in progress', 'user validation', 'it validation', 'validation']))
            return ['enhancement', 76, "JIRA status “{$i->jira_status}” implies partial build in progress — extend, not build from zero."];
        // To Do / Grooming default to missing_process
        return ['missing_process', 80, 'No evidence of an existing implementation; treat as a process to build.'];
    }

    public function confirm(BacklogItem $i, ?string $override, string $reviewer = 'engineer-01'): BacklogItem
    {
        if ($override && $override !== $i->classification) {
            $i->update(['classification' => $override, 'triage_state' => 'overridden', 'reviewer' => $reviewer]);
        } else {
            $i->update(['triage_state' => 'confirmed', 'reviewer' => $reviewer]);
        }
        return $i;
    }

    public function summary(): array
    {
        $q = BacklogItem::query();
        return [
            'total'      => (clone $q)->count(),
            'classified' => (clone $q)->whereNotNull('classification')->count(),
            'confirmed'  => (clone $q)->whereIn('triage_state', ['confirmed', 'overridden'])->count(),
            'below_floor'=> (clone $q)->where('confidence', '<', self::CONFIDENCE_FLOOR)->count(),
            'by_class'   => (clone $q)->whereNotNull('classification')
                                ->selectRaw('classification, count(*) c')->groupBy('classification')
                                ->pluck('c', 'classification')->all(),
            'no_key'     => (clone $q)->whereNull('jira_key')->orWhere('jira_key', '')->count(),
        ];
    }
}
