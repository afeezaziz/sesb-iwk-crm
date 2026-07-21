<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

/**
 * Natural-language reporting — "ask your billing data". Maps a plain-English
 * question to a real aggregate query over live billing data and returns an
 * actual result table. Addresses the Reporting module exposure (App 9: 34
 * in-scope reports with no URS, 56 platform TBC) by showing reports can be
 * produced on demand from the data model.
 *
 * Deterministic intent-matching here (works with no model); an on-prem LLM can
 * map free-form questions to the same safe, whitelisted query set — the model
 * never writes raw SQL, it only selects an intent, so there is no injection
 * surface.
 */
class ReportingService
{
    public function __construct(private LlmService $llm) {}

    /** @return array{title:string,columns:array,rows:array,note:string,intent:string} */
    public function answer(string $question): array
    {
        $intent = $this->intent($question);
        return match ($intent) {
            'outstanding_total' => $this->outstandingTotal(),
            'receipts_by_method' => $this->receiptsByMethod(),
            'bills_this_period' => $this->billsThisPeriod(),
            'top_debtors' => $this->topDebtors(),
            'arrears_aging' => $this->arrearsAging(),
            'accounts_by_category' => $this->accountsByCategory(),
            default => $this->help(),
        };
    }

    private function intent(string $q): string
    {
        $t = strtolower($q);
        // optional LLM intent selection (constrained to the whitelist)
        if ($this->llm->enabled()) {
            $keys = 'outstanding_total, receipts_by_method, bills_this_period, top_debtors, arrears_aging, accounts_by_category';
            $pick = $this->llm->complete(
                "You map a question to exactly one report key from this list: {$keys}. Reply with only the key, nothing else.",
                $this->llm->redact($q), 20);
            $pick = trim((string) $pick);
            if (array_key_exists($pick, array_flip(explode(', ', $keys)))) return $pick;
        }
        if (str_contains($t, 'outstanding') || str_contains($t, 'owe') || str_contains($t, 'unpaid total')) return 'outstanding_total';
        if (str_contains($t, 'receipt') || str_contains($t, 'payment') && str_contains($t, 'method') || str_contains($t, 'collected')) return 'receipts_by_method';
        if (str_contains($t, 'top') && (str_contains($t, 'debt') || str_contains($t, 'arrear') || str_contains($t, 'owe'))) return 'top_debtors';
        if (str_contains($t, 'aging') || str_contains($t, 'ageing') || str_contains($t, 'overdue')) return 'arrears_aging';
        if (str_contains($t, 'bill') && (str_contains($t, 'month') || str_contains($t, 'period') || str_contains($t, 'this'))) return 'bills_this_period';
        if (str_contains($t, 'account') && (str_contains($t, 'categor') || str_contains($t, 'type') || str_contains($t, 'domestic') || str_contains($t, 'commercial'))) return 'accounts_by_category';
        if (str_contains($t, 'receipt') || str_contains($t, 'payment')) return 'receipts_by_method';
        return 'help';
    }

    private function outstandingTotal(): array
    {
        $rows = Bill::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('period, ROUND(SUM(amount - paid),2) total, COUNT(*) bills')
            ->groupBy('period')->orderBy('period')->get();
        return $this->wrap('Total outstanding by billing period',
            ['Period', 'Open bills', 'Outstanding (RM)'],
            $rows->map(fn ($r) => [$r->period, number_format($r->bills), number_format($r->total, 2)])->all(),
            'Live SUM(amount − paid) over unpaid & partial bills.', 'outstanding_total');
    }

    private function receiptsByMethod(): array
    {
        $rows = Receipt::where('status', 'posted')
            ->selectRaw('method, COUNT(*) c, ROUND(SUM(amount),2) total')->groupBy('method')->orderByDesc('total')->get();
        return $this->wrap('Receipts by payment method',
            ['Method', 'Count', 'Amount (RM)'],
            $rows->map(fn ($r) => [ucfirst($r->method), number_format($r->c), number_format($r->total, 2)])->all(),
            'Live over posted receipts.', 'receipts_by_method');
    }

    private function billsThisPeriod(): array
    {
        $period = Bill::where('status', '!=', 'trial')->max('period');
        $rows = Bill::where('period', $period)->where('status', '!=', 'trial')
            ->selectRaw('status, COUNT(*) c, ROUND(SUM(amount),2) total')->groupBy('status')->get();
        return $this->wrap("Bills for the latest period ({$period})",
            ['Status', 'Count', 'Amount (RM)'],
            $rows->map(fn ($r) => [ucfirst($r->status), number_format($r->c), number_format($r->total, 2)])->all(),
            'Live over the most recent billed period.', 'bills_this_period');
    }

    private function topDebtors(): array
    {
        $sub = '(SELECT COALESCE(SUM(amount-paid),0) FROM bills WHERE bills.account_id=accounts.id AND status IN ("unpaid","partial"))';
        $rows = Account::with('customer')
            ->select('accounts.*')
            ->selectRaw("$sub as arrears")
            ->whereRaw("$sub > 0")->orderByDesc('arrears')->limit(10)->get();
        return $this->wrap('Top 10 accounts by arrears',
            ['Account', 'Customer', 'Arrears (RM)'],
            $rows->map(fn ($a) => [$a->no, $a->customer->name, number_format($a->arrears, 2)])->all(),
            'Live per-account arrears subquery.', 'top_debtors');
    }

    private function arrearsAging(): array
    {
        $svc = app(DebtService::class);
        $rows = collect($svc->aging())->map(fn ($x, $k) => [$k, number_format($x['accounts']), number_format($x['amount'], 2)])->values()->all();
        return $this->wrap('Arrears aging', ['Bucket', 'Accounts', 'Amount (RM)'], $rows,
            'Live aging buckets from unpaid bills by days overdue.', 'arrears_aging');
    }

    private function accountsByCategory(): array
    {
        $rows = Account::selectRaw('category, COUNT(*) c')->groupBy('category')->orderByDesc('c')->get();
        return $this->wrap('Accounts by category',
            ['Category', 'Accounts'],
            $rows->map(fn ($r) => [$r->category, number_format($r->c)])->all(),
            'Live count over accounts.', 'accounts_by_category');
    }

    private function help(): array
    {
        return $this->wrap('Ask about your billing data', ['Try asking'],
            [['“total outstanding by period”'], ['“receipts by method”'], ['“top debtors”'], ['“arrears aging”'], ['“bills this period”'], ['“accounts by category”']],
            'Deterministic intent-matching; an on-prem model widens the phrasing it understands.', 'help');
    }

    private function wrap($title, $columns, $rows, $note, $intent): array
    {
        return compact('title', 'columns', 'rows', 'note', 'intent');
    }
}
