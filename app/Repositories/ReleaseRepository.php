<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReleaseRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('releases')
            ->leftJoin('team_members', 'releases.deployed_by', '=', 'team_members.id')
            ->select(
                'releases.*',
                'team_members.name as deployed_by_name'
            )
            ->whereNull('releases.deleted_at');

        if (!empty($filters['environment'])) {
            $query->where('releases.environment', $filters['environment']);
        }

        return $query->orderByDesc('releases.release_date')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        $release = DB::table('releases')
            ->leftJoin('team_members', 'releases.deployed_by', '=', 'team_members.id')
            ->select(
                'releases.*',
                'team_members.name as deployed_by_name'
            )
            ->where('releases.id', $id)
            ->whereNull('releases.deleted_at')
            ->first();

        if ($release) {
            $release->features = DB::table('release_features')
                ->join('features', 'release_features.feature_id', '=', 'features.id')
                ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
                ->where('release_features.release_id', $id)
                ->select(
                    'features.id',
                    'features.title',
                    'features.status',
                    'features.priority',
                    'modules.name as module_name'
                )
                ->get()
                ->toArray();
        }

        return $release;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('releases')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('releases')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('releases')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }

    public function addFeature(int $releaseId, int $featureId): void
    {
        DB::table('release_features')->insertOrIgnore([
            'release_id' => $releaseId,
            'feature_id' => $featureId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function removeFeature(int $releaseId, int $featureId): bool
    {
        return DB::table('release_features')
            ->where('release_id', $releaseId)
            ->where('feature_id', $featureId)
            ->delete() > 0;
    }
}
