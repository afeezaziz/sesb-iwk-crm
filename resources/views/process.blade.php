@extends('layout')
@section('content')
@php
  use App\Support\Nbs;
  $engine = app(App\Services\ProcessEngine::class);
  $v1 = Nbs::isV1();
  $chips = ['covered' => ['chip-ok', 'AVAILABLE IN V1'], 'enhancement' => ['chip-enh', 'ENHANCEMENT REQUIRED'], 'gap' => ['chip-gap', 'GAP — NOT IN V1']];
  $mapped = $p->new_process && ! preg_match('/^(gap|n\/?a|enhancement|-)$/i', trim($p->new_process));
  $fields = $engine->fields($p);
  $stats = $engine->stats($p);
  $records = $engine->records($p);
  $runs = $engine->runs($p);
@endphp
<div class="page-h"><div class="crumb"><a href="{{ route('overview') }}">Overview</a> / <a href="{{ route('module', $p->module_code) }}">{{ config('modules.names.' . $p->module_code, $p->module_code) }}</a></div>
  <h1>{{ $p->description }}</h1>
  <div class="page-sub"><span class="mono">{{ $p->legacy }}</span> ·
    {{ $mapped ? 'maps to new-system process “' . $p->new_process . '”' : 'no equivalent process in the new system yet' }}
    <span class="chip {{ $chips[$p->coverage][0] }}">{{ $chips[$p->coverage][1] }}</span>
    <span class="rfp-ref" style="margin-left:6px">operable · generic engine</span></div></div>

@if ($p->coverage === 'gap' && $v1)
  <div class="ribbon ribbon-gap"><span>⛔</span><span><b>Not available in Version 1.</b> IWK grades this process a gap — the server refuses operations here in the as-inherited view. Switch to <b>Completed — Proposed</b> to operate it.</span></div>
  <div class="blocked">
    <h3>Function not present in Version 1</h3>
    <p>The legacy process <span class="mono">{{ $p->legacy }}</span> (“{{ $p->description }}”) has no equivalent in the current system — graded <b>GAP</b> by IWK.</p>
    <form method="post" action="{{ route('version', 'v2') }}">@csrf<button class="btn btn-primary">Operate the completed version →</button></form>
  </div>
