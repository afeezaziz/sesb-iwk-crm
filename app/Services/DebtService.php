<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Arrangement;
use App\Models\Instalment;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

/**
 * Arrears aging, worklist staging and instalment arrangements.
 */
class DebtService
{
    /** Aging buckets computed live from unpaid bills. */
    public function aging(): array
    {
        $rows = Bill::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw("CASE
                WHEN julianday('now') - julianday(due_date) <= 30  THEN 'b0_30'
                WHEN julianday('now') - julianday(due_date) <= 90  THEN 'b31_90'
                WHEN julianday('now') - julianday(due_date) <= 180 THEN 'b91_180'
                ELSE 'b180p' END bucket, COUNT(DISTINCT account_id) accts, SUM(amount - paid) total")
            ->groupBy('bucket')->get()->keyBy('bucket');
        $g = fn ($k, $f) => (float) ($rows[$k]->$f ?? 0);
        return [
            '0–30 d'   => ['accounts' => (int) $g('b0_30', 'accts'),  'amount' => $g('b0_30', 'total')],
            '31–90 d'  => ['accounts' => (int) $g('b31_90', 'accts'), 'amount' => $g('b31_90', 'total')],
            '91–180 d' => ['accounts' => (int) $g('b91_180', 'accts'),'amount' => $g('b91_180', 'total')],
            '> 180 d'  => ['accounts' => (int) $g('b180p', 'accts'),  'amount' => $g('b180p', 'total')],
        ];
    }

    /** Worklist: accounts with arrears, staged by age of oldest unpaid bill. */
    public function worklist(int $limit = 12)
    {
        return Account::whereHas('bills', fn ($q) => $q->whereIn('status', ['unpaid', 'partial']))
            ->with('customer')->withCount('arrangements')
            ->get()->map(function ($a) {
                $days = $a->oldestUnpaidDays();
                $a->arrears = $a->outstanding();
                $a->age_days = $days;
                $a->stage = $days > 365 ? 'Legal review' : ($days > 180 ? 'DCA assigned' : ($days > 90 ? 'Reminder 2' : 'Reminder 1'));
                return $a;
            })
            ->sortByDesc('arrears')->take($limit)->values();
    }

    /** Create an instalment arrangement over the account's current arrears. */
    public function arrange(Account $account, int $months, ?string $startDate = null): Arrangement
    {
        abort_if($months < 2 || $months > 24, 422, 'Instalment plan must be 2–24 months.');
        $total = $account->outstanding();
        abort_if($total <= 0, 422, 'Account has no arrears to arrange.');

        return DB::transaction(function () use ($account, $months, $total, $startDate) {
            $arr = Arrangement::create([
                'no'         => 'AR-' . str_pad((string) (Arrangement::count() + 5001), 5, '0', STR_PAD_LEFT),
                'account_id' => $account->id,
                'total'      => $total,
                'months'     => $months,
                'start_date' => $startDate ?: now()->addMonth()->startOfMonth()->toDateString(),
            ]);
            $per = floor($total / $months * 100) / 100;
            $acc = 0.0;
            for ($i = 1; $i <= $months; $i++) {
                $amt = $i === $months ? round($total - $acc, 2) : $per; // last instalment absorbs rounding
                $acc = round($acc + $amt, 2);
                Instalment::create([
                    'arrangement_id' => $arr->id,
                    'seq'      => $i,
                    'due_date' => $arr->start_date->copy()->addMonths($i - 1)->toDateString(),
                    'amount'   => $amt,
                ]);
            }
            return $arr->load('instalments');
        });
    }
}
