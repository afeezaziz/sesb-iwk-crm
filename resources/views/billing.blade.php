@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">Working journey · Billing Engine</div>
  <h1>Billing Runs</h1>
  <div class="page-sub">A working (simplified) rating engine: every active account is rated from the tariff table for a period. Trial produces a register without posting; live posts real bills that then appear on accounts, receipting and debt screens. The full BRAINS rating rules are assessment scope — IWK grades the Billing Engine 33 of 63 available.</div></div>

<div class="ribbon ribbon-enh"><span>⚠</span><span><b>Deliberately simplified.</b> This run rates flat sample tariffs. The real engine's 30 unfinished processes (17 enhancement, 13 gap by IWK's grading — trial register, billing register details, IST reversals among them) are exactly what the completion programme prices after code access.</span></div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>Run Billing</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
    <div class="card-b">
      <form method="post" action="{{ route('billing.run') }}">@csrf
        <div class="fgrid">
          <div class="field"><label>Billing Period</label><input class="ctl" name="period" value="{{ now()->addMonth()->format('Y-m') }}"></div>
          <div class="field"><label>Mode</label><select class="ctl" name="mode"><option value="trial">Trial — register only</option><option value="live">Live — post bills</option></select></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Run</button></div>
      </form>
    </div>
    <div class="card-h" style="border-top:1px solid var(--line-soft)"><h3>Run History (live)</h3></div>
    <table class="tbl"><thead><tr><th>Period</th><th>Mode</th><th class="num">Accounts</th><th class="num">Total</th><th>When</th></tr></thead><tbody>
      @forelse ($runs as $r)
        <tr><td>{{ $r->period }}</td><td><span class="status-pill {{ $r->mode === 'live' ? 'st-ok' : 'st-info' }}">{{ ucfirst($r->mode) }}</span></td>
          <td class="num">{{ number_format($r->accounts_billed) }}</td><td class="num">RM {{ number_format($r->total_amount, 2) }}</td>
          <td>{{ $r->ran_at->format('d M H:i') }}</td></tr>
      @empty
        <tr><td colspan="5" style="color:var(--ink-faint)">No runs yet — run a trial.</td></tr>
      @endforelse
    </tbody></table></div>

  <div class="card"><div class="card-h"><h3>Trial Register Preview — {{ $period }}</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
    <table class="tbl"><thead><tr><th>Account</th><th>Customer</th><th>Category</th><th class="num">Rated</th></tr></thead><tbody>
      @foreach ($register as $row)
        <tr><td class="mono">{{ $row['account'] }}</td><td>{{ $row['customer'] }}</td><td>{{ $row['category'] }}</td><td class="num">RM {{ number_format($row['amount'], 2) }}</td></tr>
      @endforeach
    </tbody></table></div>
</div>
@endsection
