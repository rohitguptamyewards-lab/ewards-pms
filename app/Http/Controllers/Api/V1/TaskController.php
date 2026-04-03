<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepository $taskRepository,
    ) {}

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
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $id = $this->taskRepository->create($request->validated());

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

        return Inertia::render('Tasks/Show', [
            'task' => $task,
            'workLogs' => $workLogs,
            'comments' => $comments,
        ]);
    }

    /**
     * Update task fields.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->taskRepository->update($id, $request->all());
        $task = $this->taskRepository->findById($id);

        return response()->json($task);
    }

    /**
     * Change task status with transition validation.
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:open,in_progress,blocked,done',
        ]);

        $this->taskService->changeStatus($id, $request->input('status'));
        $task = $this->taskRepository->findById($id);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'task' => $task,
        ]);
    }
}
