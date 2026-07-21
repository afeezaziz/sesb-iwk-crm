@extends('layout')
@section('content')
@php($v1 = \App\Support\Nbs::isV1())
@php($out = $account->outstanding())
<div class="page-h"><div class="crumb">Working journey · CRM &amp; Property Management</div>
  <h1>Customer &amp; Account 360</h1>
  <div class="page-sub">Live account data from this database. Search any seeded account; adjustments post for real (and v1 refuses the parts IWK graded unfinished).</div></div>

<div class="card"><div class="card-b" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
  <div><div style="font-size:17px;font-weight:650">{{ $account->customer->name }} <span class="chip chip-sample">SAMPLE DATA</span></div>
  <div style="color:var(--ink-soft);font-size:12.5px">Account <span class="mono">{{ $account->no }}</span> · Registered {{ $account->registered_at->format('d M Y') }} · {{ $account->category }}</div></div>
  <form method="get" action="{{ route('customer') }}" style="margin-left:auto;display:flex;gap:8px">
    <input class="ctl" style="width:230px;border:1px solid var(--line);border-radius:4px;padding:7px 9px" name="q" placeholder="Search account no. or name" value="{{ request('q') }}">
    <button class="btn btn-primary">Search</button>
  </form>
</div></div>

<div class="tiles">
  <div class="tile {{ $out > 0 ? 't-gap' : 't-ok' }}"><div class="k">OUTSTANDING</div><div class="v">RM {{ number_format($out, 2) }}</div>
    <div class="s">{{ $account->bills->whereIn('status', ['unpaid', 'partial'])->count() }} open bill(s)</div></div>
  <div class="tile"><div class="k">BILLS ON FILE</div><div class="v">{{ $account->bills()->count() }}</div><div class="s">since {{ $account->bills()->min('period') }}</div></div>
  <div class="tile"><div class="k">RECEIPTS</div><div class="v">{{ $account->receipts()->where('status', 'posted')->count() }}</div>
    <div class="s">last: {{ optional($account->receipts()->latest('posted_at')->first())->posted_at?->format('d M Y') ?? '—' }}</div></div>
  <div class="tile {{ $arr ? 't-new' : '' }}"><div class="k">ARRANGEMENT</div><div class="v">{{ $arr ? $arr->no : 'None' }}</div>
    <div class="s">{{ $arr ? $arr->months . ' months · ' . $arr->status : 'no active instalment plan' }}</div></div>
</div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>Recent Bills</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
    <table class="tbl"><thead><tr><th>Bill</th><th>Period</th><th class="num">Amount</th><th class="num">Paid</th><th>Status</th></tr></thead><tbody>
      @foreach ($bills as $b)
        <tr><td class="mono">{{ $b->no }}</td><td>{{ $b->period }}</td><td class="num">RM {{ number_format($b->amount, 2) }}</td>
          <td class="num">{{ $b->paid > 0 ? 'RM ' . number_format($b->paid, 2) : '—' }}</td>
          <td><span class="status-pill {{ $b->status === 'paid' ? 'st-ok' : ($b->status === 'partial' ? 'st-info' : 'st-warn') }}">{{ ucfirst($b->status) }}</span></td></tr>
      @endforeach
    </tbody></table></div>

  @include('partials.gate', ['key' => 'adjustments', 'title' => 'Account Adjustments', 'body' => view('partials.adj-form', compact('account', 'adjustments', 'v1'))->render()])
</div>

<div class="grid2">
  @include('partials.gate', ['key' => 'refunds', 'body' => '<div class="card-b"><div class="fgrid">
      <div class="field"><label>Refund Amount</label><div class="ctl">RM 0.00</div></div>
      <div class="field"><label>Refund Control</label><div class="ctl sel">Standard</div></div>
    </div><div class="form-actions"><button class="btn btn-primary">Initiate Refund</button></div></div>'])
  @include('partials.gate', ['key' => 'ebilling', 'body' => '<div class="card-b"><div class="fgrid">
      <div class="field"><label>E-Bill Status</label><div class="ctl sel">Enrolled — email</div></div>
      <div class="field"><label>Rebate Handling</label><div class="ctl sel">Auto-apply</div></div>
    </div><div class="form-actions"><button class="btn">Cancel Rebate</button><button class="btn">View E-Billing Audit</button></div></div>'])
</div>
@endsection
