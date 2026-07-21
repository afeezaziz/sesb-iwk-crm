<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillingRun;
use App\Models\Tariff;
use Illuminate\Support\Facades\DB;

/**
 * Billing run — trial and live. Rates every active account from the tariff
 * table for a period. Trial produces a register without posting; live posts
 * real bills (skipping accounts already billed for the period).
 */
class BillingService
{
    public function run(string $period, string $mode = 'trial'): BillingRun
    {
        return DB::transaction(function () use ($period, $mode) {
            $tariffs = Tariff::pluck('monthly_charge', 'category');
            $accounts = Account::where('status', 'active')->get();
            $count = 0; $total = 0.0;

            foreach ($accounts as $a) {
                $amount = (float) ($tariffs[$a->category] ?? 8.00);
                $already = Bill::where('account_id', $a->id)->where('period', $period)
                    ->where('status', '!=', 'trial')->exists();
                if ($already) continue;
                $count++; $total += $amount;

                if ($mode === 'live') {
                    Bill::where('account_id', $a->id)->where('period', $period)->where('status', 'trial')->delete();
                    Bill::create([
                        'no'         => 'B-' . substr(str_replace('-', '', $period), 2) . '-' . str_pad((string) $a->id, 4, '0', STR_PAD_LEFT),
                        'account_id' => $a->id,
                        'period'     => $period,
                        'bill_date'  => now()->toDateString(),
                        'due_date'   => now()->addDays(30)->toDateString(),
                        'amount'     => $amount,
                        'status'     => 'unpaid',
                        'source'     => 'billing_run',
                    ]);
                }
            }

            return BillingRun::create([
                'period' => $period, 'mode' => $mode,
                'accounts_billed' => $count, 'total_amount' => round($total, 2),
                'ran_at' => now(),
            ]);
        });
    }

    /** Trial register preview — first N lines + totals, without posting. */
    public function trialRegister(string $period, int $limit = 12): array
    {
        $tariffs = Tariff::pluck('monthly_charge', 'category');
        $accounts = Account::where('status', 'active')->with('customer')->limit($limit)->get();
        return $accounts->map(fn ($a) => [
            'account' => $a->no, 'customer' => $a->customer->name,
            'category' => $a->category, 'amount' => (float) ($tariffs[$a->category] ?? 8.00),
        ])->all();
    }
}
