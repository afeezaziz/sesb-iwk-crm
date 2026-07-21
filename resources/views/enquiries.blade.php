@extends('layout')
@section('content')
<div class="page-h"><div class="crumb">Working journey · Customer Enquiry</div>
  <h1>Customer Enquiry Desk</h1>
  <div class="page-sub">Real workflow: log an enquiry, route it to CEMS, resolve it — with enforced status transitions and SLA dates. IWK grades all 57 CENQ processes available; this journey behaves identically in both versions.</div></div>

<div class="ribbon ribbon-ok"><span>✓</span><span><b>100% available in Version 1</b> by IWK's own grading — no rebuild proposed; carried unchanged by the completion programme. 19 open backlog items remain against this module in Appendix 10 (triaged separately).</span></div>

<div class="grid2">
  <div class="card"><div class="card-h"><h3>Open Enquiries (live)</h3><div class="right"><span class="chip chip-sample">SAMPLE DATA</span></div></div>
    <table class="tbl"><thead><tr><th>Ticket</th><th>Account</th><th>Category</th><th>SLA</th><th>Status</th><th>Action</th></tr></thead><tbody>
      @foreach ($open as $e)
        @php($late = $e->sla_due->isPast())
        <tr><td class="mono">{{ $e->no }}</td><td class="mono">{{ $e->account?->no ?? '—' }}</td><td>{{ $e->category }}</td>
          <td style="{{ $late ? 'color:var(--gap);font-weight:600' : '' }}">{{ $e->sla_due->format('d M') }}{{ $late ? ' ⚠' : '' }}</td>
          <td><span class="status-pill {{ ['open' => 'st-warn', 'with_cems' => 'st-info', 'pending_info' => 'st-mut'][$e->status] }}">{{ str_replace('_', ' ', $e->status) }}</span></td>
          <td style="white-space:nowrap">
            @if ($e->status !== 'with_cems')<form class="inline-form" method="post" action="{{ route('enquiry.transition', $e) }}">@csrf<input type="hidden" name="to" value="with_cems"><button class="btn btn-sm">→ CEMS</button></form>@endif
            <form class="inline-form" method="post" action="{{ route('enquiry.transition', $e) }}">@csrf<input type="hidden" name="to" value="resolved"><button class="btn btn-sm btn-primary">Resolve</button></form>
          </td></tr>
      @endforeach
    </tbody></table></div>

  <div class="card"><div class="card-h"><h3>Log New Enquiry</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
    <div class="card-b">
      <form method="post" action="{{ route('enquiry.store') }}">@csrf
        <div class="fgrid">
          <div class="field"><label>Account No. (optional)</label><input class="ctl mono" name="account_no" placeholder="88-102000-0"></div>
          <div class="field"><label>Channel</label><select class="ctl" name="channel"><option>counter</option><option>call</option><option>portal</option><option>email</option></select></div>
          <div class="field"><label>Category</label><select class="ctl" name="category"><option>Billing dispute</option><option>Refund status</option><option>E-bill enrolment</option><option>Connection request</option><option>Payment not reflected</option></select></div>
          <div class="field"><label>Detail</label><input class="ctl" name="detail" placeholder="Short description"></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Log Enquiry (SLA auto-set +3 days)</button></div>
      </form>
      @if ($resolved->count())
        <div style="margin-top:14px;font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--ink-faint)">RECENTLY RESOLVED</div>
        <table class="tbl"><tbody>
          @foreach ($resolved as $e)<tr><td class="mono">{{ $e->no }}</td><td>{{ $e->category }}</td><td><span class="status-pill st-ok">Resolved</span></td></tr>@endforeach
        </tbody></table>
      @endif
    </div></div>
</div>
@endsection
