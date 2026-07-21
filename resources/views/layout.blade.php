<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>IWK NBS — Completion Demonstration Prototype</title>
<link rel="stylesheet" href="{{ asset('css/nbs.css') }}">
</head>
@php($v = \App\Support\Nbs::version())
<body data-v="{{ $v }}">
<div class="app">
  <div class="topbar">
    <div class="brand"><b>IWK NBS — Billing &amp; CRM</b><span>Completion Demonstration Prototype · not production software</span></div>
    <div class="top-spacer"></div>
    <div class="vtoggle">
      <form method="post" action="{{ route('version', 'v1') }}">@csrf
        <button class="{{ $v === 'v1' ? 'active' : '' }}">Version 1 — As-Is<span class="sub">incomplete, per IWK grading</span></button></form>
      <form method="post" action="{{ route('version', 'v2') }}">@csrf
        <button class="{{ $v === 'v2' ? 'active' : '' }}">Completed — Proposed<span class="sub">after completion programme</span></button></form>
    </div>
    <div class="demo-chip">DEMO PROTOTYPE</div>
  </div>
  <div class="statebar"><span class="dot"></span>
    @if ($v === 'v1')
      <span><b>Viewing Version 1 as inherited.</b>&nbsp; Missing and partial functions follow IWK's own Appendix 11 grading (460 available · 44 enhancement · 90 gap) — enforced server-side, reconstructed pre-code-access.</span>
    @else
      <span><b>Viewing the proposed completed system.</b>&nbsp; Functions added or enhanced by the completion programme are marked. Scope per IWK Appendix 11: 90 gaps closed, 44 enhancements delivered.</span>
    @endif
  </div>
  <nav class="sidebar">
    <div class="nav-h">START HERE</div>
    <div class="nav-item {{ request()->routeIs('overview') ? 'active' : '' }}" onclick="location='{{ route('overview') }}'">Demo Overview</div>
    <div class="nav-h">WORKING JOURNEYS</div>
    @foreach ([['customer','Customer & Account 360','CRM'],['receipting','Counter Receipting','CASH'],['billing','Billing Runs','BILL'],['debt','Debt Recovery Workbench','DEBT'],['enquiries','Customer Enquiry Desk','CENQ'],['forecasting','Forecasting Workspace','FCST']] as [$rt,$label,$mod])
      <div class="nav-item {{ request()->routeIs($rt) ? 'active' : '' }}" onclick="location='{{ route($rt) }}'"><span>{{ $label }}</span><span class="cnt">{{ $mod }}</span></div>
    @endforeach
    <div class="nav-h">AI ASSIST <span style="color:var(--new);font-weight:800">◆</span></div>
    @foreach ([['ai.overview','AI Strategy',''],['ai.anomalies','Billing Anomaly Detection','QA'],['ai.triage','Assessment Triage','242'],['ai.urs','URS / Gap Drafter','BA'],['ai.reporting','Ask Your Data','RPT'],['ai.enquiry','Enquiry Copilot','CENQ']] as [$rt,$label,$mod])
      <div class="nav-item {{ request()->routeIs($rt) ? 'active' : '' }}" onclick="location='{{ route($rt) }}'"><span>{{ $label }}</span>@if($mod)<span class="cnt">{{ $mod }}</span>@endif</div>
    @endforeach
    <div class="nav-h">ALL MODULES — 594 PROCESSES</div>
    @foreach (\App\Models\Process::selectRaw("module_code, COUNT(*) t, SUM(coverage='covered') c, SUM(coverage='enhancement') e, SUM(coverage='gap') g")->groupBy('module_code')->orderByRaw("CASE module_code WHEN 'CRM' THEN 1 WHEN 'CENQ' THEN 2 WHEN 'BILL' THEN 3 WHEN 'CASH' THEN 4 WHEN 'DEBT' THEN 5 WHEN 'LEGAL' THEN 6 WHEN 'GL' THEN 7 WHEN 'JOINT' THEN 8 WHEN 'GEN' THEN 9 WHEN 'MEYE' THEN 10 ELSE 11 END")->get() as $m)
      <div class="nav-item {{ request()->is('modules/' . $m->module_code) ? 'active' : '' }}" onclick="location='{{ route('module', $m->module_code) }}'">
        <span>{{ config('modules.names.' . $m->module_code, $m->module_code) }} <span style="color:var(--ink-faint);font-size:11px">({{ $m->t }})</span></span>
        <span class="cbar"><i class="c1" style="width:{{ $m->c / $m->t * 100 }}%"></i><i class="c2" style="width:{{ $m->e / $m->t * 100 }}%"></i><i class="c3" style="width:{{ $m->g / $m->t * 100 }}%"></i></span>
      </div>
    @endforeach
  </nav>
  <main class="main">
    @if (session('ok'))<div class="flash"><span>✓</span><span>{{ session('ok') }}</span></div>@endif
    @if ($errors->any())<div class="flash flash-err"><span>⚠</span><span>{{ $errors->first() }}</span></div>@endif
    @yield('content')
    <div class="stamp">
      <span>[COMPANY NAME] · Prepared for IWK tender — System Assessment, Recovery &amp; Completion of Billing &amp; CRM</span>
      <span>Illustrative prototype reconstructed from RFP v1.9 and Appendix 11 before source-code access (4–5 Aug 2026, RFP §9.3). Not IWK production software. All data fictitious.</span>
    </div>
  </main>
</div>
</body>
</html>
