@extends('layout')
@section('content')
@php($chips = ['covered' => ['chip-ok', 'AVAILABLE IN V1'], 'enhancement' => ['chip-enh', 'ENHANCEMENT REQUIRED'], 'gap' => ['chip-gap', 'GAP — NOT IN V1']])
<div class="page-h"><div class="crumb"><a href="{{ route('overview') }}">Overview</a> / Modules</div>
  <h1>{{ config('modules.names.' . $code, $code) }} <span style="color:var(--ink-faint);font-weight:400;font-size:15px">· {{ $procs->count() }} processes</span></h1>
  <div class="page-sub">IWK grading: {{ $procs->where('coverage', 'covered')->count() }} available,
    {{ $procs->where('coverage', 'enhancement')->count() }} enhancement, {{ $procs->where('coverage', 'gap')->count() }} gap.</div></div>
@if ($code === 'MEYE')
  <div class="ribbon ribbon-enh"><span>⚠</span><span><b>Note:</b> Appendix 11 grades this single process available, but RFP §7 states Month-End is "to develop". The bid sides with the RFP and prices the module as a full build (clarification C-5 filed).</span></div>
@endif
<div class="card">
  @foreach ($procs->groupBy('sub_module') as $sub => $items)
    @if ($procs->pluck('sub_module')->unique()->count() > 1 && $sub)<div class="sub-h">{{ strtoupper($sub) }}</div>@endif
    @foreach ($items as $p)
      <div class="proc-row" onclick="location='{{ route('process', $p) }}'">
        <span class="mono">{{ $p->legacy }}</span><span class="nm">{{ $p->description }}</span>
        <span class="chip {{ $chips[$p->coverage][0] }}">{{ $chips[$p->coverage][1] }}</span>
      </div>
    @endforeach
  @endforeach
</div>
@endsection
