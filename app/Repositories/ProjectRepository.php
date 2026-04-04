<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class ProjectRepository
{
    /**
     * Get paginated list of projects with owner name, task count, and progress.
     *
     * @param array $filters Supported: status, owner_id
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('projects')
            ->leftJoin('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->select(
                'projects.*',
                'team_members.name as owner_name',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as task_count'),
                DB::raw('(SELECT COALESCE(ROUND(SUM(CASE WHEN tasks.status = \'done\' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as progress'),
                DB::raw('(SELECT COALESCE(SUM(hours_spent), 0) FROM work_logs WHERE work_logs.project_id = projects.id AND work_logs.deleted_at IS NULL) as total_effort')
            )
            ->whereNull('projects.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('projects.status', $filters['status']);
        }

        if (!empty($filters['owner_id'])) {
            $query->where('projects.owner_id', $filters['owner_id']);
        }

        return $query->orderByDesc('projects.created_at')->paginate($perPage);
    }

    /**
     * Find a single project by ID with owner, task counts, and total effort.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        $project = DB::table('projects')
            ->leftJoin('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->select(
                'projects.*',
                'team_members.name as owner_name',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as task_count'),
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.status = \'done\' AND tasks.deleted_at IS NULL) as done_task_count'),
                DB::raw('(SELECT COALESCE(SUM(hours_spent), 0) FROM work_logs WHERE work_logs.project_id = projects.id AND work_logs.deleted_at IS NULL) as total_effort')
            )
            ->where('projects.id', $id)
            ->whereNull('projects.deleted_at')
            ->first();

        if (!$project) {
            return null;
        }

        $project->members = $this->getMembers($id);

        return $project;
    }

    /**
     * Get projects where a user is an owner or member.
     *
     * @param int $userId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findByMember(int $userId)
    {
        $query = DB::table('projects')
            ->leftJoin('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->leftJoin('project_members', 'projects.id', '=', 'project_members.project_id')
            ->select(
                'projects.*',
                'team_members.name as owner_name',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as task_count'),
                DB::raw('(SELECT COALESCE(ROUND(SUM(CASE WHEN tasks.status = \'done\' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as progress')
            )
            ->whereNull('projects.deleted_at')
            ->where(function ($q) use ($userId) {
                $q->where('projects.owner_id', $userId)
                  ->orWhere('project_members.user_id', $userId);
            })
            ->distinct();

        return $query->orderByDesc('projects.created_at')->paginate(20);
    }

    /**
     * Insert a new project and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('projects')->insertGetId($data);
    }

    /**
     * Update a project by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('projects')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Add a member to a project.
     *
     * @param int $projectId
     * @param int $userId
     * @return void
     */
    public function addMember(int $projectId, int $userId): void
    {
        $exists = DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            DB::table('project_members')->insert([
                'project_id' => $projectId,
                'user_id'    => $userId,
                'added_at'   => now(),
            ]);
        }
    }

    /**
     * Remove a member from a project.
     *
     * @param int $projectId
     * @param int $userId
     * @return void
     */
    public function removeMember(int $projectId, int $userId): void
    {
        DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Get all members of a project.
     *
     * @param int $projectId
     * @return array
     */
    public function getMembers(int $projectId): array
    {
        return DB::table('project_members')
            ->join('team_members', 'project_members.user_id', '=', 'team_members.id')
            ->select('team_members.id', 'team_members.name', 'team_members.email', 'team_members.role', 'project_members.added_at')
            ->where('project_members.project_id', $projectId)
            ->get()
            ->toArray();
    }

    /**
     * Calculate progress as percentage of done tasks over total tasks.
     *
     * @param int $projectId
     * @return float
     */
    public function calculateProgress(int $projectId): float
    {
        $counts = DB::table('tasks')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = \'done\' THEN 1 ELSE 0 END) as done')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->first();

        if (!$counts || (int) $counts->total === 0) {
            return 0.0;
        }

        return round(((float) $counts->done / (float) $counts->total) * 100, 2);
    }

    /**
     * Calculate total effort (sum of work_log hours) for a project.
     *
     * @param int $projectId
     * @return float
     */
    public function calculateEffort(int $projectId): float
    {
        $result = DB::table('work_logs')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        return round((float) $result, 2);
    }
}

