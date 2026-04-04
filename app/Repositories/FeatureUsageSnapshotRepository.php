<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class FeatureUsageSnapshotRepository
{
    /**
     * Get all usage snapshots for a feature, ordered by date desc.
     *
     * @param int $featureId
     * @return array
     */
    public function findByFeature(int $featureId): array
    {
        return DB::table('feature_usage_snapshots')
            ->where('feature_id', $featureId)
            ->orderByDesc('date')
            ->get()
            ->toArray();
    }

    /**
     * Get the most recent usage snapshot for a feature.
     *
     * @param int $featureId
     * @return stdClass|null
     */
    public function findLatest(int $featureId): ?stdClass
    {
        return DB::table('feature_usage_snapshots')
            ->where('feature_id', $featureId)
            ->orderByDesc('date')
            ->first() ?: null;
    }

    /**
     * Insert or update a usage snapshot based on the unique [feature_id, date] pair.
     *
     * @param array $data
     * @return void
     */
    public function upsert(array $data): void
    {
        $existing = DB::table('feature_usage_snapshots')
            ->where('feature_id', $data['feature_id'])
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            $data['updated_at'] = now();
            DB::table('feature_usage_snapshots')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('feature_usage_snapshots')->insert($data);
        }
    }
}
