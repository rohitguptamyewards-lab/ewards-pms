<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class FeatureSpecVersionRepository
{
    public function findByFeature(int $featureId): array
    {
        return DB::table('feature_spec_versions')
            ->leftJoin('team_members as author', 'feature_spec_versions.author_id', '=', 'author.id')
            ->leftJoin('team_members as ack', 'feature_spec_versions.acknowledged_by', '=', 'ack.id')
            ->select(
                'feature_spec_versions.*',
                'author.name as author_name',
                'ack.name as acknowledged_by_name'
            )
            ->where('feature_spec_versions.feature_id', $featureId)
            ->whereNull('feature_spec_versions.deleted_at')
            ->orderByDesc('feature_spec_versions.version_number')
            ->get()
            ->toArray();
    }

    public function findById(int $id): ?object
    {
        return DB::table('feature_spec_versions')
            ->leftJoin('team_members as author', 'feature_spec_versions.author_id', '=', 'author.id')
            ->leftJoin('team_members as ack', 'feature_spec_versions.acknowledged_by', '=', 'ack.id')
            ->select(
                'feature_spec_versions.*',
                'author.name as author_name',
                'ack.name as acknowledged_by_name'
            )
            ->where('feature_spec_versions.id', $id)
            ->whereNull('feature_spec_versions.deleted_at')
            ->first();
    }

    public function getLatestVersion(int $featureId): ?object
    {
        return DB::table('feature_spec_versions')
            ->where('feature_id', $featureId)
            ->whereNull('deleted_at')
            ->orderByDesc('version_number')
            ->first();
    }

    public function getNextVersionNumber(int $featureId): int
    {
        $max = DB::table('feature_spec_versions')
            ->where('feature_id', $featureId)
            ->whereNull('deleted_at')
            ->max('version_number');

        return ($max ?? 0) + 1;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('feature_spec_versions')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('feature_spec_versions')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('feature_spec_versions')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }
}
