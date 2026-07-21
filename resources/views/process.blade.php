@extends('layout')
@section('content')
@php
  $v1 = \App\Support\Nbs::isV1();
  mt_srand($p->id * 7 + 3);
  $names = ['Tetuan Contoh Trading Sdn Bhd', 'Ahmad bin Hassan (Sample)', 'Syarikat Demo Maju Sdn Bhd', 'Lim Demo Enterprise', 'Perniagaan Contoh Jaya', 'S. Kumar (Sample Account)'];
  $acct = fn () => sprintf('88-%06d-%d', mt_rand(100000, 999999), mt_rand(0, 9));
  $rm = fn () => 'RM ' . number_format(mt_rand(2400, 240000) / 100, 2);
  $dt = fn () => date('d M Y', strtotime('2026-01-01 +' . mt_rand(0, 200) . ' days'));
  $chips = ['covered' => ['chip-ok', 'AVAILABLE IN V1'], 'enhancement' => ['chip-enh', 'ENHANCEMENT REQUIRED'], 'gap' => ['chip-gap', 'GAP — NOT IN V1']];
  $mapped = $p->new_process && ! preg_match('/^(gap|n\/?a|enhancement|-)$/i', trim($p->new_process));
@endphp
<div class="page-h"><div class="crumb"><a href="{{ route('overview') }}">Overview</a> / <a href="{{ route('module', $p->module_code) }}">{{ config('modules.names.' . $p->module_code, $p->module_code) }}</a></div>
  <h1>{{ $p->description }}</h1>
  <div class="page-sub"><span class="mono">{{ $p->legacy }}</span> ·
    {{ $mapped ? 'maps to new-system process “' . $p->new_process . '”' : 'no equivalent process in the new system yet' }}
    <span class="chip {{ $chips[$p->coverage][0] }}">{{ $chips[$p->coverage][1] }}</span></div></div>

@if ($p->coverage === 'gap' && $v1)
  <div class="ribbon ribbon-gap"><span>⛔</span><span><b>Not available in Version 1.</b> IWK's Appendix 11 grades this process a gap — the legacy BRAINS capability was never rebuilt. Switch to <b>Completed — Proposed</b> to see it delivered.</span></div>
  <div class="blocked">
    <h3>Function not present in Version 1</h3>
    <p>The legacy process <span class="mono">{{ $p->legacy }}</span> (“{{ $p->description }}”) has no equivalent in the current system — graded <b>GAP</b> by IWK.</p>
    <form method="post" action="{{ route('version', 'v2') }}">@csrf<button class="btn btn-primary">View the completed version →</button></form>
  </div>
