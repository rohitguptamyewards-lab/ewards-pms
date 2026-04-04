<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ActivityLogRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('activity_logs')
            ->leftJoin('team_members', 'activity_logs.team_member_id', '=', 'team_members.id')
            ->leftJoin('features', 'activity_logs.feature_id', '=', 'features.id')
            ->select(
                'activity_logs.*',
                'team_members.name as team_member_name',
                'team_members.role as team_member_role',
                'features.title as feature_title'
            )
            ->whereNull('activity_logs.deleted_at');

        if (!empty($filters['team_member_id'])) {
            $query->where('activity_logs.team_member_id', $filters['team_member_id']);
        }
        if (!empty($filters['feature_id'])) {
            $query->where('activity_logs.feature_id', $filters['feature_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('activity_logs.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('activity_logs.log_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('activity_logs.log_date', '<=', $filters['date_to']);
        }
        if (isset($filters['ai_used'])) {
            $query->where('activity_logs.ai_used', $filters['ai_used']);
        }

        return $query->orderByDesc('activity_logs.log_date')
            ->orderByDesc('activity_logs.created_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('activity_logs')
            ->leftJoin('team_members', 'activity_logs.team_member_id', '=', 'team_members.id')
            ->leftJoin('features', 'activity_logs.feature_id', '=', 'features.id')
            ->select(
                'activity_logs.*',
                'team_members.name as team_member_name',
                'team_members.role as team_member_role',
                'features.title as feature_title'
            )
            ->where('activity_logs.id', $id)
            ->whereNull('activity_logs.deleted_at')
            ->first();
    }

    public function findYesterdayLogs(int $teamMemberId): array
    {
        $yesterday = now()->subDay()->toDateString();

        return DB::table('activity_logs')
            ->where('team_member_id', $teamMemberId)
            ->where('log_date', $yesterday)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('activity_logs')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('activity_logs')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('activity_logs')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }

    public function findByMemberAndDate(int $teamMemberId, string $date): array
    {
        return DB::table('activity_logs')
            ->where('team_member_id', $teamMemberId)
            ->where('log_date', $date)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();
    }
}
