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
                DB::raw("(SELECT COALESCE(SUM(wl.hours_spent), 0) FROM work_logs wl WHERE wl.project_id = projects.id AND wl.user_id = {$userId} AND wl.deleted_at IS NULL) as my_hours"),
                DB::raw("(SELECT COALESCE(ROUND(SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks t WHERE t.project_id = projects.id AND t.deleted_at IS NULL) as progress")
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
                DB::raw('(SELECT COALESCE(ROUND(SUM(CASE WHEN tasks.status = \'done\' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as progress')
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

    /**
     * CEO dashboard: high-level business view — pipeline counts, no task/person details.
     */
    public function getCEOData(): array
    {
        $featurePipelineRows = DB::table('features')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $featurePipeline = [];
        foreach ($featurePipelineRows as $row) {
            $featurePipeline[$row->status] = (int) $row->count;
        }

        $requestPipelineRows = DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $requestPipeline = [];
        foreach ($requestPipelineRows as $row) {
            $requestPipeline[$row->status] = (int) $row->count;
        }

        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        $teamSize = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        $totalHoursThisMonth = DB::table('work_logs')
            ->whereNull('deleted_at')
            ->whereYear('log_date', now()->year)
            ->whereMonth('log_date', now()->month)
            ->sum('hours_spent');

        return [
            'featurePipeline'    => $featurePipeline,
            'requestPipeline'    => $requestPipeline,
            'activeProjects'     => $activeProjects,
            'teamSize'           => $teamSize,
            'hoursThisMonth'     => round((float) $totalHoursThisMonth, 1),
        ];
    }

    /**
     * MC Team dashboard: request-centric — untriaged queue + merchant-blocked.
     */
    public function getMCTeamData(): array
    {
        $untriagedRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.urgency',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name',
                'team_members.name as requester_name'
            )
            ->where('requests.status', 'received')
            ->whereNull('requests.deleted_at')
            ->orderBy('requests.urgency')
            ->orderBy('requests.created_at')
            ->get()
            ->toArray();

        $merchantBlockedRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.status',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name',
                'team_members.name as requester_name'
            )
            ->where('requests.urgency', 'merchant_blocked')
            ->whereNotIn('requests.status', ['rejected', 'completed'])
            ->whereNull('requests.deleted_at')
            ->orderBy('requests.created_at')
            ->get()
            ->toArray();

        $total           = DB::table('requests')->whereNull('deleted_at')->count();
        $untriaged       = DB::table('requests')->where('status', 'received')->whereNull('deleted_at')->count();
        $accepted        = DB::table('requests')->where('status', 'accepted')->whereNull('deleted_at')->count();
        $merchantBlocked = DB::table('requests')
            ->where('urgency', 'merchant_blocked')
            ->whereNotIn('status', ['rejected', 'completed'])
            ->whereNull('deleted_at')
            ->count();

        return [
            'untriagedRequests'       => $untriagedRequests,
            'merchantBlockedRequests' => $merchantBlockedRequests,
            'stats' => [
                'total'           => $total,
                'untriaged'       => $untriaged,
                'accepted'        => $accepted,
                'merchantBlocked' => $merchantBlocked,
            ],
        ];
    }

    /**
     * Sales dashboard: only the requests this user submitted.
     */
    public function getSalesData(int $userId): array
    {
        $myRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.urgency',
                'requests.status',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name'
            )
            ->where('requests.requested_by', $userId)
            ->whereNull('requests.deleted_at')
            ->orderByDesc('requests.created_at')
            ->get()
            ->toArray();

        $statsRows = DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('requested_by', $userId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $stats = [];
        foreach ($statsRows as $row) {
            $stats[$row->status] = (int) $row->count;
        }

        return [
            'myRequests' => $myRequests,
            'stats'      => $stats,
        ];
    }
}
