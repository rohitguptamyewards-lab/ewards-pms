<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class IdeaRepository
{
    public function findAll(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('ideas')
            ->leftJoin('team_members', 'ideas.created_by', '=', 'team_members.id')
            ->select('ideas.*', 'team_members.name as creator_name')
            ->whereNull('ideas.deleted_at');

        if (! empty($filters['status'])) {
            $query->where('ideas.status', $filters['status']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('ideas.created_by', $filters['created_by']);
        }

        return $query->orderBy('ideas.created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('ideas')
            ->leftJoin('team_members', 'ideas.created_by', '=', 'team_members.id')
            ->select('ideas.*', 'team_members.name as creator_name')
            ->where('ideas.id', $id)
            ->whereNull('ideas.deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        $data['last_activity_at'] = now();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('ideas')->insertGetId($data);
    }

    public function update(int $id, array $data): int
    {
        $data['last_activity_at'] = now();
        $data['updated_at'] = now();

        return DB::table('ideas')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data);
    }

    /**
     * BR-006: Ideas older than 30 days with no activity get a review reminder.
     */
    public function findStaleForReminder(): \Illuminate\Support\Collection
    {
        return DB::table('ideas')
            ->whereNull('deleted_at')
            ->where('status', 'new')
            ->where('last_activity_at', '<', now()->subDays(30))
            ->where(function ($q) {
                $q->whereNull('review_reminded_at')
                  ->orWhere('review_reminded_at', '<', now()->subDays(30));
            })
            ->get();
    }

    /**
     * BR-006: Ideas older than 90 days with no activity are auto-archived.
     */
    public function findStaleForArchive(): \Illuminate\Support\Collection
    {
        return DB::table('ideas')
            ->whereNull('deleted_at')
            ->whereIn('status', ['new', 'under_review'])
            ->where('last_activity_at', '<', now()->subDays(90))
            ->get();
    }

    public function archiveStale(): int
    {
        return DB::table('ideas')
            ->whereNull('deleted_at')
            ->whereIn('status', ['new', 'under_review'])
            ->where('last_activity_at', '<', now()->subDays(90))
            ->update([
                'status' => 'archived',
                'updated_at' => now(),
            ]);
    }
}
