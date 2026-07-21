@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">AI Assist · Billing QA</div>
  <h1>Billing Anomaly Detection</h1>
  <div class="page-sub">Scans every issued bill and adjustment for the errors a retrospective-recalculation engine produces — before they reach the customer. Statistical outliers (robust z-score per rate category) + billing domain rules. <span class="rfp-ref">RFP §2 Adjustment Modules · Risk R-01</span></div></div>

<div class="ribbon {{ $aiLive ? 'ribbon-ok' : 'ribbon-new' }}"><span>{{ $aiLive ? '●' : '○' }}</span><span>
  @if($aiLive)<b>LLM endpoint live</b> — click “Explain” on any flag for a generated officer-friendly explanation.
  @else <b>Detection runs with no model</b> (statistics + rules). The optional LLM layer only adds plain-English explanations; set an on-prem endpoint to enable “Explain”.@endif
  &nbsp;Governance: <b>AI proposes, a named billing officer confirms or dismisses</b> — nothing is auto-actioned.</span></div>

<div class="tiles">
  <div class="tile t-gap"><div class="k">CRITICAL</div><div class="v">{{ $summary['critical'] }}</div><div class="s">need review now</div></div>
  <div class="tile t-enh"><div class="k">HIGH</div><div class="v">{{ $summary['high'] }}</div><div class="s">check before posting</div></div>
  <div class="tile"><div class="k">SCANNED</div><div class="v">{{ number_format($summary['scanned_bills']) }}</div><div class="s">bills + {{ $summary['scanned_adjustments'] }} adjustments</div></div>
  <div class="tile t-ok"><div class="k">REVIEWED</div><div class="v">{{ $summary['confirmed'] + $summary['dismissed'] }}</div><div class="s">{{ $summary['confirmed'] }} confirmed · {{ $summary['dismissed'] }} dismissed</div></div>
</div>

<div class="card"><div class="card-h"><h3>Flagged items — most severe first</h3><div class="right"><a class="btn btn-sm" href="{{ route('ai.anomalies.export') }}">↓ Export CSV</a><span class="chip chip-sample">SAMPLE DATA</span></div></div>
  <div class="card-b">
    @forelse ($flags as $f)
      @php($key = "{$f['subject_type']}:{$f['subject_id']}:{$f['rule']}")
      <div class="flag-row {{ $f['review'] }}">
        <div class="flag-head">
          <span class="sev sev-{{ $f['severity'] }}">{{ strtoupper($f['severity']) }}</span>
          <span class="mono" style="font-size:12px;color:var(--ink-soft)">{{ str_replace('_',' ',$f['rule']) }}</span>
          <span style="font-size:11.5px;color:var(--ink-faint)">· {{ ucfirst($f['subject_type']) }} #{{ $f['subject_id'] }}</span>
          @if($f['review'])<span class="chip {{ $f['review']==='confirmed'?'chip-gap':'chip-sample' }}" style="margin-left:auto">{{ strtoupper($f['review']) }}</span>@endif
        </div>
        <div class="flag-reason">{{ $f['reason'] }}</div>
        @if(session('explain_key') === $key)
          <div class="ribbon ribbon-new" style="margin:6px 0 0"><span>✦</span><span>{{ session('explain_text') }}</span></div>
        @endif
        <div class="flag-actions">
          <form method="post" action="{{ route('ai.anomalies.review') }}" class="inline-form">@csrf
            <input type="hidden" name="subject_type" value="{{ $f['subject_type'] }}"><input type="hidden" name="subject_id" value="{{ $f['subject_id'] }}"><input type="hidden" name="rule" value="{{ $f['rule'] }}">
            <button name="verdict" value="confirmed" class="btn btn-sm btn-primary">Confirm — needs correction</button>
            <button name="verdict" value="dismissed" class="btn btn-sm">Dismiss — false positive</button>
          </form>
          @if($aiLive)
          <form method="post" action="{{ route('ai.anomalies.explain') }}" class="inline-form">@csrf
            <input type="hidden" name="type" value="{{ $f['subject_type'] }}"><input type="hidden" name="id" value="{{ $f['subject_id'] }}"><input type="hidden" name="rule" value="{{ $f['rule'] }}">
            <button class="btn btn-sm">✦ Explain</button>
          </form>
          @endif
        </div>
      </div>
    @empty
      <div class="panel-blocked" style="border-color:var(--ok-line);background:var(--ok-bg);color:var(--ok)">No anomalies detected in the current dataset.</div>
    @endforelse
  </div></div>
@endsection
