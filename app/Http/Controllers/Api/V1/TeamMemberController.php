<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TeamMemberController extends Controller
{
    /**
     * List all active team members.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $members = DB::table('team_members')
            ->select('id', 'name', 'email', 'role', 'department', 'joining_date', 'is_active', 'weekly_capacity')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                // Count assigned tasks
                $member->task_count = DB::table('tasks')
                    ->where('assigned_to', $member->id)
                    ->whereIn('status', ['open', 'in_progress', 'blocked'])
                    ->whereNull('deleted_at')
                    ->count();

                // Count active projects
                $member->project_count = DB::table('project_members')
                    ->join('projects', 'project_members.project_id', '=', 'projects.id')
                    ->where('project_members.user_id', $member->id)
                    ->where('projects.status', 'active')
                    ->whereNull('projects.deleted_at')
                    ->count();

                // Total hours logged
                $member->total_hours = round((float) DB::table('work_logs')
                    ->where('user_id', $member->id)
                    ->whereNull('deleted_at')
                    ->sum('hours_spent'), 2);

                return $member;
            });

        if ($request->wantsJson()) {
            return response()->json($members);
        }

        return Inertia::render('TeamMembers/Index', [
            'members' => $members,
        ]);
    }

    /**
     * Show a team member's profile: projects, tasks, work logs, total effort.
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $member = DB::table('team_members')
            ->select('id', 'name', 'email', 'role', 'department', 'joining_date', 'is_active', 'weekly_capacity', 'working_hours')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$member, 404, 'Team member not found.');

        // Projects this member belongs to
        $projects = DB::table('project_members')
            ->join('projects', 'project_members.project_id', '=', 'projects.id')
            ->leftJoin('team_members as owners', 'projects.owner_id', '=', 'owners.id')
            ->select(
                'projects.id',
                'projects.name',
                'projects.status',
                'projects.priority',
                'projects.end_date',
                'owners.name as owner_name',
                DB::raw("(SELECT IFNULL(SUM(wl.hours_spent), 0) FROM work_logs wl WHERE wl.project_id = projects.id AND wl.user_id = {$id} AND wl.deleted_at IS NULL) as my_hours"),
                DB::raw("(SELECT IFNULL(ROUND(SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1), 0) FROM tasks t WHERE t.project_id = projects.id AND t.deleted_at IS NULL) as progress")
            )
            ->where('project_members.user_id', $id)
            ->whereNull('projects.deleted_at')
            ->orderByDesc('projects.created_at')
            ->get();

        // Tasks assigned to this member
        $tasks = DB::table('tasks')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.status',
                'tasks.priority',
                'tasks.deadline',
                'projects.name as project_name',
                'projects.id as project_id'
            )
            ->where('tasks.assigned_to', $id)
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.priority')
            ->orderByDesc('tasks.created_at')
            ->get();

        // Recent work logs (last 30 days)
        $recentLogs = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.id',
                'work_logs.log_date',
                'work_logs.hours_spent',
                'work_logs.status',
                'work_logs.note',
                'work_logs.blocker',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.user_id', $id)
            ->where('work_logs.log_date', '>=', now()->subDays(30)->toDateString())
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.log_date')
            ->get();

        // Summary stats
        $totalHours = round((float) DB::table('work_logs')
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->sum('hours_spent'), 2);

        $thisWeekHours = round((float) DB::table('work_logs')
            ->where('user_id', $id)
            ->where('log_date', '>=', now()->startOfWeek()->toDateString())
            ->whereNull('deleted_at')
            ->sum('hours_spent'), 2);

        $openTasksCount = DB::table('tasks')
            ->where('assigned_to', $id)
            ->whereIn('status', ['open', 'in_progress', 'blocked'])
            ->whereNull('deleted_at')
            ->count();

        $blockedTasksCount = DB::table('tasks')
            ->where('assigned_to', $id)
            ->where('status', 'blocked')
            ->whereNull('deleted_at')
            ->count();

        $payload = [
            'member'           => $member,
            'projects'         => $projects,
            'tasks'            => $tasks,
            'recentLogs'       => $recentLogs,
            'stats' => [
                'totalHours'       => $totalHours,
                'thisWeekHours'    => $thisWeekHours,
                'openTasksCount'   => $openTasksCount,
                'blockedTasksCount' => $blockedTasksCount,
                'projectCount'     => count($projects),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('TeamMembers/Show', $payload);
    }
}
