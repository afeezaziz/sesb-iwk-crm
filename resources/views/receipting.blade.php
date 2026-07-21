@extends('layout')
@section('content')
@php($v1 = \App\Support\Nbs::isV1())
<div class="page-h"><div class="crumb">Working journey · Cash Receipting</div>
  <h1>Counter Receipting</h1>
  <div class="page-sub">Post a payment against any seeded account — allocation runs oldest-bill-first for real, updates balances, and can be voided. In v1 the batch-control and daily-summary functions are off, because IWK graded them gap.</div></div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>Receipt Entry</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
    <div class="card-b">
      <form method="post" action="{{ route('receipt.store') }}">@csrf
        <div class="fgrid">
          <div class="field"><label>Account No.</label><input class="ctl mono" name="account_no" placeholder="88-102000-0" value="{{ old('account_no', \App\Models\Account::whereHas('bills', fn ($q) => $q->whereIn('status', ['unpaid', 'partial']))->first()?->no) }}"></div>
          <div class="field"><label>Amount (RM)</label><input class="ctl" type="number" step="0.01" min="0.01" name="amount" placeholder="96.00"></div>
          <div class="field"><label>Method</label><select class="ctl" name="method"><option>cash</option><option>cheque</option><option>card</option><option>fpx</option></select></div>
          <div class="field"><label>Teller</label><div class="ctl">KL-HQ · counter-05</div></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Post Receipt</button></div>
      </form>
    </div>
    <div class="card-h" style="border-top:1px solid var(--line-soft)"><h3>Recent Receipts</h3></div>
    <table class="tbl"><thead><tr><th>Receipt</th><th>Account</th><th class="num">Amount</th><th>Allocated To</th><th></th></tr></thead><tbody>
      @foreach ($recent as $r)
        <tr><td class="mono">{{ $r->no }}{!! $r->status === 'voided' ? ' <span class="status-pill st-mut">voided</span>' : '' !!}</td>
          <td class="mono">{{ $r->account->no }}</td><td class="num">RM {{ number_format($r->amount, 2) }}</td>
          <td style="font-size:11.5px">{{ $r->allocations->map(fn ($a) => $a->bill->no)->implode(', ') ?: 'credit' }}</td>
          <td>@if ($r->status === 'posted')<form class="inline-form" method="post" action="{{ route('receipt.void', $r) }}">@csrf<button class="btn btn-sm">Void</button></form>@endif</td></tr>
      @endforeach
    </tbody></table></div>

  <div>
    @include('partials.gate', ['key' => 'batch_control', 'body' => '<div class="card-b"><div class="fgrid">
        <div class="field"><label>Batch</label><div class="ctl mono">BC-' . now()->format('ymd') . '-05</div></div>
        <div class="field"><label>Control Total</label><div class="ctl">RM ' . number_format((float) \App\Models\Receipt::where('status', 'posted')->whereDate('posted_at', now())->sum('amount'), 2) . '</div></div>
        <div class="field"><label>System Total</label><div class="ctl">RM ' . number_format((float) \App\Models\Receipt::where('status', 'posted')->whereDate('posted_at', now())->sum('amount'), 2) . '</div></div>
        <div class="field"><label>Variance</label><div class="ctl">RM 0.00</div></div>
      </div><div class="form-actions"><button class="btn btn-primary">Check Batch</button><button class="btn">Regenerate Control</button><button class="btn">Rebuild Unposted</button></div></div>'])

    @php($dailyRows = $daily ? collect($daily)->map(fn ($d) => '<tr><td>' . ucfirst($d['method']) . '</td><td class="num">' . $d['count'] . '</td><td class="num">RM ' . number_format($d['total'], 2) . '</td></tr>')->implode('') : '')
    @include('partials.gate', ['key' => 'daily_summary', 'title' => 'Daily Receipts — by Type (live query)', 'body' => '<table class="tbl"><thead><tr><th>Type</th><th class="num">Count</th><th class="num">Amount</th></tr></thead><tbody>' . $dailyRows . '</tbody></table>'])
  </div>
</div>
@endsection
