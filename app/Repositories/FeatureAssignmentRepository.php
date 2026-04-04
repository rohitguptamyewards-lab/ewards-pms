<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class FeatureAssignmentRepository
{
    public function findByFeature(int $featureId): array
    {
        return DB::table('feature_assignments')
            ->join('team_members', 'feature_assignments.team_member_id', '=', 'team_members.id')
            ->leftJoin('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->select(
                'feature_assignments.*',
                'team_members.name as member_name',
                'features.title as feature_title'
            )
            ->where('feature_assignments.feature_id', $featureId)
            ->whereNull('feature_assignments.deleted_at')
            ->orderByDesc('feature_assignments.assigned_at')
            ->get()
            ->toArray();
    }

    public function findByMember(int $memberId): array
    {
        return DB::table('feature_assignments')
            ->join('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->join('team_members', 'feature_assignments.team_member_id', '=', 'team_members.id')
            ->select(
                'feature_assignments.*',
                'features.title as feature_title',
                'team_members.name as member_name'
            )
            ->where('feature_assignments.team_member_id', $memberId)
            ->whereNull('feature_assignments.deleted_at')
            ->orderByDesc('feature_assignments.assigned_at')
            ->get()
            ->toArray();
    }

    public function findActive(int $memberId): array
    {
        return DB::table('feature_assignments')
            ->join('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->select(
                'feature_assignments.*',
                'features.title as feature_title'
            )
            ->where('feature_assignments.team_member_id', $memberId)
            ->whereIn('feature_assignments.state', ['assigned', 'in_progress'])
            ->whereNull('feature_assignments.deleted_at')
            ->get()
            ->toArray();
    }

    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('feature_assignments')
            ->join('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->join('team_members', 'feature_assignments.team_member_id', '=', 'team_members.id')
            ->select(
                'feature_assignments.*',
                'features.title as feature_title',
                'team_members.name as member_name'
            )
            ->whereNull('feature_assignments.deleted_at');

        if (!empty($filters['feature_id'])) {
            $query->where('feature_assignments.feature_id', $filters['feature_id']);
        }
        if (!empty($filters['team_member_id'])) {
            $query->where('feature_assignments.team_member_id', $filters['team_member_id']);
        }
        if (!empty($filters['state'])) {
            $query->where('feature_assignments.state', $filters['state']);
        }
        if (!empty($filters['role'])) {
            $query->where('feature_assignments.role', $filters['role']);
        }

        return $query->orderByDesc('feature_assignments.assigned_at')->paginate($perPage);
    }

    public function findById(int $id): ?stdClass
    {
        return DB::table('feature_assignments')
            ->join('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->join('team_members', 'feature_assignments.team_member_id', '=', 'team_members.id')
            ->select(
                'feature_assignments.*',
                'features.title as feature_title',
                'team_members.name as member_name'
            )
            ->where('feature_assignments.id', $id)
            ->whereNull('feature_assignments.deleted_at')
            ->first() ?: null;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('feature_assignments')->insertGetId($data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = now();

        return DB::table('feature_assignments')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data);
    }

    public function delete(int $id): int
    {
        return DB::table('feature_assignments')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function sumAllocatedHours(int $memberId): int
    {
        return (int) DB::table('feature_assignments')
            ->where('team_member_id', $memberId)
            ->whereIn('state', ['assigned', 'in_progress'])
            ->whereNull('deleted_at')
            ->sum('estimated_hours');
    }

    public function updateActualHours(int $id, int $hours): int
    {
        return DB::table('feature_assignments')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['actual_hours' => $hours, 'updated_at' => now()]);
    }
}
