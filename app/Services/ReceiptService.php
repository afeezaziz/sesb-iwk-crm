<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Payment capture and allocation — oldest-bill-first, partial-aware.
 * This is real logic: posting a receipt updates bill balances and statuses.
 */
class ReceiptService
{
    public function post(Account $account, float $amount, string $method, string $teller = 'counter-05'): Receipt
    {
        return DB::transaction(function () use ($account, $amount, $method, $teller) {
            $receipt = Receipt::create([
                'no'         => 'R-' . str_pad((string) (Receipt::count() + 100001), 6, '0', STR_PAD_LEFT),
                'account_id' => $account->id,
                'amount'     => $amount,
                'method'     => $method,
                'teller'     => $teller,
                'batch'      => 'BC-' . now()->format('ymd') . '-' . substr($teller, -2),
                'posted_at'  => now(),
            ]);

            $remaining = $amount;
            $bills = $account->bills()->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();

            foreach ($bills as $bill) {
                if ($remaining <= 0) break;
                $due = round($bill->amount - $bill->paid, 2);
                if ($due <= 0) continue;
                $alloc = min($due, $remaining);
                ReceiptAllocation::create([
                    'receipt_id' => $receipt->id,
                    'bill_id'    => $bill->id,
                    'amount'     => $alloc,
                ]);
                $bill->paid = round($bill->paid + $alloc, 2);
                $bill->status = $bill->paid >= $bill->amount ? 'paid' : 'partial';
                $bill->save();
                $remaining = round($remaining - $alloc, 2);
            }
            // any remainder stays as credit on account (demo: recorded implicitly on receipt)
            return $receipt->load('allocations.bill');
        });
    }

    public function void(Receipt $receipt): Receipt
    {
        return DB::transaction(function () use ($receipt) {
            abort_if($receipt->status === 'voided', 422, 'Receipt already voided.');
            foreach ($receipt->allocations as $a) {
                $bill = $a->bill;
                $bill->paid = round($bill->paid - $a->amount, 2);
                $bill->status = $bill->paid <= 0 ? 'unpaid' : ($bill->paid < $bill->amount ? 'partial' : 'paid');
                if ($bill->paid < 0) $bill->paid = 0;
                $bill->save();
            }
            $receipt->update(['status' => 'voided']);
            return $receipt;
        });
    }

    /** Daily receipts by payment method — the CS.TYPES capability (gap in v1). */
    public function dailyByType(?string $date = null): array
    {
        $q = Receipt::where('status', 'posted')
            ->when($date, fn ($q) => $q->whereDate('posted_at', $date))
            ->selectRaw('method, COUNT(*) c, SUM(amount) total')->groupBy('method')->get();
        return $q->map(fn ($r) => ['method' => $r->method, 'count' => (int) $r->c, 'total' => (float) $r->total])->all();
    }
}
