<?php

namespace App\Services;

use App\Repositories\CostRateRepository;
use App\Repositories\FeatureCostSnapshotRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureCostSnapshotService
{
    public function __construct(
        private readonly FeatureCostSnapshotRepository $snapshotRepository,
        private readonly CostRateRepository $costRateRepository,
    ) {}

    /**
     * Generate daily cost snapshots for all features with activity logs.
     * Decoupled: failure must NEVER prevent activity log saving.
     */
    public function generateDailySnapshots(string $date = null): void
    {
        $date = $date ?? now()->toDateString();

        try {
            $features = DB::table('features')
                ->whereNull('deleted_at')
                ->pluck('id');

            foreach ($features as $featureId) {
                $this->generateForFeature($featureId, $date);
            }
        } catch (\Throwable $e) {
            Log::error('Cost snapshot generation failed', [
                'date'  => $date,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generateForFeature(int $featureId, string $date): void
    {
        try {
            $logs = DB::table('activity_logs')
                ->where('feature_id', $featureId)
                ->whereNull('deleted_at')
                ->get();

            $totalCost     = 0;
            $costByPerson  = [];
            $costByType    = [];
            $totalHours    = 0;

            foreach ($logs as $log) {
                $hours = $this->durationToHours($log->duration ?? '');
                $totalHours += $hours;

                $rate = $this->costRateRepository->findActive($log->team_member_id, $log->log_date);
                $hourlyRate = 0;
                if ($rate && $rate->loaded_hourly_rate) {
                    try {
                        $hourlyRate = (float) Crypt::decryptString($rate->loaded_hourly_rate);
                    } catch (\Throwable $e) {
                        $hourlyRate = 0;
                    }
                }

                $cost = round($hours * $hourlyRate, 2);
                $totalCost += $cost;

                $memberId = $log->team_member_id;
                $costByPerson[$memberId] = ($costByPerson[$memberId] ?? 0) + $cost;

                $actType = $log->activity_type ?? 'unknown';
                $costByType[$actType] = ($costByType[$actType] ?? 0) + $cost;
            }

            $estimatedHours = (int) DB::table('feature_assignments')
                ->where('feature_id', $featureId)
                ->whereNull('deleted_at')
                ->sum('estimated_hours');

            $this->snapshotRepository->upsert([
                'feature_id'            => $featureId,
                'total_cost'            => round($totalCost, 2),
                'cost_by_person'        => json_encode($costByPerson),
                'cost_by_activity_type' => json_encode($costByType),
                'estimated_hours'       => $estimatedHours,
                'actual_hours'          => (int) $totalHours,
                'snapshot_date'         => $date,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cost snapshot failed for feature', [
                'feature_id' => $featureId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function durationToHours(string $duration): float
    {
        return match ($duration) {
            '15min'  => 0.25,
            '30min'  => 0.5,
            '1h'     => 1,
            '2h'     => 2,
            '4h'     => 4,
            '8h'     => 8,
            default  => 0,
        };
    }
}
