@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist · Reporting</div>
  <h1>Ask Your Billing Data</h1>
  <div class="page-sub">Natural-language reporting over live billing data — answers come from real aggregate queries, not a canned deck. Addresses the reporting exposure of 34 in-scope reports with no URS and 56 with platform TBC. <span class="rfp-ref">Reporting module · Appendix 9</span></div></div>

<div class="ribbon {{ $aiLive ? 'ribbon-ok' : 'ribbon-new' }}"><span>{{ $aiLive ? '●' : '○' }}</span><span>
  @if($aiLive)<b>LLM endpoint live</b> — free-form questions mapped to the safe query set by the model.
  @else <b>Deterministic intent-matching</b> — the model only ever <i>selects</i> a whitelisted query, never writes SQL, so there is no injection surface. An on-prem endpoint widens the phrasing understood.@endif</span></div>

<div class="card"><div class="card-b">
  <form method="get" action="{{ route('ai.reporting') }}">
    <div class="field"><label>Question</label><input class="ctl" name="q" value="{{ $question }}" placeholder="e.g. total outstanding by period"></div>
    <div class="form-actions"><button class="btn btn-primary">Ask</button></div>
  </form>
  <div style="margin-top:6px;font-size:11.5px;color:var(--ink-faint)">Try:
    @foreach ($samples as $s)<a href="{{ route('ai.reporting', ['q'=>$s]) }}">{{ $s }}</a>@if(!$loop->last) · @endif @endforeach
  </div>
</div></div>

@if ($result)
  <div class="card"><div class="card-h"><h3>{{ $result['title'] }}</h3><div class="right"><span class="ai-badge {{ $aiLive?'ai-live':'' }}">{{ $result['intent']==='help'?'PICK A QUESTION':'LIVE QUERY' }}</span></div></div>
    <table class="tbl"><thead><tr>@foreach($result['columns'] as $c)<th class="{{ $loop->index? 'num':'' }}">{{ $c }}</th>@endforeach</tr></thead><tbody>
      @forelse ($result['rows'] as $row)
        <tr>@foreach($row as $cell)<td class="{{ $loop->index? 'num mono':'' }}">{{ $cell }}</td>@endforeach</tr>
      @empty<tr><td colspan="{{ count($result['columns']) }}" style="color:var(--ink-faint)">No data.</td></tr>@endforelse
    </tbody></table>
    <div class="card-b" style="padding-top:8px;font-size:11.5px;color:var(--ink-faint)">{{ $result['note'] }}</div>
  </div>
@endif
@endsection
