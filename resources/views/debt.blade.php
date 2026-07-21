@extends('layout')
@section('content')
@php($v1 = \App\Support\Nbs::isV1())
<div class="page-h"><div class="crumb">Working journey · Debt Recovery</div>
  <h1>Debt Recovery Workbench</h1>
  <div class="page-sub">Aging computed live from unpaid bills in this database; instalment arrangements generate a real schedule. The DCA suite is partial in v1 — 15 processes graded enhancement by IWK.</div></div>

<div class="tiles">
  @foreach ($aging as $bucket => $x)
    <div class="tile {{ $bucket === '> 180 d' ? 't-gap' : '' }}"><div class="k">{{ strtoupper($bucket) }}</div>
      <div class="v">RM {{ number_format($x['amount'] / 1000, 1) }}k</div><div class="s">{{ $x['accounts'] }} account(s) · live query</div></div>
  @endforeach
</div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>Arrears Worklist (live)</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
    <table class="tbl"><thead><tr><th>Account</th><th>Customer</th><th class="num">Arrears</th><th>Age</th><th>Stage</th></tr></thead><tbody>
      @foreach ($worklist as $a)
        <tr><td class="mono">{{ $a->no }}</td><td>{{ $a->customer->name }}</td><td class="num">RM {{ number_format($a->arrears, 2) }}</td>
          <td>{{ $a->age_days }} d</td>
          <td><span class="status-pill {{ $a->stage === 'Legal review' ? 'st-info' : 'st-warn' }}">{{ $a->stage }}</span></td></tr>
      @endforeach
    </tbody></table>
    <div class="card-b" style="padding-top:10px"><div class="steps">
      <div class="step done">Reminder 1</div><div class="step done">Reminder 2</div><div class="step cur">DCA / Arrangement</div><div class="step">Legal (JID/CTOS)</div><div class="step">Write-off</div>
    </div><div style="font-size:12px;color:var(--ink-soft)">Action schedule driven by <span class="mono">IWKDRSCH</span> —
      <span class="chip {{ $v1 ? 'chip-enh' : 'chip-enh2' }}">{{ $v1 ? 'PARTIAL IN V1' : 'ENHANCED' }}</span></div></div></div>

  <div>
    <div class="card"><div class="card-h"><h3>Create Instalment Arrangement</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
      <div class="card-b">
        <form method="post" action="{{ route('arrangement.store') }}">@csrf
          <div class="fgrid">
            <div class="field"><label>Account No. (with arrears)</label><input class="ctl mono" name="account_no" value="{{ old('account_no', $worklist->first()?->no) }}"></div>
            <div class="field"><label>Months (2–24)</label><input class="ctl" type="number" name="months" min="2" max="24" value="6"></div>
          </div>
          <div class="form-actions"><button class="btn btn-primary">Generate Schedule</button></div>
        </form>
        @foreach ($arrangements as $arr)
          <div style="margin-top:12px;font-size:12.5px"><b class="mono">{{ $arr->no }}</b> · {{ $arr->account->customer->name }} ·
            RM {{ number_format($arr->total, 2) }} / {{ $arr->months }} mo</div>
          <table class="tbl"><thead><tr><th>#</th><th>Due</th><th class="num">Amount</th><th>Status</th></tr></thead><tbody>
            @foreach ($arr->instalments->take(4) as $i)
              <tr><td>{{ $i->seq }}</td><td>{{ $i->due_date->format('d M Y') }}</td><td class="num">RM {{ number_format($i->amount, 2) }}</td>
                <td><span class="status-pill st-mut">{{ ucfirst($i->status) }}</span></td></tr>
            @endforeach
            @if ($arr->instalments->count() > 4)<tr><td colspan="4" style="color:var(--ink-faint)">… {{ $arr->instalments->count() - 4 }} more instalments</td></tr>@endif
          </tbody></table>
        @endforeach
      </div></div>

    @include('partials.gate', ['key' => 'dca_suite', 'title' => 'DCA Management', 'body' => '<table class="tbl"><thead><tr><th>DCA</th><th class="num">Assigned</th><th class="num">Collected</th><th>File Status</th></tr></thead><tbody>
        <tr><td>Agensi Contoh A</td><td class="num">1,204</td><td class="num">RM 402k</td><td><span class="status-pill st-ok">Reconciled</span></td></tr>
        <tr><td>Demo Recovery B</td><td class="num">886</td><td class="num">RM 251k</td><td><span class="status-pill st-warn">2 variances</span></td></tr>
      </tbody></table>'])
  </div>
</div>
@endsection