@else
  @if ($p->coverage === 'gap')
    <div class="ribbon ribbon-new"><span>★</span><span><b>Delivered by the completion programme.</b> One of the 90 gaps graded by IWK, now fully operable below (create / search / run / approve persist with audit).</span></div>
  @elseif ($p->coverage === 'enhancement')
    <div class="ribbon {{ $v1 ? 'ribbon-enh' : 'ribbon-new' }}"><span>{{ $v1 ? '⚠' : '↑' }}</span><span>
      @if($v1)<b>Partially available in Version 1.</b> Operable, but elements IWK graded as enhancement are flagged.@else <b>Enhanced by the completion programme.</b> The v1 shortfalls are closed.@endif</span></div>
  @elseif ($v1)
    <div class="ribbon ribbon-ok"><span>✓</span><span><b>Available in Version 1</b> per IWK's grading. Fully operable below; to be reconciled against the live system during assessment.</span></div>
  @endif

  <div class="tiles" style="grid-template-columns:repeat(3,1fr)">
    <div class="tile"><div class="k">RECORDS</div><div class="v">{{ $stats['records'] }}</div><div class="s">persisted for this process</div></div>
    <div class="tile {{ $stats['pending'] ? 't-enh' : '' }}"><div class="k">PENDING</div><div class="v">{{ $stats['pending'] }}</div><div class="s">awaiting approval</div></div>
    <div class="tile"><div class="k">RUNS</div><div class="v">{{ $stats['runs'] }}</div><div class="s">batch / file executions</div></div>
  </div>

  @php($type = $p->screen_type)

  {{-- ── BATCH / FILE: run parameters + run history ── --}}
  @if (in_array($type, ['batch', 'file']))
    <div class="grid2">
      <div class="card"><div class="card-h"><h3>{{ $type === 'file' ? 'Upload & Validate' : 'Run Parameters' }}</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
        <div class="card-b"><form method="post" action="{{ route('process.run', $p) }}">@csrf
          <div class="fgrid">
            @foreach ($fields as $f)
              <div class="field"><label>{{ $f['label'] }}</label>
                @if ($f['type'] === 'select')
                  <select class="ctl" name="{{ $f['key'] }}">@foreach($f['options'] as $o)<option>{{ $o }}</option>@endforeach</select>
                @else<input class="ctl" type="{{ $f['type'] }}" name="{{ $f['key'] }}" value="{{ $f['default'] }}">@endif
              </div>
            @endforeach
          </div>
          <div class="form-actions"><button class="btn btn-primary">{{ $type === 'file' ? 'Upload & Validate' : 'Run Process' }}</button></div>
        </form></div></div>
      <div class="card"><div class="card-h"><h3>Run History</h3></div>
        <table class="tbl"><thead><tr><th>When</th><th>Result</th><th class="num">Records</th></tr></thead><tbody>
          @forelse ($runs as $run)
            <tr><td>{{ $run->ran_at->format('d M H:i') }}</td><td style="font-size:12px">{{ $run->log }}</td><td class="num">{{ number_format($run->records_affected) }}</td></tr>
          @empty<tr><td colspan="3" style="color:var(--ink-faint)">No runs yet — run one.</td></tr>@endforelse
        </tbody></table></div>
    </div>

  {{-- ── ENQUIRY: search over persisted records ── --}}
  @elseif ($type === 'enquiry')
    <div class="card"><div class="card-h"><h3>Search — {{ $p->description }}</h3></div>
      <div class="card-b"><form method="get" action="{{ route('process.search', $p) }}">
        <div class="fgrid fgrid3">
          <div class="field"><label>Reference or Account</label><input class="ctl" name="q" value="{{ $searchTerm }}" placeholder="ref or 88-…"></div>
        </div><div class="form-actions"><button class="btn btn-primary">Search</button></div>
      </form></div>
      @if ($searchResults !== null)
        <table class="tbl"><thead><tr><th>Reference</th><th>Account</th><th class="num">Amount</th><th>Status</th><th>Created</th></tr></thead><tbody>
          @forelse ($searchResults as $rec)
            <tr><td class="mono">{{ $rec->reference }}</td><td class="mono">{{ $rec->account_no ?? '—' }}</td><td class="num">{{ $rec->amount ? 'RM ' . number_format($rec->amount, 2) : '—' }}</td>
              <td><span class="status-pill st-ok">{{ ucfirst($rec->status) }}</span></td><td>{{ $rec->created_at->format('d M') }}</td></tr>
          @empty<tr><td colspan="5" style="color:var(--ink-faint)">No matching records for “{{ $searchTerm }}”.</td></tr>@endforelse
        </tbody></table>
      @endif
    </div>
    @include('partials.process-records', ['records' => $records, 'p' => $p])

  {{-- ── FORM / LISTING / APPROVAL: create + list (+ approve) ── --}}
  @else
    <div class="grid2">
      <div class="card"><div class="card-h"><h3>{{ $type === 'approval' ? 'Submit for Approval' : ($type === 'listing' ? 'Add Entry' : 'Maintenance') }}</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
        <div class="card-b"><form method="post" action="{{ route('process.store', $p) }}">@csrf
          <div class="fgrid">
            @foreach ($fields as $f)
              @php($dis = $v1 && $p->coverage === 'enhancement' && $loop->index === count($fields) - 1)
              <div class="field {{ $dis ? 'disabled' : '' }}"><label>{{ $f['label'] }} @if($dis)<span class="chip chip-enh flag">ENH</span>@endif</label>
                <input class="ctl" type="{{ $f['type'] === 'number' ? 'number' : $f['type'] }}" @if($f['type']==='number') step="0.01" @endif name="{{ $f['key'] }}" value="{{ $f['default'] }}" {{ $dis ? 'disabled' : '' }}></div>
            @endforeach
          </div>
          <div class="form-actions"><button class="btn btn-primary">{{ $type === 'approval' ? 'Submit' : 'Save' }}</button></div>
        </form></div></div>
      <div class="card"><div class="card-h"><h3>Records</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
        <table class="tbl"><thead><tr><th>Reference</th><th>Account</th><th class="num">Amount</th><th>Status</th>@if($type==='approval')<th>Action</th>@endif</tr></thead><tbody>
          @forelse ($records as $rec)
            <tr><td class="mono">{{ $rec->reference }}</td><td class="mono">{{ $rec->account_no ?? '—' }}</td><td class="num">{{ $rec->amount ? 'RM ' . number_format($rec->amount, 2) : '—' }}</td>
              <td><span class="status-pill {{ ['active'=>'st-ok','pending'=>'st-warn','approved'=>'st-ok','rejected'=>'st-mut'][$rec->status] ?? 'st-mut' }}">{{ ucfirst($rec->status) }}</span></td>
              @if($type==='approval')<td>@if($rec->status==='pending')
                <form class="inline-form" method="post" action="{{ route('process.decide', $rec) }}">@csrf<input type="hidden" name="verdict" value="approved"><button class="btn btn-sm btn-primary">Approve</button></form>
                <form class="inline-form" method="post" action="{{ route('process.decide', $rec) }}">@csrf<input type="hidden" name="verdict" value="rejected"><button class="btn btn-sm">Reject</button></form>
                @else <span style="font-size:11.5px;color:var(--ink-faint)">by {{ $rec->decided_by }}</span>@endif</td>@endif</tr>
          @empty<tr><td colspan="{{ $type==='approval'?5:4 }}" style="color:var(--ink-faint)">No records yet — create one.</td></tr>@endforelse
        </tbody></table></div>
    </div>
  @endif
@endif
@endsection
