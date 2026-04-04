<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
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
     * Show the create team member form.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('TeamMembers/Create');
    }

    /**
     * Store a new team member.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:team_members,email',
            'password'         => ['required', 'confirmed', Password::min(8)],
            'role'             => 'required|string|in:cto,ceo,manager,mc_team,sales,developer,tester,analyst',
            'department'       => 'nullable|string|max:255',
            'joining_date'     => 'nullable|date',
            'weekly_capacity'  => 'nullable|numeric|min:0|max:168',
            'working_hours'    => 'nullable|numeric|min:0|max:24',
            'timezone'         => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'contractor_flag'  => 'boolean',
            'freelancer_flag'  => 'boolean',
            'git_username'     => 'nullable|string|max:255',
        ]);

        $plainPassword = $validated['password'];

        TeamMember::create($validated);

        // Send welcome email with credentials
        try {
            $emailService = app(EmailNotificationService::class);
            $emailService->onTeamMemberCreated(
                $validated['email'],
                $validated['name'],
                $validated['role'],
                $plainPassword,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome email failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('team-members.index')->with('success', 'Team member created successfully.');
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
                DB::raw("(SELECT COALESCE(SUM(wl.hours_spent), 0) FROM work_logs wl WHERE wl.project_id = projects.id AND wl.user_id = {$id} AND wl.deleted_at IS NULL) as my_hours"),
                DB::raw("(SELECT COALESCE(ROUND(SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1), 0) FROM tasks t WHERE t.project_id = projects.id AND t.deleted_at IS NULL) as progress")
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

    /**
     * Resend welcome email with a fresh temporary password.
     */
    public function resendWelcome(int $id): RedirectResponse
    {
        $member = TeamMember::where('id', $id)->whereNull('deleted_at')->firstOrFail();

        $newPassword = 'Password';
        $member->password = Hash::make($newPassword);
        $member->save();

        try {
            $emailService = app(EmailNotificationService::class);
            $emailService->onTeamMemberCreated(
                $member->email,
                $member->name,
                $member->role,
                $newPassword,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Resend welcome email failed', [
                'email' => $member->email,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        return back()->with('success', 'Welcome email sent to ' . $member->email . ' with a new temporary password.');
    }
}
