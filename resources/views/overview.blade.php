@extends('layout')
@section('content')
@php($t = ['covered' => 460, 'enh' => 44, 'gap' => 90, 'total' => 594])
<div class="page-h"><h1>What you are looking at</h1>
  <div class="page-sub">A working demonstration of IWK's New Billing System in two states — as inherited, and as it will be once completed. This is a live application: receipts allocate, billing runs post, arrangements generate schedules.</div></div>

<div class="card hero-note"><div class="card-b">
  <p><b>Every one of the 594 processes in IWK's Appendix 11 is represented</b> — browsable module by module from the left navigation. What is missing or partial in the <b>Version 1 — As-Is</b> view is decided by IWK's own grading, not our judgement — and it is enforced in server code, not just labels: in v1 this application will refuse to compute a forecast or post a summary adjustment, exactly because IWK graded those functions gap and enhancement.</p>
  <p>The six <b>working journeys</b> carry real business logic on a live database of fictitious sample data: payment allocation oldest-bill-first, tariff-rated trial and live billing runs, instalment schedule generation, enquiry workflow with status transitions, and a forecast computed from the billed history in this very database, freezable per <span class="mono">IWKFCFRZ</span>.</p>
  <p>Because no bidder receives source-code access before 4–5 Aug 2026 (RFP §9.3), Version 1 screens are honest reconstructions from the RFP and appendix descriptions, to be reconciled during the assessment phase.</p>
</div></div>

<div class="card"><div class="card-h"><h3>System completeness — IWK's own count (Appendix 11)</h3></div>
  <div class="card-b">
    <div class="spine"><i class="s1" style="width:{{ $t['covered'] / $t['total'] * 100 }}%"></i><i class="s2" style="width:{{ $t['enh'] / $t['total'] * 100 }}%"></i><i class="s3" style="width:{{ $t['gap'] / $t['total'] * 100 }}%"></i></div>
    <div class="legend">
      <span><span class="sw" style="background:var(--ok)"></span>460 available in v1</span>
      <span><span class="sw" style="background:var(--enh)"></span>44 require enhancement</span>
      <span><span class="sw" style="background:var(--gap)"></span>90 gaps — to be built</span>
      <span style="margin-left:auto">594 legacy processes graded by IWK</span>
    </div>
</div></div>

<div class="page-h" style="margin-top:22px"><h1 style="font-size:16px">Modules</h1></div>
<div class="mod-grid">
  @foreach ($mods as $code => $m)
    <div class="mod-card" onclick="location='{{ route('module', $code) }}'">
      <div class="code">{{ $code }}</div><h4>{{ config('modules.names.' . $code, $code) }}</h4>
      <p>{{ $m->total }} processes graded by IWK</p>
      <div class="spine" style="height:9px"><i class="s1" style="width:{{ $m->covered / $m->total * 100 }}%"></i><i class="s2" style="width:{{ $m->enh / $m->total * 100 }}%"></i><i class="s3" style="width:{{ $m->gap / $m->total * 100 }}%"></i></div>
      <div style="font-size:11.5px;color:var(--ink-soft)">{{ $m->covered }} available · {{ $m->enh }} enhancement · {{ $m->gap }} gap</div>
    </div>
  @endforeach
</div>
@endsection
