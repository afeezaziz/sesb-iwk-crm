<div class="card-b">
  <form method="post" action="{{ route('adjustment.store', $account) }}">@csrf
    <div class="fgrid">
      <div class="field"><label>Adjustment Type @if($v1)<span class="chip chip-enh flag">SUMMARY = ENH</span>@endif</label>
        <select class="ctl" name="type"><option value="billing">Billing adjustment</option><option value="summary">Summary adjustment (IWKSUMADJ)</option></select></div>
      <div class="field"><label>Effective Date</label><input class="ctl" type="date" name="effective_date" value="{{ now()->addDays(10)->toDateString() }}"></div>
      <div class="field"><label>Amount (RM, +/-)</label><input class="ctl" type="number" step="0.01" name="amount" placeholder="-12.50"></div>
      <div class="field"><label>Reason</label><input class="ctl" name="reason" placeholder="e.g. billing dispute upheld"></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Raise Adjustment</button></div>
  </form>
  @if ($adjustments->count())
    <table class="tbl" style="margin-top:12px"><thead><tr><th>Ref</th><th>Type</th><th class="num">Amount</th><th>Status</th></tr></thead><tbody>
      @foreach ($adjustments as $a)
        <tr><td class="mono">{{ $a->no }}</td><td>{{ ucfirst($a->type) }}</td><td class="num">RM {{ number_format($a->amount, 2) }}</td>
          <td><span class="status-pill st-warn">{{ ucfirst($a->status) }}</span></td></tr>
      @endforeach
    </tbody></table>
  @endif
</div>