@else
  @if ($p->coverage === 'gap')
    <div class="ribbon ribbon-new"><span>★</span><span><b>Delivered by the completion programme.</b> One of the 90 gaps graded by IWK in Appendix 11, rebuilt to the legacy capability <span class="mono">{{ $p->legacy }}</span>.</span></div>
  @elseif ($p->coverage === 'enhancement')
    <div class="ribbon {{ $v1 ? 'ribbon-enh' : 'ribbon-new' }}"><span>{{ $v1 ? '⚠' : '↑' }}</span><span>
      @if ($v1)<b>Partially available in Version 1.</b> IWK grades this process as requiring enhancement — elements marked <span class="chip chip-enh">ENH</span> are not yet functional.
      @else <b>Enhanced by the completion programme.</b> The v1 shortfalls in this process are closed.@endif</span></div>
  @elseif ($v1)
    <div class="ribbon ribbon-ok"><span>✓</span><span><b>Available in Version 1</b> per IWK's Appendix 11 grading. Screen reconstructed from the process description; to be verified against the live system during assessment.</span></div>
  @endif

  @php($enh = $p->coverage === 'enhancement' && $v1)
  @switch($p->screen_type)
    @case('listing')
      <div class="card"><div class="card-h"><h3>{{ $p->description }}</h3>
        <div class="right"><span class="chip chip-sample">SAMPLE DATA</span><button class="btn btn-sm">Export</button><button class="btn btn-sm" @if($enh) disabled @endif>Schedule</button></div></div>
        <table class="tbl"><thead><tr><th>Account</th><th>Name</th><th>Date</th><th class="num">Amount</th></tr></thead><tbody>
          @for ($i = 0; $i < 7; $i++)<tr><td class="mono">{{ $acct() }}</td><td>{{ $names[mt_rand(0, 5)] }}</td><td>{{ $dt() }}</td><td class="num">{{ $rm() }}</td></tr>@endfor
        </tbody></table></div>
      @break
    @case('enquiry')
      <div class="card"><div class="card-h"><h3>Search — {{ $p->description }}</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <div class="card-b"><div class="fgrid fgrid3">
          <div class="field"><label>Account No.</label><div class="ctl">e.g. 88-000000-0</div></div>
          <div class="field"><label>Name contains</label><div class="ctl"></div></div>
          <div class="field"><label>Area</label><div class="ctl sel">All areas</div></div>
        </div><div class="form-actions"><button class="btn btn-primary">Search</button><button class="btn">Clear</button></div></div>
        <table class="tbl"><thead><tr><th>Account</th><th>Name</th><th>Last Activity</th><th class="num">Balance</th></tr></thead><tbody>
          @for ($i = 0; $i < 5; $i++)<tr><td class="mono">{{ $acct() }}</td><td>{{ $names[mt_rand(0, 5)] }}</td><td>{{ $dt() }}</td><td class="num">{{ $rm() }}</td></tr>@endfor
        </tbody></table></div>
      @break
    @case('batch')
      <div class="grid2"><div class="card"><div class="card-h"><h3>{{ $p->description }} — Run Parameters</h3></div>
        <div class="card-b"><div class="fgrid">
          <div class="field"><label>Billing Period</label><div class="ctl sel">JUL 2026</div></div>
          <div class="field"><label>Cycle / Area</label><div class="ctl sel">All cycles</div></div>
          <div class="field"><label>Run Mode</label><div class="ctl sel">Trial</div></div>
          <div class="field"><label>Scheduled</label><div class="ctl">{{ $dt() }}</div></div>
        </div><div class="form-actions"><button class="btn btn-primary">Run Process</button><button class="btn">View Log</button></div></div></div>
        <div class="card"><div class="card-h"><h3>Run History</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <table class="tbl"><thead><tr><th>Run</th><th>Date</th><th class="num">Records</th><th>Status</th></tr></thead><tbody>
          @for ($i = 0; $i < 4; $i++)<tr><td class="mono">RUN-{{ 2600 + $i }}</td><td>{{ $dt() }}</td><td class="num">{{ number_format(mt_rand(500, 40000)) }}</td><td><span class="status-pill {{ $i ? 'st-ok' : 'st-info' }}">{{ $i ? 'Completed' : 'Ready' }}</span></td></tr>@endfor
        </tbody></table></div></div>
      @break
    @case('file')
      <div class="grid2"><div class="card"><div class="card-h"><h3>{{ $p->description }}</h3></div>
        <div class="card-b"><div class="fgrid">
          <div class="field"><label>File / Source</label><div class="ctl sel">Choose file…</div></div>
          <div class="field"><label>Validation Mode</label><div class="ctl sel">Full field-level</div></div>
        </div><div class="form-actions"><button class="btn btn-primary">Upload &amp; Validate</button><button class="btn">Download Template</button></div></div></div>
        <div class="card"><div class="card-h"><h3>Processed Files</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <table class="tbl"><thead><tr><th>File</th><th>Date</th><th class="num">Rows</th><th>Status</th></tr></thead><tbody>
          @for ($i = 0; $i < 4; $i++)<tr><td class="mono">FIL-{{ mt_rand(1000, 9999) }}.txt</td><td>{{ $dt() }}</td><td class="num">{{ number_format(mt_rand(40, 12000)) }}</td><td><span class="status-pill st-ok">Processed</span></td></tr>@endfor
        </tbody></table></div></div>
      @break
    @case('approval')
      <div class="card"><div class="card-h"><h3>{{ $p->description }} — Pending Queue</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <div class="card-b"><div class="steps">
          <div class="step done">Submitted</div><div class="step cur">Checker Review</div><div class="step">Approver</div><div class="step">Applied</div>
        </div></div>
        <table class="tbl"><thead><tr><th>Request</th><th>Raised For</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
          @for ($i = 0; $i < 5; $i++)<tr><td class="mono">REQ-{{ mt_rand(1000, 9999) }}</td><td>{{ $names[mt_rand(0, 5)] }}</td><td>{{ $dt() }}</td><td><span class="status-pill st-warn">Pending</span></td><td><button class="btn btn-sm btn-primary">Approve</button> <button class="btn btn-sm">Reject</button></td></tr>@endfor
        </tbody></table></div>
      @break
    @default
      <div class="card"><div class="card-h"><h3>{{ $p->description }} — Maintenance</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <div class="card-b"><div class="fgrid">
          <div class="field"><label>Account No.</label><div class="ctl mono">{{ $acct() }}</div></div>
          <div class="field"><label>Customer / Payer</label><div class="ctl">{{ $names[mt_rand(0, 5)] }}</div></div>
          <div class="field {{ $enh ? 'disabled' : '' }}"><label>Effective Date @if($enh)<span class="chip chip-enh flag">ENH</span>@endif</label><div class="ctl">{{ $dt() }}</div></div>
          <div class="field"><label>Status</label><div class="ctl">Active</div></div>
          <div class="field"><label>Reference</label><div class="ctl mono">REF-{{ mt_rand(10000, 99999) }}</div></div>
          <div class="field"><label>Remarks</label><div class="ctl">Sample record for demonstration</div></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Save</button><button class="btn">Submit for Approval</button><button class="btn" @if($enh) disabled title="Not available in v1" @endif>Audit Trail</button></div>
      </div></div>
  @endswitch
@endif
@endsection
