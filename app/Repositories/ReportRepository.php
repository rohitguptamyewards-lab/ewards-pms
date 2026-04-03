<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Get work log report data with optional filters.
     *
     * @param array $filters Supported: user_id, project_id, from, to
     * @return array{logs: array, totalHours: float}
     */
    public function getWorkLogReport(array $filters): array
    {
        $query = DB::table('work_logs')
            ->join('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'team_members.name as user_name',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->whereNull('work_logs.deleted_at');

        if (!empty($filters['user_id'])) {
            $query->where('work_logs.user_id', $filters['user_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('work_logs.project_id', $filters['project_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('work_logs.log_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('work_logs.log_date', '<=', $filters['to']);
        }

        $logs = $query->orderByDesc('work_logs.log_date')->get()->toArray();

        $totalHours = array_sum(array_map(fn ($log) => (float) $log->hours_spent, $logs));

        return [
            'logs'       => $logs,
            'totalHours' => round($totalHours, 2),
        ];
    }

    /**
     * Get a comprehensive report for a single project.
     *
     * @param int $projectId
     * @return array{taskBreakdown: array, totalEffort: float, tasks: array}
     */
    public function getProjectReport(int $projectId): array
    {
        // Task breakdown by status
        $taskBreakdown = DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->toArray();

        // Total effort (hours)
        $totalEffort = DB::table('work_logs')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        // Full task list with assignees
        $tasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->select(
                'tasks.*',
                'team_members.name as assignee_name'
            )
            ->where('tasks.project_id', $projectId)
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.priority')
            ->orderBy('tasks.status')
            ->get()
            ->toArray();

        return [
            'taskBreakdown' => $taskBreakdown,
            'totalEffort'   => round((float) $totalEffort, 2),
            'tasks'         => $tasks,
        ];
    }

    /**
     * Get an individual report for a user over a date range.
     *
     * @param int         $userId
     * @param string|null $from
     * @param string|null $to
     * @return array{totalHours: float, projects: array, logs: array}
     */
    public function getIndividualReport(int $userId, ?string $from = null, ?string $to = null): array
    {
        $logsQuery = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.user_id', $userId)
            ->whereNull('work_logs.deleted_at');

        if ($from) {
            $logsQuery->where('work_logs.log_date', '>=', $from);
        }

        if ($to) {
            $logsQuery->where('work_logs.log_date', '<=', $to);
        }

        $logs = $logsQuery->orderByDesc('work_logs.log_date')->get()->toArray();

        $totalHours = array_sum(array_map(fn ($log) => (float) $log->hours_spent, $logs));

        // Hours grouped by project
        $projectHoursQuery = DB::table('work_logs')
            ->join('projects', 'work_logs.project_id', '=', 'projects.id')
            ->select(
                'projects.id as project_id',
                'projects.name as project_name',
                DB::raw('SUM(work_logs.hours_spent) as total_hours')
            )
            ->where('work_logs.user_id', $userId)
            ->whereNull('work_logs.deleted_at');

        if ($from) {
            $projectHoursQuery->where('work_logs.log_date', '>=', $from);
        }

        if ($to) {
            $projectHoursQuery->where('work_logs.log_date', '<=', $to);
        }

        $projects = $projectHoursQuery
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('total_hours')
            ->get()
            ->toArray();

        return [
            'totalHours' => round($totalHours, 2),
            'projects'   => $projects,
            'logs'       => $logs,
        ];
    }
}
