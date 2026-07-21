@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">Working journey · Forecasting</div>
  <h1>Forecasting Workspace</h1>
  <div class="page-sub">Computed from the billed history in this database — trailing-average growth plus your adjustment percentage (<span class="mono">IWKFCADJCALC</span>), projectable and freezable (<span class="mono">IWKFCFRZ</span>).</div></div>

<div class="ribbon ribbon-new"><span>★</span><span><b>Entire module delivered by the completion programme</b> — 11 processes rebuilt from gap, on the only signed-off URS in the tender.</span></div>

@if (! $run)
  <div class="card"><div class="card-b">
    <p style="margin-bottom:12px">No forecast computed yet. Run one from the live billed history:</p>
    <form method="post" action="{{ route('forecast.compute') }}">@csrf
      <div class="fgrid" style="max-width:420px">
        <div class="field"><label>Adjustment % (−10 to +10)</label><input class="ctl" type="number" step="0.1" name="adjustment_pct" value="2.4"></div>
      </div>
      <div class="form-actions"><button class="btn btn-primary">Compute Forecast</button></div>
    </form>
  </div></div>
@else
  <div class="tiles">
    <div class="tile"><div class="k">ACCOUNTS IN FORECAST</div><div class="v">{{ number_format($stats['accounts']) }}</div><div class="s">active accounts · live count</div></div>
    <div class="tile"><div class="k">NEXT-12-MO PROJECTED</div><div class="v">RM {{ number_format($stats['projected'] / 1000, 1) }}k</div><div class="s">from billed history · sample scale</div></div>
    <div class="tile"><div class="k">ADJUSTMENT FACTOR</div><div class="v">{{ $stats['adj'] >= 0 ? '+' : '' }}{{ $stats['adj'] }}%</div><div class="s">IWKFCADJCALC</div></div>
    <div class="tile {{ $stats['frozen'] ? 't-new' : 't-enh' }}"><div class="k">FORECAST STATUS</div><div class="v">{{ $stats['frozen'] ? 'Frozen' : 'Draft' }}</div>
      <div class="s">{{ $stats['frozen'] ? 'revenue data locked (IWKFCFRZ)' : 'freeze to lock revenue data' }}</div></div>
  </div>

  @php
    $series = $run->series ?? [];
    $vals = array_column($series, 'total');
    $W = 940; $H = 240; $P = ['l' => 56, 'r' => 84, 't' => 14, 'b' => 26];
    $n = count($series);
    $ymin = floor(min($vals) * 0.97); $ymax = ceil(max($vals) * 1.03);
    $x = fn ($i) => $P['l'] + $i * ($W - $P['l'] - $P['r']) / max(1, $n - 1);
    $y = fn ($v) => $P['t'] + ($ymax - $v) / max(1, $ymax - $ymin) * ($H - $P['t'] - $P['b']);
    $actualPts = ''; $fcPts = ''; $fcStart = null;
    foreach ($series as $i => $pt) {
      $c = number_format($x($i), 1) . ',' . number_format($y($pt['total']), 1) . ' ';
      if ($pt['kind'] === 'actual') { $actualPts .= $c; $lastActual = $i; }
      else { if ($fcStart === null) { $fcStart = $i; $fcPts .= number_format($x($lastActual), 1) . ',' . number_format($y($series[$lastActual]['total']), 1) . ' '; } $fcPts .= $c; }
    }
  @endphp
  <div class="card"><div class="card-h"><h3>Billed revenue — actual &amp; forecast (RM / month, live data)</h3>
      <div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
    <div class="card-b"><svg viewBox="0 0 {{ $W }} {{ $H }}" style="width:100%;display:block">
      @for ($gv = $ymin; $gv <= $ymax; $gv += max(1, round(($ymax - $ymin) / 4)))
        <line x1="{{ $P['l'] }}" x2="{{ $W - $P['r'] }}" y1="{{ $y($gv) }}" y2="{{ $y($gv) }}" stroke="#EAEFF3"/>
        <text x="{{ $P['l'] - 8 }}" y="{{ $y($gv) + 4 }}" text-anchor="end" font-size="10.5" fill="#8397A4">{{ number_format($gv / 1000, 1) }}k</text>
      @endfor
      @foreach ($series as $i => $pt)
        @if ($i % 3 === 0)<text x="{{ $x($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10.5" fill="#8397A4">{{ $pt['period'] }}</text>@endif
      @endforeach
      @if ($fcStart)
        <line x1="{{ $x($fcStart - 1) }}" x2="{{ $x($fcStart - 1) }}" y1="{{ $P['t'] }}" y2="{{ $H - $P['b'] }}" stroke="#D8E0E6" stroke-dasharray="3 3"/>
        <text x="{{ $x($fcStart - 1) + 5 }}" y="{{ $P['t'] + 10 }}" font-size="10.5" fill="#8397A4">forecast →</text>
      @endif
      <polyline points="{{ trim($actualPts) }}" fill="none" stroke="#2456A6" stroke-width="2"/>
      <polyline points="{{ trim($fcPts) }}" fill="none" stroke="#2456A6" stroke-width="2" stroke-dasharray="6 5"/>
      @php($last = end($series))
      <circle cx="{{ $x($n - 1) }}" cy="{{ $y($last['total']) }}" r="3.5" fill="#2456A6"/>
      <text x="{{ $x($n - 1) + 8 }}" y="{{ $y($last['total']) + 4 }}" font-size="11.5" font-weight="600" fill="#2456A6">RM {{ number_format($last['total'] / 1000, 1) }}k</text>
    </svg></div></div>

  <div class="grid2">
    <div class="card"><div class="card-h"><h3>Recompute</h3></div>
      <div class="card-b">
        @if ($run->frozen)
          <div class="ribbon ribbon-new" style="margin-bottom:0"><span>🔒</span><span><b>{{ $run->label }} is frozen.</b> Revenue data locked per <span class="mono">IWKFCFRZ</span> — recomputation is refused by the server, matching the legacy control.</span></div>
        @else
          <form method="post" action="{{ route('forecast.compute') }}">@csrf
            <div class="fgrid">
              <div class="field"><label>Adjustment % (−10 to +10)</label><input class="ctl" type="number" step="0.1" name="adjustment_pct" value="{{ $run->adjustment_pct }}"></div>
              <div class="field"><label>Computed</label><div class="ctl">{{ $run->computed_at->format('d M Y H:i') }}</div></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Recompute</button>
              <form></form></div>
          </form>
          <form method="post" action="{{ route('forecast.freeze', $run) }}" style="margin-top:8px">@csrf
            <button class="btn">Freeze Forecast &amp; Lock Revenue Data (IWKFCFRZ)</button></form>
        @endif
      </div></div>
    <div class="card"><div class="card-h"><h3>Delivered capabilities — all 11 processes</h3></div>
      @foreach ($procs as $p)
        <div class="proc-row" onclick="location='{{ route('process', $p) }}'">
          <span class="mono">{{ $p->legacy }}</span><span class="nm">{{ $p->description }}</span>
          <span class="chip chip-new">DELIVERED</span></div>
      @endforeach
    </div>
  </div>
@endif
@endsection
