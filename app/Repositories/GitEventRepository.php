<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class GitEventRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('git_events')
            ->leftJoin('repositories', 'git_events.repository_id', '=', 'repositories.id')
            ->leftJoin('features', 'git_events.feature_id', '=', 'features.id')
            ->leftJoin('team_members', 'git_events.team_member_id', '=', 'team_members.id')
            ->select(
                'git_events.*',
                'repositories.name as repository_name',
                'features.title as feature_title',
                'team_members.name as team_member_name'
            );

        if (!empty($filters['repository_id'])) {
            $query->where('git_events.repository_id', $filters['repository_id']);
        }
        if (!empty($filters['event_type'])) {
            $query->where('git_events.event_type', $filters['event_type']);
        }
        if (!empty($filters['feature_id'])) {
            $query->where('git_events.feature_id', $filters['feature_id']);
        }

        return $query->orderByDesc('git_events.created_at')->paginate($perPage);
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('git_events')->insertGetId($data);
    }

    public function markProcessed(int $id): bool
    {
        return DB::table('git_events')
            ->where('id', $id)
            ->update(['processed_at' => now()]) > 0;
    }

    public function findUnprocessed(): array
    {
        return DB::table('git_events')
            ->whereNull('processed_at')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
}
