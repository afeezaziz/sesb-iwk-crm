@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist · Assessment</div>
  <h1>Assessment Triage — 242 backlog items</h1>
  <div class="page-sub">Classifies IWK's Appendix-10 JIRA gap list into scope categories that drive the delivery plan. The model proposes; a named engineer confirms or overrides — only confirmed items count as scope. <span class="rfp-ref">RFP §7.2.3 System Assessment</span></div></div>

<div class="ribbon {{ $aiLive ? 'ribbon-ok' : 'ribbon-new' }}"><span>{{ $aiLive ? '●' : '○' }}</span><span>
  @if($aiLive)<b>LLM endpoint live</b> — proposals refined by the model.
  @else <b>Deterministic classifier active</b> — proposals from ticket wording + JIRA status; an on-prem model sharpens edge cases.@endif
  &nbsp;Confidence below {{ $floor }} is routed to review, never auto-accepted. <b>Model proposes, engineer decides.</b></span></div>

<div class="tiles">
  <div class="tile"><div class="k">BACKLOG</div><div class="v">{{ $summary['total'] }}</div><div class="s">{{ $summary['no_key'] }} without JIRA key (C-1)</div></div>
  <div class="tile t-new"><div class="k">CLASSIFIED</div><div class="v">{{ $summary['classified'] }}</div><div class="s">AI-proposed</div></div>
  <div class="tile t-ok"><div class="k">CONFIRMED</div><div class="v">{{ $summary['confirmed'] }}</div><div class="s">counts as scope</div></div>
  <div class="tile {{ $summary['below_floor'] ? 't-enh':'' }}"><div class="k">BELOW FLOOR</div><div class="v">{{ $summary['below_floor'] }}</div><div class="s">routed to review</div></div>
</div>

<div class="card"><div class="card-b" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <form method="post" action="{{ route('ai.triage.run') }}" class="inline-form">@csrf<button class="btn btn-primary">✦ Run AI classification on pending</button></form>
  <div style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
    <a class="btn btn-sm {{ !$module?'btn-primary':'' }}" href="{{ route('ai.triage') }}">All</a>
    @foreach ($modules as $m)<a class="btn btn-sm {{ $module===$m?'btn-primary':'' }}" href="{{ route('ai.triage', ['module'=>$m]) }}">{{ $m }}</a>@endforeach
  </div>
</div></div>

@if (!empty($summary['by_class']))
  <div class="card"><div class="card-h"><h3>Proposed scope mix</h3></div><div class="card-b"><div class="legend">
    @foreach ($classes as $k => $label)
      <span><b>{{ $summary['by_class'][$k] ?? 0 }}</b> · {{ $label }}</span>
    @endforeach
  </div></div></div>
@endif

<div class="card"><div class="card-h"><h3>Backlog items</h3></div>
  <table class="tbl"><thead><tr><th>JIRA</th><th>Module</th><th>Title</th><th>JIRA status</th><th>AI proposal</th><th class="num">Conf.</th><th>Decision</th></tr></thead><tbody>
    @foreach ($items as $it)
      <tr>
        <td class="mono">{{ $it->jira_key ?: '—' }}</td><td class="mono">{{ $it->module_code }}</td>
        <td style="max-width:280px">{{ $it->title }}</td>
        <td style="font-size:11.5px;color:var(--ink-soft)">{{ $it->jira_status ?: '(blank)' }}</td>
        <td>@if($it->classification)<span class="chip {{ ['missing_process'=>'chip-gap','enhancement'=>'chip-enh','defect'=>'chip-sample','net_new'=>'chip-new'][$it->classification] }}">{{ $classes[$it->classification] }}</span>@else <span style="color:var(--ink-faint)">— run AI —</span>@endif</td>
        <td class="num">@if($it->confidence)<span style="{{ $it->confidence < $floor ? 'color:var(--enh);font-weight:700':'' }}">{{ $it->confidence }}</span>@else—@endif</td>
        <td>
          @if($it->triage_state !== 'pending')
            <span class="chip {{ $it->triage_state==='overridden'?'chip-enh':'chip-ok' }}">{{ strtoupper($it->triage_state) }}</span>
          @elseif($it->classification)
            <form method="post" action="{{ route('ai.triage.confirm', $it) }}" class="inline-form">@csrf
              <button class="btn btn-sm btn-primary">Confirm</button>
              <select name="override" class="btn btn-sm" onchange="this.form.submit()" style="max-width:120px">
                <option value="">override…</option>
                @foreach($classes as $k=>$label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
              </select>
            </form>
          @else <span style="color:var(--ink-faint);font-size:11.5px">classify first</span>@endif
        </td>
      </tr>
    @endforeach
  </tbody></table>
</div>
@endsection
