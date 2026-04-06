<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 65 — Velocity forecast recalculator cron.
 * Item 37 — Velocity forecasting with confidence ranges.
 */
class RecalculateVelocityForecast extends Command
{
    protected $signature   = 'pms:recalculate-velocity-forecast';
    protected $description = 'Recalculate team velocity forecasts based on completed sprint data';

    /** Number of past sprints to use for rolling average */
    private const LOOKBACK_SPRINTS = 6;

    public function handle(): int
    {
        $forecasts = 0;

        // Get all active/completed sprints from last N sprints
        $completedSprints = DB::table('sprints')
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->orderByDesc('end_date')
            ->limit(self::LOOKBACK_SPRINTS * 3) // get enough data
            ->get();

        if ($completedSprints->isEmpty()) {
            $this->info('No completed sprints found — skipping velocity calculation.');
            return self::SUCCESS;
        }

        // Calculate velocity per sprint (features completed)
        $velocities = [];
        foreach ($completedSprints as $sprint) {
            $featuresCompleted = DB::table('sprint_features')
                ->join('features', 'sprint_features.feature_id', '=', 'features.id')
                ->where('sprint_features.sprint_id', $sprint->id)
                // sprint_features has no softDeletes
                ->whereNull('features.deleted_at')
                ->where('features.status', 'released')
                ->count();

            $hoursCompleted = DB::table('sprint_features')
                ->join('features', 'sprint_features.feature_id', '=', 'features.id')
                ->where('sprint_features.sprint_id', $sprint->id)
                // sprint_features has no softDeletes
                ->whereNull('features.deleted_at')
                ->sum('features.estimated_hours');

            $velocities[] = [
                'sprint_id'    => $sprint->id,
                'features'     => $featuresCompleted,
                'hours'        => (float) ($hoursCompleted ?? 0),
            ];
        }

        if (empty($velocities)) {
            $this->info('No velocity data found.');
            return self::SUCCESS;
        }

        // Use last N sprints for rolling average
        $recentVelocities = array_slice($velocities, 0, self::LOOKBACK_SPRINTS);
        $featureCounts    = array_column($recentVelocities, 'features');
        $hoursCounts      = array_column($recentVelocities, 'hours');

        $avgFeatures  = count($featureCounts) > 0 ? array_sum($featureCounts) / count($featureCounts) : 0;
        $avgHours     = count($hoursCounts) > 0 ? array_sum($hoursCounts) / count($hoursCounts) : 0;

        // Standard deviation for confidence range
        $featureStdDev = $this->stdDev($featureCounts);
        $hoursStdDev   = $this->stdDev($hoursCounts);

        // 80% confidence range: avg ± 1.28 * stddev
        $featureLow  = max(0, round($avgFeatures - 1.28 * $featureStdDev, 1));
        $featureHigh = round($avgFeatures + 1.28 * $featureStdDev, 1);
        $hoursLow    = max(0, round($avgHours - 1.28 * $hoursStdDev, 1));
        $hoursHigh   = round($avgHours + 1.28 * $hoursStdDev, 1);

        // Store forecast in Laravel cache (30-day TTL; recalculated weekly)
        $forecast = [
            'avg_features_per_sprint' => round($avgFeatures, 2),
            'avg_hours_per_sprint'    => round($avgHours, 2),
            'feature_low_80'          => $featureLow,
            'feature_high_80'         => $featureHigh,
            'hours_low_80'            => $hoursLow,
            'hours_high_80'           => $hoursHigh,
            'sprints_sampled'         => count($recentVelocities),
            'calculated_at'           => now()->toIso8601String(),
        ];

        Cache::put('pms.velocity_forecast', $forecast, now()->addDays(30));

        $forecasts++;
        $this->info("Velocity forecast updated. Avg: {$avgFeatures} features/sprint ({$featureLow}–{$featureHigh} @ 80% confidence).");
        Log::info("pms:recalculate-velocity-forecast completed.", compact('avgFeatures', 'avgHours', 'featureLow', 'featureHigh'));

        return self::SUCCESS;
    }

    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $mean     = array_sum($values) / $n;
        $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $values)) / ($n - 1);
        return sqrt($variance);
    }
}
