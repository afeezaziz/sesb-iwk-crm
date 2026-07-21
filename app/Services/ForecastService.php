<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Account;
use App\Models\ForecastRun;

/**
 * Forecasting — computed from actual billed history in this database, plus an
 * adjustment percentage (IWKFCADJCALC), projectable and freezable (IWKFCFRZ).
 */
class ForecastService
{
    /** Monthly billed totals (actuals) from the bills table. */
    public function actuals(): array
    {
        return Bill::where('status', '!=', 'trial')
            ->selectRaw("period, SUM(amount) total")->groupBy('period')->orderBy('period')
            ->get()->map(fn ($r) => ['period' => $r->period, 'total' => (float) $r->total])->all();
    }

    /** Compute a 12-month projection: trailing-average growth + adjustment %. */
    public function compute(float $adjustmentPct = 0.0, string $label = 'FY2027'): ForecastRun
    {
        $actuals = $this->actuals();
        abort_if(count($actuals) < 3, 422, 'Not enough billed history to forecast.');

        $n = count($actuals);
        $growths = [];
        for ($i = max(1, $n - 6); $i < $n; $i++) {
            $prev = $actuals[$i - 1]['total'];
            if ($prev > 0) $growths[] = $actuals[$i]['total'] / $prev - 1;
        }
        $g = count($growths) ? array_sum($growths) / count($growths) : 0.0;

        $last = $actuals[$n - 1];
        [$y, $m] = array_map('intval', explode('-', $last['period']));
        $value = $last['total'];
        $series = array_map(fn ($a) => $a + ['kind' => 'actual'], $actuals);
        for ($i = 1; $i <= 12; $i++) {
            $m++; if ($m > 12) { $m = 1; $y++; }
            $value = $value * (1 + $g) * (1 + $adjustmentPct / 100 / 12);
            $series[] = ['period' => sprintf('%04d-%02d', $y, $m), 'total' => round($value, 2), 'kind' => 'forecast'];
        }

        $run = ForecastRun::firstOrNew(['label' => $label, 'frozen' => false]);
        abort_if(ForecastRun::where('label', $label)->where('frozen', true)->exists(), 422,
            "Forecast $label is frozen — unfreeze is not permitted (IWKFCFRZ locks revenue data).");
        $run->fill([
            'adjustment_pct' => $adjustmentPct,
            'series' => $series,
            'computed_at' => now(),
        ])->save();
        return $run;
    }

    public function freeze(ForecastRun $run): ForecastRun
    {
        abort_if($run->frozen, 422, 'Already frozen.');
        $run->update(['frozen' => true]);
        return $run;
    }

    public function stats(ForecastRun $run): array
    {
        $fc = array_values(array_filter($run->series ?? [], fn ($p) => $p['kind'] === 'forecast'));
        return [
            'accounts'  => Account::where('status', 'active')->count(),
            'projected' => array_sum(array_column($fc, 'total')),
            'adj'       => (float) $run->adjustment_pct,
            'frozen'    => $run->frozen,
        ];
    }
}
