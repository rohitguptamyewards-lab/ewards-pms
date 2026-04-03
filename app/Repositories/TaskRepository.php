<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class TaskRepository
{
    /**
     * Get tasks for a specific project with optional filters.
     *
     * @param int   $projectId
     * @param array $filters Supported: status, assigned_to, priority
     * @return array
     */
    public function findByProject(int $projectId, array $filters = []): array
    {
        $query = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->select(
                'tasks.*',
                'team_members.name as assignee_name'
            )
            ->where('tasks.project_id', $projectId)
            ->whereNull('tasks.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('tasks.status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('tasks.assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['priority'])) {
            $query->where('tasks.priority', $filters['priority']);
        }

        return $query->orderByDesc('tasks.created_at')->get()->toArray();
    }

    /**
     * Find a single task by ID with project and assignee names.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        $row = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->select(
                'tasks.*',
                'projects.name as project_name',
                'team_members.name as assignee_name'
            )
            ->where('tasks.id', $id)
            ->whereNull('tasks.deleted_at')
            ->first();

        return $row ?: null;
    }

    /**
     * Insert a new task and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('tasks')->insertGetId($data);
    }

    /**
     * Update a task by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('tasks')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Get task counts grouped by status for a given project.
     *
     * @param int $projectId
     * @return array
     */
    public function countByStatus(int $projectId): array
    {
        return DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->toArray();
    }
}
