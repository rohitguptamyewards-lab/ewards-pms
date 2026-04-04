<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SprintRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('sprints')
            ->whereNull('deleted_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('sprint_number')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('sprints')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    public function findByIdWithFeatures(int $id): ?object
    {
        $sprint = $this->findById($id);
        if (!$sprint) {
            return null;
        }

        $sprint->features = DB::table('sprint_features')
            ->join('features', 'sprint_features.feature_id', '=', 'features.id')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->where('sprint_features.sprint_id', $id)
            ->select(
                'sprint_features.*',
                'features.title as feature_title',
                'features.status as feature_status',
                'features.priority as feature_priority',
                'features.estimated_hours',
                'team_members.name as assignee_name',
                'modules.name as module_name'
            )
            ->get()
            ->toArray();

        $sprint->delivered_count = collect($sprint->features)
            ->filter(fn ($f) => $f->feature_status === 'released')
            ->count();

        $sprint->carry_over_count = collect($sprint->features)
            ->filter(fn ($f) => $f->carried_over)
            ->count();

        return $sprint;
    }

    public function getNextSprintNumber(): int
    {
        $max = DB::table('sprints')->max('sprint_number');
        return ($max ?? 0) + 1;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('sprints')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('sprints')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('sprints')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }

    public function addFeature(int $sprintId, array $data): int
    {
        $data['sprint_id']  = $sprintId;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('sprint_features')->insertGetId($data);
    }

    public function removeFeature(int $sprintId, int $featureId): bool
    {
        return DB::table('sprint_features')
            ->where('sprint_id', $sprintId)
            ->where('feature_id', $featureId)
            ->delete() > 0;
    }

    public function getActiveSprint(): ?object
    {
        return DB::table('sprints')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();
    }

    public function calculateVelocity(int $sprintId): float
    {
        $delivered = DB::table('sprint_features')
            ->join('features', 'sprint_features.feature_id', '=', 'features.id')
            ->where('sprint_features.sprint_id', $sprintId)
            ->where('features.status', 'released')
            ->sum('sprint_features.committed_hours');

        return round((float) $delivered, 2);
    }
}
