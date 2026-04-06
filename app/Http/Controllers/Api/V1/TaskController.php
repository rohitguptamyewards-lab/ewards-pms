<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Repositories\TaskRepository;
use App\Services\EmailNotificationService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepository $taskRepository,
        private readonly EmailNotificationService $emailService,
    ) {}

    /**
     * List all tasks (web) with optional filters for the tasks index page.
     */
    public function indexAll(Request $request): InertiaResponse
    {
        $filters = $request->only(['project_id', 'user_id', 'status', 'deadline_to']);

        $user    = auth()->user();
        $roleRaw = $user->role;
        $role    = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;
        $isManager = in_array($role, ['cto', 'ceo', 'manager']);

        // Non-managers only see tasks assigned to them
        if (! $isManager) {
            $filters['user_id'] = $user->id;
        }

        $tasks = $this->taskRepository->findAll($filters);

        $projects = DB::table('projects')
            ->whereNull('deleted_at')->orderBy('name')->select('id', 'name')->get();

        $teamMembers = DB::table('team_members')
            ->where('is_active', true)->orderBy('name')->select('id', 'name')->get();

        return Inertia::render('Tasks/Index', [
            'tasks'       => $tasks,
            'filters'     => $filters,
            'projects'    => $projects,
            'teamMembers' => $teamMembers,
        ]);
    }

    /**
     * List tasks for a specific project.
     */
    public function index(int $projectId): JsonResponse
    {
        $tasks = $this->taskRepository->findByProject($projectId);

        return response()->json($tasks);
    }

    /**
     * Store a new task.
     * MC Team & Sales cannot create tasks.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $role = $this->authRole();
        abort_if(in_array($role, ['mc_team', 'sales']), 403, 'You do not have permission to create tasks.');

        $data = $request->validated();
        $id = $this->taskRepository->create($data);

        // Notify assignee
        if (!empty($data['assigned_to'])) {
            try {
                $this->emailService->onTaskAssigned($id, (int) $data['assigned_to'], auth()->id());
            } catch (\Throwable $e) {}
        }

        return response()->json($this->taskRepository->findById($id), 201);
    }

    /**
     * Show a single task with work logs and comments.
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $task = $this->taskRepository->findById($id);

        if ($request->wantsJson()) {
            return response()->json($task);
        }

        $workLogs = DB::table('work_logs')
            ->leftJoin('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->select('work_logs.*', 'team_members.name as user_name')
            ->where('work_logs.task_id', $id)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.log_date')
            ->get();

        $comments = DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select('comments.*', 'team_members.name as user_name')
            ->where('comments.commentable_type', 'task')
            ->where('comments.commentable_id', $id)
            ->whereNull('comments.parent_id')
            ->whereNull('comments.deleted_at')
            ->orderByDesc('comments.created_at')
            ->get();

        // Attach replies to each top-level comment
        foreach ($comments as $comment) {
            $comment->replies = DB::table('comments')
                ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
                ->select('comments.*', 'team_members.name as user_name')
                ->where('comments.parent_id', $comment->id)
                ->whereNull('comments.deleted_at')
                ->orderBy('comments.created_at')
                ->get()
                ->toArray();
        }

        $teamMembers = \DB::table('team_members')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name', 'role')
            ->get();

        return Inertia::render('Tasks/Show', [
            'task'        => $task,
            'workLogs'    => $workLogs,
            'comments'    => $comments,
            'teamMembers' => $teamMembers,
        ]);
    }

    private function authRole(): string
    {
        $role = auth()->user()->role;
        return $role instanceof \App\Enums\Role ? $role->value : (string) $role;
    }

    /**
     * Update task fields.
     * Only the assignee, project owner, or managers can update tasks.
     * MC Team & Sales cannot update tasks.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = $this->authRole();
        abort_if(in_array($role, ['mc_team', 'sales']), 403, 'You do not have permission to update tasks.');

        $oldTask = DB::table('tasks')->where('id', $id)->first();
        $oldAssignee = $oldTask->assigned_to ?? null;

        $this->taskRepository->update($id, $request->all());
        $task = $this->taskRepository->findById($id);

        // Notify new assignee if changed
        $newAssignee = $request->input('assigned_to');
        if ($newAssignee && (int) $newAssignee !== (int) $oldAssignee) {
            try {
                $this->emailService->onTaskAssigned($id, (int) $newAssignee, auth()->id());
            } catch (\Throwable $e) {}
        }

        return response()->json($task);
    }

    /**
     * Change task status with transition validation.
     * MC Team & Sales cannot change task status.
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $role = $this->authRole();
        abort_if(in_array($role, ['mc_team', 'sales']), 403, 'You do not have permission to change task status.');

        $request->validate([
            'status' => 'required|string|in:open,in_progress,blocked,done',
        ]);

        try {
            $this->taskService->changeStatus($id, $request->input('status'));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        $task = $this->taskRepository->findById($id);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'task' => $task,
        ]);
    }
}


