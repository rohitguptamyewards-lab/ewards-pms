<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * Get individual dashboard data for a specific user.
     *
     * @param int $userId
     * @return array{todaysLogs: array, myTasks: array, myProjects: array, weeklyHours: float}
     */
    public function getIndividualData(int $userId): array
    {
        $today = Carbon::today()->toDateString();
        $weekAgo = Carbon::today()->subDays(7)->toDateString();

        $todaysLogs = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.user_id', $userId)
            ->where('work_logs.log_date', $today)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.created_at')
            ->get()
            ->toArray();

        $myTasks = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.*',
                'projects.name as project_name'
            )
            ->where('tasks.assigned_to', $userId)
            ->whereIn('tasks.status', ['open', 'in_progress', 'blocked'])
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.priority')
            ->orderByDesc('tasks.created_at')
            ->get()
            ->toArray();

        $myProjects = DB::table('projects')
            ->join('project_members', 'projects.id', '=', 'project_members.project_id')
            ->select(
                'projects.*',
                DB::raw("(SELECT IFNULL(SUM(wl.hours_spent), 0) FROM work_logs wl WHERE wl.project_id = projects.id AND wl.user_id = {$userId} AND wl.deleted_at IS NULL) as my_hours")
            )
            ->where('project_members.user_id', $userId)
            ->where('projects.status', 'active')
            ->whereNull('projects.deleted_at')
            ->get()
            ->toArray();

        $weeklyHoursResult = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', '>=', $weekAgo)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        return [
            'todaysLogs'  => $todaysLogs,
            'myTasks'     => $myTasks,
            'myProjects'  => $myProjects,
            'weeklyHours' => round((float) $weeklyHoursResult, 2),
        ];
    }

    /**
     * Get manager dashboard data: overview of all projects, tasks, team workload.
     *
     * @return array{activeProjects: array, openTasks: int, blockedTasks: array, overdueTasks: array, teamWorkload: array, untriagedRequests: int}
     */
    public function getManagerData(): array
    {
        $today = Carbon::today()->toDateString();

        // Active projects with progress
        $activeProjects = DB::table('projects')
            ->leftJoin('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->select(
                'projects.*',
                'team_members.name as owner_name',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as task_count'),
                DB::raw('(SELECT IFNULL(ROUND(SUM(CASE WHEN tasks.status = \'done\' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as progress')
            )
            ->where('projects.status', 'active')
            ->whereNull('projects.deleted_at')
            ->get()
            ->toArray();

        // Open tasks count
        $openTasks = DB::table('tasks')
            ->where('status', 'open')
            ->whereNull('deleted_at')
            ->count();

        // Blocked tasks with assignee and latest blocker reason from work_logs
        $blockedTasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'team_members.name as assignee_name',
                'projects.name as project_name',
                DB::raw("(SELECT wl.blocker FROM work_logs wl WHERE wl.task_id = tasks.id AND wl.blocker IS NOT NULL AND wl.blocker != '' AND wl.deleted_at IS NULL ORDER BY wl.created_at DESC LIMIT 1) as blocker_reason")
            )
            ->where('tasks.status', 'blocked')
            ->whereNull('tasks.deleted_at')
            ->get()
            ->toArray();

        // Overdue tasks: deadline < today AND status != done
        $overdueTasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.deadline',
                'tasks.status',
                'team_members.name as assignee_name',
                'projects.name as project_name'
            )
            ->where('tasks.deadline', '<', $today)
            ->where('tasks.status', '!=', 'done')
            ->whereNull('tasks.deleted_at')
            ->get()
            ->toArray();

        // Team workload: today's work_logs grouped by user with project/task/hours
        $teamWorkload = DB::table('work_logs')
            ->join('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.user_id',
                'team_members.name as user_name',
                'projects.name as project_name',
                'tasks.title as task_title',
                'work_logs.hours_spent',
                'work_logs.note'
            )
            ->where('work_logs.log_date', $today)
            ->whereNull('work_logs.deleted_at')
            ->orderBy('team_members.name')
            ->get()
            ->toArray();

        // Untriaged requests count
        $untriagedRequests = DB::table('requests')
            ->where('status', 'received')
            ->whereNull('deleted_at')
            ->count();

        return [
            'activeProjects'    => [
                'count' => count($activeProjects),
                'list'  => $activeProjects,
            ],
            'openTasks'         => $openTasks,
            'blockedTasks'      => $blockedTasks,
            'overdueTasks'      => $overdueTasks,
            'teamWorkload'      => $teamWorkload,
            'untriagedRequests' => $untriagedRequests,
        ];
    }
}
