@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist</div>
  <h1>AI, applied where it de-risks the recovery</h1>
  <div class="page-sub">Not AI for show. Each feature targets a specific, named risk or scored deliverable in this tender — and every one runs with a human in the loop. <span class="rfp-ref">Appendix 3 · optional line “AI Tools”</span></div></div>

<div class="card"><div class="card-b" style="font-size:13.5px;line-height:1.6">
  <p style="margin-bottom:10px">IWK’s system was set back once by a vendor who over-promised. So the AI here is deliberately pragmatic: it <b>proposes, a named officer decides</b>, confidential data is <b>redacted before it ever leaves the process</b>, and every feature has a deterministic fallback so the system keeps working with no model at all. The design assumes an <b>on-prem / private-cloud model endpoint</b> inside IWK’s RedHat estate — nothing here requires customer data to leave the building.</p>
  <p>Status of the model endpoint in this demo:
    @if ($aiLive)<span class="ai-badge ai-live">● LLM ENDPOINT LIVE</span> natural-language generation active.
    @else <span class="ai-badge">○ RUNNING ON DETERMINISTIC FALLBACK</span> — set an on-prem model endpoint to light up generated text; all detection/classification below already works without it.@endif</p>
</div></div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>1 · Billing Anomaly Detection</h3><div class="right"><span class="ai-badge">FLAGSHIP</span></div></div>
    <div class="card-b" style="font-size:13px">Scans issued bills &amp; adjustments for the errors a <b>retrospective-recalculation</b> engine produces — the hardest correctness problem in the system. <span class="rfp-ref">RFP §2 · Risk R-01</span><br>
      <a class="btn btn-sm btn-primary" style="margin-top:10px" href="{{ route('ai.anomalies') }}">Open detector →</a></div></div>

  <div class="card"><div class="card-h"><h3>2 · URS / Gap Drafter</h3><div class="right"><span class="ai-badge">ON-THESIS</span></div></div>
    <div class="card-b" style="font-size:13px">Turns a 90-item gap list into review-ready URS drafts — the exact deliverable this engagement is scored on. <span class="rfp-ref">RFP §7.2.4 · App 1 §I</span><br>
      <a class="btn btn-sm btn-primary" style="margin-top:10px" href="{{ route('ai.urs') }}">Open drafter →</a></div></div>

  <div class="card"><div class="card-h"><h3>3 · Customer Enquiry Copilot</h3><div class="right"><span class="ai-badge">CENQ</span></div></div>
    <div class="card-b" style="font-size:13px">Auto-classifies inbound enquiries, suggests routing to CEMS, and drafts an agent-approved reply on a high-volume channel. <span class="rfp-ref">Customer Enquiry · CEMS · Chatbot</span><br>
      <a class="btn btn-sm btn-primary" style="margin-top:10px" href="{{ route('ai.enquiry') }}">Open copilot →</a></div></div>

  <div class="card"><div class="card-h"><h3>On the roadmap (priced, not built for demo)</h3></div>
    <div class="card-b" style="font-size:12.5px;color:var(--ink-soft)">
      <b>Migration reconciliation AI</b> — field-level anomaly checks on the MS-SQL→Oracle migration (§7.2.7). ·
      <b>NL reporting</b> — “ask your billing data”, addressing the 34 reports with no URS (App 9). ·
      <b>Assessment triage</b> — classify the 242 JIRA gaps (§7.2.3). All governed the same way: model proposes, human decides.</div></div>
</div>
@endsection
