<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class FeatureCostSnapshotRepository
{
    /**
     * Get all snapshots for a feature, ordered by snapshot_date desc.
     *
     * @param int $featureId
     * @return array
     */
    public function findByFeature(int $featureId): array
    {
        return DB::table('feature_cost_snapshots')
            ->where('feature_id', $featureId)
            ->orderByDesc('snapshot_date')
            ->get()
            ->toArray();
    }

    /**
     * Get the most recent snapshot for a feature.
     *
     * @param int $featureId
     * @return stdClass|null
     */
    public function findLatest(int $featureId): ?stdClass
    {
        return DB::table('feature_cost_snapshots')
            ->where('feature_id', $featureId)
            ->orderByDesc('snapshot_date')
            ->first() ?: null;
    }

    /**
     * Get all snapshots for a given date.
     *
     * @param string $date
     * @return array
     */
    public function findByDate(string $date): array
    {
        return DB::table('feature_cost_snapshots')
            ->where('snapshot_date', $date)
            ->get()
            ->toArray();
    }

    /**
     * Insert or update a snapshot based on the unique [feature_id, snapshot_date] pair.
     *
     * @param array $data
     * @return void
     */
    public function upsert(array $data): void
    {
        $existing = DB::table('feature_cost_snapshots')
            ->where('feature_id', $data['feature_id'])
            ->where('snapshot_date', $data['snapshot_date'])
            ->first();

        if ($existing) {
            $data['updated_at'] = now();
            DB::table('feature_cost_snapshots')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('feature_cost_snapshots')->insert($data);
        }
    }

    /**
     * Returns total_cost sum per feature for a given set of feature IDs.
     * Used for the cost dashboard.
     *
     * @param array $featureIds
     * @return array  Each item: feature_id, total_cost_sum
     */
    public function getAggregated(array $featureIds): array
    {
        if (empty($featureIds)) {
            return [];
        }

        return DB::table('feature_cost_snapshots')
            ->select(
                'feature_id',
                DB::raw('SUM(total_cost) as total_cost_sum')
            )
            ->whereIn('feature_id', $featureIds)
            ->groupBy('feature_id')
            ->get()
            ->toArray();
    }
}
