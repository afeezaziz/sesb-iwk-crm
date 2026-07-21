@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist · Business Analysis</div>
  <h1>URS / Gap Drafter</h1>
  <div class="page-sub">Pick any gap or enhancement process; the assistant drafts a first-cut User Requirement Specification from its Appendix-11 metadata and the RFP module descriptions — the exact deliverable this engagement is scored on. <span class="rfp-ref">RFP §7.2.4 · Appendix 1 §I</span></div></div>

<div class="ribbon {{ $aiLive ? 'ribbon-ok' : 'ribbon-new' }}"><span>{{ $aiLive ? '●' : '○' }}</span><span>
  @if($aiLive)<b>LLM endpoint live</b> — functional requirements are model-generated per process.
  @else <b>Running on deterministic templates</b> — structured skeleton with family-specific requirements; an on-prem model endpoint enriches the functional-requirements section.@endif
  &nbsp;Every draft is a starting point for a human BA — never auto-final.</span></div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px">
  <div class="card" style="margin:0"><div class="card-h"><h3>Gap &amp; enhancement processes</h3></div>
    <div class="pick">
      @foreach ($gaps as $g)
        <a href="{{ route('ai.urs', ['p' => $g->id]) }}" class="{{ $selected && $selected->id===$g->id ? 'on':'' }}">
          <span class="chip {{ $g->coverage==='gap'?'chip-gap':'chip-enh' }}" style="flex-shrink:0">{{ $g->coverage==='gap'?'GAP':'ENH' }}</span>
          <span>{{ $g->description }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <div class="card" style="margin:0">
    @if (! $draft)
      <div class="card-b"><div class="panel-blocked" style="border-style:solid;border-color:var(--line);background:#F7F9FA;color:var(--ink-soft)">Select a process on the left to generate a URS draft.</div></div>
    @else
      <div class="card-h"><h3>{{ $draft['title'] }}</h3><div class="right">
        <span class="ai-badge {{ $draft['source']==='llm'?'ai-live':'' }}">{{ $draft['source']==='llm' ? 'MODEL-GENERATED' : 'TEMPLATE DRAFT' }}</span></div></div>
      <div class="card-b urs-doc">
        <div style="font-size:12px;color:var(--ink-faint)"><span class="mono">{{ $draft['ref'] }}</span> · module {{ $draft['module'] }} · IWK grade: {{ $draft['grade'] }}</div>
        <h4>1 · Purpose</h4><p>{{ $draft['purpose'] }}</p>
        <h4>2 · Scope</h4><ul>@foreach($draft['scope'] as $s)<li>{{ $s }}</li>@endforeach</ul>
        <h4>3 · Functional Requirements</h4>
        @foreach($draft['functional'] as $fr)<div class="fr">{{ $fr }}</div>@endforeach
        <h4>4 · Integration</h4><ul>@foreach($draft['integration'] as $i)<li>{{ $i }}</li>@endforeach</ul>
        <h4>5 · Non-Functional</h4><ul>@foreach($draft['nonfunctional'] as $n)<li>{{ $n }}</li>@endforeach</ul>
        <h4>6 · Acceptance Criteria</h4><ul>@foreach($draft['acceptance'] as $a)<li>{{ $a }}</li>@endforeach</ul>
        <h4>7 · Open Items for the 4–5 Aug Access Window</h4><ul>@foreach($draft['open'] as $o)<li>{{ $o }}</li>@endforeach</ul>
        <div style="margin-top:14px"><button class="btn btn-sm btn-primary" onclick="return false">Export draft</button>
          <span style="font-size:11.5px;color:var(--ink-faint);margin-left:8px">Draft for BA review — not a final signed URS.</span></div>
      </div>
    @endif
  </div>
</div>
@endsection
