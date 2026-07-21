<div class="card"><div class="card-h"><h3>Add Record</h3><div class="right"><span class="chip chip-sample">LIVE — WRITES TO DB</span></div></div>
  <div class="card-b"><form method="post" action="{{ route('process.store', $p) }}">@csrf
    <div class="fgrid">
      <div class="field"><label>Account No.</label><input class="ctl" name="account_no" placeholder="88-…"></div>
      <div class="field"><label>Amount (RM)</label><input class="ctl" type="number" step="0.01" name="amount"></div>
      <div class="field"><label>Effective Date</label><input class="ctl" type="date" name="effective_date" value="{{ now()->toDateString() }}"></div>
      <div class="field"><label>Remarks</label><input class="ctl" name="remarks"></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Save</button></div>
  </form></div>
</div>
