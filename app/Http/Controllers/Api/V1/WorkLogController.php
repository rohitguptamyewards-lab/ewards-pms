<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\WorkLogCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkLogRequest;
use App\Repositories\WorkLogRepository;
use App\Services\WorkLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class WorkLogController extends Controller
{
    public function __construct(
        private readonly WorkLogService $workLogService,
        private readonly WorkLogRepository $workLogRepository,
    ) {}

    /**
     * List work logs with filters -- Inertia page for web, JSON for API.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['project_id', 'task_id', 'user_id', 'date_from', 'date_to']);

        $managerRoles = ['cto', 'ceo', 'manager'];
        $roleRaw = auth()->user()->role;
        $userRole = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;

        // Non-managers can only ever see their own logs
        if (! in_array($userRole, $managerRoles)) {
            $filters['user_id'] = auth()->id();
        } elseif (! $request->wantsJson() && empty($filters['user_id'])) {
            // Managers default to all logs on the web view (they can filter)
        }

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

        $workLogs = $query->orderByDesc('work_logs.log_date')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($workLogs);
        }

        $projectsForFilter = DB::table('projects')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $membersForFilter = in_array($userRole, $managerRoles)
            ? DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get()
            : collect();

        return Inertia::render('WorkLogs/Index', [
            'workLogs'    => $workLogs,
            'filters'     => $filters,
            'projects'    => $projectsForFilter,
            'teamMembers' => $membersForFilter,
            'isManager'   => in_array($userRole, $managerRoles),
        ]);
    }

    /**
     * Show the create form with projects and tasks dropdowns.
     */
    public function create(): InertiaResponse
    {
        $user    = auth()->user();
        $roleRaw = $user->role;
        $role    = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;
        $isManager = in_array($role, ['cto', 'ceo', 'manager', 'mc_team']);

        if ($isManager) {
            $projects = DB::table('projects')
                ->where('status', 'active')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        } else {
            $projects = DB::table('projects')
                ->join('project_members', 'projects.id', '=', 'project_members.project_id')
                ->where('project_members.user_id', $user->id)
                ->where('projects.status', 'active')
                ->select('projects.id', 'projects.name')
                ->orderBy('projects.name')
                ->get();
        }

        // Attach tasks per project
        foreach ($projects as $project) {
            $project->tasks = DB::table('tasks')
                ->select('id', 'project_id', 'title', 'status')
                ->where('project_id', $project->id)
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
        }

        $today = now()->toDateString();
        $lastEndTime = $this->workLogService->getLastEndTime($user->id, $today);

        return Inertia::render('WorkLogs/Create', [
            'projects'    => $projects,
            'lastEndTime' => $lastEndTime,
        ]);
    }

    /**
     * Store a new work log, auto-setting user_id and firing event.
     */
    public function store(StoreWorkLogRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $id = $this->workLogService->create($data);
        $workLog = DB::table('work_logs')->where('id', $id)->first();

        WorkLogCreated::dispatch(
            $workLog->project_id,
            $workLog->user_id,
            (float) $workLog->hours_spent,
        );

        if ($request->wantsJson()) {
            return response()->json($workLog, 201);
        }

        return redirect()->route('work-logs.index')
            ->with('success', 'Work log created successfully.');
    }

    /**
     * Update a work log. Only owner or CTO can update.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $workLog = DB::table('work_logs')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $roleRaw = auth()->user()->role;
        $role = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;
        abort_unless($workLog->user_id === auth()->id() || $role === 'cto', 403, 'You can only edit your own work logs.');

        $this->workLogRepository->update($id, $request->all());
        $workLog = DB::table('work_logs')->where('id', $id)->first();

        return response()->json($workLog);
    }

    /**
     * Delete a work log. Only owner or CTO can delete.
     */
    public function destroy(int $id): JsonResponse
    {
        $workLog = DB::table('work_logs')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $roleRaw = auth()->user()->role;
        $role = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;
        abort_unless($workLog->user_id === auth()->id() || $role === 'cto', 403, 'You can only delete your own work logs.');

        $this->workLogRepository->delete($id);

        return response()->json(['message' => 'Work log deleted successfully.']);
    }
}


