<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class WorkLogRepository
{
    /**
     * Get work logs for a user with optional filters.
     *
     * @param int   $userId
     * @param array $filters Supported: project_id, from, to
     * @return array
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $query = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.user_id', $userId)
            ->whereNull('work_logs.deleted_at');

        if (!empty($filters['project_id'])) {
            $query->where('work_logs.project_id', $filters['project_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('work_logs.log_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('work_logs.log_date', '<=', $filters['to']);
        }

        return $query->orderByDesc('work_logs.log_date')->get()->toArray();
    }

    /**
     * Get paginated work logs with optional filters and joined names.
     *
     * @param array $filters Supported: user_id, project_id, task_id, date_from, date_to
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->leftJoin('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->select(
                'work_logs.*',
                'projects.name as project_name',
                'tasks.title as task_title',
                'team_members.name as user_name'
            )
            ->whereNull('work_logs.deleted_at');

        if (!empty($filters['user_id'])) {
            $query->where('work_logs.user_id', $filters['user_id']);
        }
        if (!empty($filters['project_id'])) {
            $query->where('work_logs.project_id', $filters['project_id']);
        }
        if (!empty($filters['task_id'])) {
            $query->where('work_logs.task_id', $filters['task_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('work_logs.log_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('work_logs.log_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('work_logs.log_date')->paginate($perPage);
    }

    /**
     * Get all work logs for a project.
     *
     * @param int $projectId
     * @return array
     */
    public function findByProject(int $projectId): array
    {
        return DB::table('work_logs')
            ->leftJoin('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'team_members.name as user_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.project_id', $projectId)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.log_date')
            ->get()
            ->toArray();
    }

    /**
     * Get all work logs for a task.
     *
     * @param int $taskId
     * @return array
     */
    public function findByTask(int $taskId): array
    {
        return DB::table('work_logs')
            ->leftJoin('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->select(
                'work_logs.*',
                'team_members.name as user_name'
            )
            ->where('work_logs.task_id', $taskId)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.log_date')
            ->get()
            ->toArray();
    }

    /**
     * Get the last work log's end_time for a user on a given date.
     */
    public function getLastEndTime(int $userId, string $date): ?string
    {
        $row = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', $date)
            ->whereNotNull('end_time')
            ->whereNull('deleted_at')
            ->orderByDesc('end_time')
            ->select('end_time')
            ->first();

        return $row?->end_time;
    }

    /**
     * Insert a new work log and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('work_logs')->insertGetId($data);
    }

    /**
     * Update a work log by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('work_logs')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Delete a work log by ID (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return DB::table('work_logs')
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Sum hours spent by a user in a date range.
     *
     * @param int    $userId
     * @param string $from
     * @param string $to
     * @return float
     */
    public function sumHoursForUser(int $userId, string $from, string $to): float
    {
        $result = DB::table('work_logs')
            ->where('user_id', $userId)
            ->whereBetween('log_date', [$from, $to])
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        return round((float) $result, 2);
    }

    /**
     * Sum hours spent across all logs for a project.
     *
     * @param int $projectId
     * @return float
     */
    public function sumHoursForProject(int $projectId): float
    {
        $result = DB::table('work_logs')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        return round((float) $result, 2);
    }
}
