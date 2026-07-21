@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist · Customer Enquiry</div>
  <h1>Customer Enquiry Copilot</h1>
  <div class="page-sub">Paste an inbound enquiry; the copilot classifies it, suggests routing to CEMS, and drafts a reply for an agent to approve — cutting handle time on a high-volume channel. <span class="rfp-ref">Customer Enquiry module · CEMS interface · IWK Chatbot</span></div></div>

<div class="ribbon {{ $aiLive ? 'ribbon-ok' : 'ribbon-new' }}"><span>{{ $aiLive ? '●' : '○' }}</span><span>
  @if($aiLive)<b>LLM endpoint live</b> — the reply is model-drafted.
  @else <b>Running on deterministic classifier + templates</b> — classification and routing work with no model; an on-prem endpoint drafts a richer reply.@endif
  &nbsp;Replies are agent-approved before sending — never auto-sent.</span></div>

<div class="card"><div class="card-b">
  <form method="get" action="{{ route('ai.enquiry') }}">
    <div class="field"><label>Inbound enquiry text</label>
      <textarea class="ctl" name="text" rows="3" style="min-height:70px">{{ $result['text'] ?? '' }}</textarea></div>
    <div class="form-actions"><button class="btn btn-primary">Analyse &amp; draft reply</button></div>
  </form>
  <div style="margin-top:6px;font-size:11.5px;color:var(--ink-faint)">Try:
    @foreach ($samples as $s)<a href="{{ route('ai.enquiry', ['text'=>$s]) }}">“{{ \Illuminate\Support\Str::limit($s, 42) }}”</a>@if(!$loop->last) · @endif @endforeach
  </div>
</div></div>

@if ($result)
  <div class="grid2">
    <div class="card"><div class="card-h"><h3>Classification</h3><div class="right"><span class="ai-badge">AI PROPOSED</span></div></div>
      <div class="card-b">
        <div class="tiles" style="grid-template-columns:1fr 1fr;margin-bottom:12px">
          <div class="tile"><div class="k">CATEGORY</div><div class="v" style="font-size:16px">{{ $result['classify']['category'] }}</div></div>
          <div class="tile t-ok"><div class="k">CONFIDENCE</div><div class="v">{{ $result['classify']['confidence'] }}%</div></div>
        </div>
        <table class="tbl"><tbody>
          <tr><td style="width:120px;color:var(--ink-soft)">Route to</td><td><b>{{ $result['classify']['routing'] }}</b></td></tr>
          <tr><td style="color:var(--ink-soft)">Signals</td><td class="mono" style="font-size:12px">{{ implode(', ', $result['classify']['matched']) ?: '—' }}</td></tr>
        </tbody></table>
        <div style="font-size:11.5px;color:var(--ink-faint);margin-top:8px">Agent may override the category before routing — human decides.</div>
      </div></div>

    <div class="card"><div class="card-h"><h3>Suggested reply</h3><div class="right"><span class="ai-badge {{ $aiLive?'ai-live':'' }}">{{ $aiLive?'MODEL-DRAFTED':'TEMPLATE' }}</span></div></div>
      <div class="card-b">
        <div style="white-space:pre-line;font-size:13px;background:#F7F9FA;border:1px solid var(--line-soft);border-radius:var(--r);padding:12px">{{ $result['reply'] }}</div>
        <div class="form-actions"><button class="btn btn-primary" onclick="return false">Approve &amp; send</button><button class="btn" onclick="return false">Edit</button></div>
      </div></div>
  </div>
@endif
@endsection
