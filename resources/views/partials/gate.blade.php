@php
    /** Gated feature panel: renders $slot content when the feature is on,
     *  a blocked panel when off (gap in v1), with grade chips + appendix anchors.
     *  Usage: @include('partials.gate', ['key' => 'refunds', 'title' => '...', 'body' => view(...)]) */
    $f = \App\Support\Nbs::feature($key);
    $chip = \App\Support\Nbs::chip($key);
    $on = \App\Support\Nbs::on($key);
    $chips = ['ok' => ['chip-ok', 'AVAILABLE'], 'enh' => ['chip-enh', 'PARTIAL IN V1'],
              'gap' => ['chip-gap', 'NOT IN V1'], 'new' => ['chip-new', 'DELIVERED BY COMPLETION'],
              'enh2' => ['chip-enh2', 'ENHANCED']];
@endphp
<div class="card" @if($chip==='new') style="border-color:var(--new-line)" @endif>
  <div class="card-h"><h3>{{ $title ?? $f['label'] }}</h3>
    <div class="right"><span class="chip {{ $chips[$chip][0] }}">{{ $chips[$chip][1] }}</span></div></div>
  @if ($on)
    {!! $body !!}
    <div class="card-b" style="padding-top:0;font-size:12px;color:var(--ink-soft)">
      @if ($chip === 'new') Rebuilt from gap: @elseif ($chip === 'enh2') Enhancement delivered: @elseif ($chip === 'enh') Graded enhancement by IWK — parts degraded: @else Appendix 11: @endif
      <span class="mono">{{ \App\Support\Nbs::processList($key) }}</span>
    </div>
  @else
    <div class="card-b"><div class="panel-blocked">Function not present in Version 1 — graded <b>gap</b> by IWK (Appendix 11):<br>
      <span class="mono">{{ \App\Support\Nbs::processList($key) }}</span></div></div>
  @endif
</div>
