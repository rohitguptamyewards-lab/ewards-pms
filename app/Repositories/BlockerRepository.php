<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class BlockerRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('blockers')
            ->leftJoin('features', 'blockers.feature_id', '=', 'features.id')
            ->leftJoin('team_members', 'blockers.team_member_id', '=', 'team_members.id')
            ->leftJoin('team_members as resolver', 'blockers.resolved_by', '=', 'resolver.id')
            ->select(
                'blockers.*',
                'features.title as feature_title',
                'team_members.name as reported_by_name',
                'resolver.name as resolved_by_name'
            )
            ->whereNull('blockers.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('blockers.status', $filters['status']);
        }
        if (!empty($filters['feature_id'])) {
            $query->where('blockers.feature_id', $filters['feature_id']);
        }

        return $query->orderByDesc('blockers.created_at')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('blockers')
            ->leftJoin('features', 'blockers.feature_id', '=', 'features.id')
            ->leftJoin('team_members', 'blockers.team_member_id', '=', 'team_members.id')
            ->leftJoin('team_members as resolver', 'blockers.resolved_by', '=', 'resolver.id')
            ->select(
                'blockers.*',
                'features.title as feature_title',
                'team_members.name as reported_by_name',
                'resolver.name as resolved_by_name'
            )
            ->where('blockers.id', $id)
            ->whereNull('blockers.deleted_at')
            ->first();
    }

    public function findActiveByFeature(int $featureId): array
    {
        return DB::table('blockers')
            ->leftJoin('team_members', 'blockers.team_member_id', '=', 'team_members.id')
            ->select('blockers.*', 'team_members.name as reported_by_name')
            ->where('blockers.feature_id', $featureId)
            ->where('blockers.status', 'active')
            ->whereNull('blockers.deleted_at')
            ->orderByDesc('blockers.created_at')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('blockers')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('blockers')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('blockers')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }
}
