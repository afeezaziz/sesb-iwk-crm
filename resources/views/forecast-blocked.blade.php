@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">Working journey · Forecasting</div>
  <h1>Forecasting Workspace</h1>
  <div class="page-sub">The clearest before/after in the tender: all 11 processes graded gap by IWK — the legacy BRAINS capability was never rebuilt. In v1 this application refuses to compute; the gate is server-side, not cosmetic.</div></div>
<div class="blocked">
  <h3>Module not present in Version 1</h3>
  <p>Every Forecasting process is graded <b>GAP</b> in Appendix 11. The capability existed in legacy BRAINS and was lost in the rebuild. Its URS is the only one in this tender already signed off — making it the most accurately priceable scope in the programme.</p>
  <form method="post" action="{{ route('version', 'v2') }}">@csrf<button class="btn btn-primary">View the completed module →</button></form>
</div>
<div class="card"><div class="card-h"><h3>To be restored — the 11 graded processes</h3></div>
  @foreach ($procs as $p)
    <div class="proc-row" onclick="location='{{ route('process', $p) }}'">
      <span class="mono">{{ $p->legacy }}</span><span class="nm">{{ $p->description }}</span>
      <span class="chip chip-gap">GAP — NOT IN V1</span></div>
  @endforeach
</div>
@endsection
