<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Adjustment;
use App\Models\Arrangement;
use App\Models\Bill;
use App\Models\BillingRun;
use App\Models\Enquiry;
use App\Models\ForecastRun;
use App\Models\Process;
use App\Models\Receipt;
use App\Services\BillingService;
use App\Services\DebtService;
use App\Services\ForecastService;
use App\Services\ReceiptService;
use App\Support\Nbs;
use Illuminate\Http\Request;

class NbsController extends Controller
{
    /* ── version toggle ─────────────────────────────── */
    public function version(string $v)
    {
        abort_unless(in_array($v, ['v1', 'v2']), 404);
        session(['nbs.version' => $v]);
        return back();
    }

    /* ── overview + catalogue ───────────────────────── */
    public function overview()
    {
        $mods = Process::selectRaw("module_code,
                COUNT(*) total,
                SUM(coverage='covered') covered, SUM(coverage='enhancement') enh, SUM(coverage='gap') gap")
            ->groupBy('module_code')->get()->keyBy('module_code');
        return view('overview', ['mods' => $mods]);
    }

    public function module(string $code)
    {
        $procs = Process::where('module_code', strtoupper($code))->orderBy('sub_module')->orderBy('id')->get();
        abort_if($procs->isEmpty(), 404);
        return view('module', ['code' => strtoupper($code), 'procs' => $procs]);
    }

    public function process(Process $process)
    {
        return view('process', ['p' => $process]);
    }

    /* ── customer 360 ───────────────────────────────── */
    public function customer(Request $r)
    {
        $account = null;
        if ($q = trim((string) $r->query('q'))) {
            $account = Account::where('no', 'like', "%$q%")
                ->orWhereHas('customer', fn ($w) => $w->where('name', 'like', "%$q%"))
                ->with(['customer', 'premise'])->first();
        }
        $account ??= Account::with(['customer', 'premise'])->orderBy('id')->first();
        $bills = $account->bills()->orderByDesc('period')->limit(6)->get();
        $adjustments = $account->adjustments()->latest()->limit(5)->get();
        $arr = $account->arrangements()->with('instalments')->latest()->first();
        return view('customer360', compact('account', 'bills', 'adjustments', 'arr'));
    }

    public function storeAdjustment(Request $r, Account $account)
    {
        // Enhancement-graded: allowed in both versions, but v1 rejects the
        // summary type — the part IWK graded as not yet functional.
        $data = $r->validate([
            'type'   => 'required|in:billing,summary',
            'amount' => 'required|numeric|not_in:0|min:-9999|max:9999',
            'reason' => 'required|string|max:120',
            'effective_date' => 'required|date',
        ]);
        if (Nbs::isV1() && $data['type'] === 'summary') {
            return back()->withErrors(['type' => 'Summary adjustments (IWKSUMADJ) are not functional in Version 1 — graded enhancement by IWK. Switch to the Completed view.']);
        }
        $adj = Adjustment::create([
            'no' => 'ADJ-' . str_pad((string) (Adjustment::count() + 30001), 5, '0', STR_PAD_LEFT),
            'account_id' => $account->id,
        ] + $data);
        return back()->with('ok', "Adjustment {$adj->no} raised for {$account->no} — pending approval.");
    }

    /* ── receipting ─────────────────────────────────── */
    public function receipting(ReceiptService $svc)
    {
        $recent = Receipt::with(['account.customer', 'allocations.bill'])->latest('posted_at')->limit(8)->get();
        $daily = Nbs::on('daily_summary') ? $svc->dailyByType() : null;
        return view('receipting', compact('recent', 'daily'));
    }

    public function storeReceipt(Request $r, ReceiptService $svc)
    {
        $data = $r->validate([
            'account_no' => 'required|exists:accounts,no',
            'amount'     => 'required|numeric|min:0.01|max:100000',
            'method'     => 'required|in:cash,cheque,card,fpx',
        ]);
        $account = Account::where('no', $data['account_no'])->firstOrFail();
        $receipt = $svc->post($account, (float) $data['amount'], $data['method']);
        $alloc = $receipt->allocations->map(fn ($a) => $a->bill->no . ' RM ' . number_format($a->amount, 2))->implode(', ');
        return back()->with('ok', "Receipt {$receipt->no} posted: RM " . number_format($receipt->amount, 2)
            . " — allocated to " . ($alloc ?: 'account credit') . ". Outstanding now RM " . number_format($account->outstanding(), 2) . '.');
    }

    public function voidReceipt(Receipt $receipt, ReceiptService $svc)
    {
        $svc->void($receipt);
        return back()->with('ok', "Receipt {$receipt->no} voided — allocations reversed, bill balances restored.");
    }

    /* ── billing runs ───────────────────────────────── */
    public function billing(BillingService $svc)
    {
        $runs = BillingRun::latest('ran_at')->limit(6)->get();
        $period = now()->format('Y-m');
        $register = $svc->trialRegister($period);
        return view('billing', compact('runs', 'period', 'register'));
    }

    public function runBilling(Request $r, BillingService $svc)
    {
        $data = $r->validate(['period' => 'required|date_format:Y-m', 'mode' => 'required|in:trial,live']);
        $run = $svc->run($data['period'], $data['mode']);
        $msg = $run->mode === 'live'
            ? "Live billing run posted {$run->accounts_billed} bills totalling RM " . number_format($run->total_amount, 2) . " for {$run->period}."
            : "Trial run rated {$run->accounts_billed} accounts (RM " . number_format($run->total_amount, 2) . ") for {$run->period} — nothing posted.";
        return back()->with('ok', $msg);
    }

    /* ── debt recovery ──────────────────────────────── */
    public function debt(DebtService $svc)
    {
        return view('debt', [
            'aging'    => $svc->aging(),
            'worklist' => $svc->worklist(),
            'arrangements' => Arrangement::with(['account.customer', 'instalments'])->latest()->limit(3)->get(),
        ]);
    }

    public function storeArrangement(Request $r, DebtService $svc)
    {
        $data = $r->validate(['account_no' => 'required|exists:accounts,no', 'months' => 'required|integer|min:2|max:24']);
        $account = Account::where('no', $data['account_no'])->firstOrFail();
        $arr = $svc->arrange($account, (int) $data['months']);
        return back()->with('ok', "Arrangement {$arr->no}: RM " . number_format($arr->total, 2)
            . " over {$arr->months} months — {$arr->instalments->count()} instalments generated, first due {$arr->instalments->first()->due_date->format('d M Y')}.");
    }

    /* ── enquiries ──────────────────────────────────── */
    public function enquiries()
    {
        $open = Enquiry::with('account.customer')->where('status', '!=', 'resolved')->orderBy('sla_due')->get();
        $resolved = Enquiry::where('status', 'resolved')->latest()->limit(5)->get();
        return view('enquiries', compact('open', 'resolved'));
    }

    public function storeEnquiry(Request $r)
    {
        $data = $r->validate([
            'account_no' => 'nullable|exists:accounts,no',
            'channel' => 'required|in:counter,call,portal,email',
            'category' => 'required|string|max:60',
            'detail' => 'nullable|string|max:500',
        ]);
        $acct = $data['account_no'] ? Account::where('no', $data['account_no'])->first() : null;
        $e = Enquiry::create([
            'no' => 'ENQ-' . str_pad((string) (Enquiry::count() + 88201), 5, '0', STR_PAD_LEFT),
            'account_id' => $acct?->id,
            'channel' => $data['channel'], 'category' => $data['category'], 'detail' => $data['detail'],
            'sla_due' => now()->addDays(3)->toDateString(),
        ]);
        return back()->with('ok', "Enquiry {$e->no} logged — SLA due {$e->sla_due->format('d M Y')}.");
    }

    public function transitionEnquiry(Request $r, Enquiry $enquiry)
    {
        $to = $r->validate(['to' => 'required|in:with_cems,pending_info,resolved'])['to'];
        $allowed = [
            'open'         => ['with_cems', 'pending_info', 'resolved'],
            'with_cems'    => ['pending_info', 'resolved'],
            'pending_info' => ['with_cems', 'resolved'],
        ];
        abort_unless(in_array($to, $allowed[$enquiry->status] ?? []), 422, 'Invalid status transition.');
        $enquiry->update(['status' => $to, 'assigned_to' => $to === 'with_cems' ? 'CEMS — Zone 4' : $enquiry->assigned_to]);
        return back()->with('ok', "{$enquiry->no} → " . str_replace('_', ' ', $to) . '.');
    }

    /* ── forecasting ────────────────────────────────── */
    public function forecasting(ForecastService $svc)
    {
        if (! Nbs::on('forecasting')) return view('forecast-blocked', [
            'procs' => Process::where('module_code', 'FCST')->get(),
        ]);
        $run = ForecastRun::latest('computed_at')->first();
        return view('forecasting', [
            'run' => $run,
            'stats' => $run ? $svc->stats($run) : null,
            'procs' => Process::where('module_code', 'FCST')->get(),
        ]);
    }

    public function computeForecast(Request $r, ForecastService $svc)
    {
        abort_unless(Nbs::on('forecasting'), 403, 'Forecasting is not available in Version 1 — graded gap by IWK.');
        $data = $r->validate(['adjustment_pct' => 'required|numeric|min:-10|max:10']);
        $run = $svc->compute((float) $data['adjustment_pct']);
        return back()->with('ok', "Forecast recomputed from billed history with {$run->adjustment_pct}% adjustment.");
    }

    public function freezeForecast(ForecastRun $forecast, ForecastService $svc)
    {
        abort_unless(Nbs::on('forecasting'), 403);
        $svc->freeze($forecast);
        return back()->with('ok', "Forecast {$forecast->label} frozen — revenue data locked (IWKFCFRZ).");
    }
}
