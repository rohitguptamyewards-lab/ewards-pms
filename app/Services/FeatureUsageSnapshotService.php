<?php

namespace App\Services;

use App\Repositories\FeatureUsageSnapshotRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureUsageSnapshotService
{
    public function __construct(
        private readonly FeatureUsageSnapshotRepository $snapshotRepository,
    ) {}

    /**
     * Generate daily usage snapshots for all features.
     * In production, this would pull from the eWards analytics/usage API.
     * For now, we create placeholder records that can be populated externally.
     */
    public function generateDailySnapshots(string $date = null): void
    {
        $date = $date ?? now()->toDateString();

        try {
            $features = DB::table('features')
                ->whereNull('deleted_at')
                ->where('status', 'released')
                ->pluck('id');

            foreach ($features as $featureId) {
                $latest = $this->snapshotRepository->findLatest($featureId);

                $this->snapshotRepository->upsert([
                    'feature_id'           => $featureId,
                    'date'                 => $date,
                    'merchants_using_count' => $latest->merchants_using_count ?? 0,
                    'first_used_at'        => $latest->first_used_at ?? null,
                    'last_used_at'         => $latest->last_used_at ?? null,
                    'total_usage_count'    => $latest->total_usage_count ?? 0,
                    'revenue_attributed'   => $latest->revenue_attributed ?? 0,
                    'abandoned_count'      => $latest->abandoned_count ?? 0,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Usage snapshot generation failed', [
                'date'  => $date,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
