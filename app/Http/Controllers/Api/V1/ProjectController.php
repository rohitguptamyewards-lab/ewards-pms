<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Repositories\ProjectRepository;
use App\Services\EmailNotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ProjectRepository $projectRepository,
        private readonly EmailNotificationService $emailService,
    ) {}

    /**
     * List projects with stats.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user    = auth()->user();
        $roleRaw = $user->role;
        $role    = $roleRaw instanceof \App\Enums\Role ? $roleRaw->value : (string) $roleRaw;
        $isManager = in_array($role, ['cto', 'ceo', 'manager']);

        if ($isManager) {
            $projects = $this->projectRepository->findAll();
        } else {
            $projects = $this->projectRepository->findByMember($user->id);
        }

        if ($request->wantsJson()) {
            return response()->json($projects);
        }

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the create form with team members list.
     */
    public function create(): InertiaResponse
    {
        $teamMembers = DB::table('team_members')
            ->where('is_active', true)
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Create', [
            'teamMembers' => $teamMembers,
        ]);
    }

    /**
     * Store a new project and optionally add members.
     * Only CTO, CEO, Manager can create projects.
     */
    public function store(StoreProjectRequest $request)
    {
        abort_unless(in_array($this->authRole(), ['cto', 'ceo', 'manager']), 403, 'Only managers can create projects.');
        $data = $request->validated();
        $memberIds = $data['member_ids'] ?? [];
        unset($data['member_ids']);

        $id = $this->projectRepository->create($data);

        foreach ($memberIds as $memberId) {
            $this->projectRepository->addMember($id, $memberId);
        }

        $project = $this->projectRepository->findById($id);

        if ($request->wantsJson()) {
            return response()->json($project, 201);
        }

        return redirect()->route('projects.show', $id)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Show a single project with tasks, members, and stats.
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $project = $this->projectRepository->findById($id);

        // Fetch tasks for this project
        $tasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->select('tasks.*', 'team_members.name as assignee_name')
            ->where('tasks.project_id', $id)
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.priority')
            ->get();

        $project->tasks = $tasks;

        // Build task counts by status
        $taskCounts = ['open' => 0, 'in_progress' => 0, 'blocked' => 0, 'done' => 0];
        foreach ($tasks as $task) {
            if (isset($taskCounts[$task->status])) {
                $taskCounts[$task->status]++;
            }
        }

        $stats = [
            'progress' => $this->projectRepository->calculateProgress($id),
            'effort' => $this->projectRepository->calculateEffort($id),
            'total_effort' => $this->projectRepository->calculateEffort($id),
            'task_counts' => $taskCounts,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'project' => $project,
                'stats' => $stats,
            ]);
        }

        // Work logs for this project
        $workLogs = DB::table('work_logs')
            ->leftJoin('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select('work_logs.*', 'team_members.name as user_name', 'tasks.title as task_title')
            ->where('work_logs.project_id', $id)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.log_date')
            ->get();

        // Documents for this project (documents table has no soft deletes)
        $documents = DB::table('documents')
            ->where('documentable_type', 'project')
            ->where('documentable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        // Comments for this project
        $comments = DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select('comments.*', 'team_members.name as user_name')
            ->where('comments.commentable_type', 'project')
            ->where('comments.commentable_id', $id)
            ->whereNull('comments.parent_id')
            ->whereNull('comments.deleted_at')
            ->orderByDesc('comments.created_at')
            ->get();

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

        return Inertia::render('Projects/Show', [
            'project'  => $project,
            'stats'    => $stats,
            'workLogs' => $workLogs,
            'documents' => $documents,
            'comments' => $comments,
        ]);
    }

    private function authRole(): string
    {
        $role = auth()->user()->role;
        return $role instanceof \App\Enums\Role ? $role->value : (string) $role;
    }

    /**
     * Update project fields.
     * Only CTO, CEO, Manager can update projects.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless(in_array($this->authRole(), ['cto', 'ceo', 'manager']), 403, 'Only managers can update projects.');

        $oldProject = $this->projectRepository->findById($id);
        $oldStatus = $oldProject->status ?? null;

        $this->projectRepository->update($id, $request->all());
        $project = $this->projectRepository->findById($id);

        // Log status change as a comment
        $newStatus = $project->status ?? null;
        if ($newStatus && $oldStatus && $newStatus !== $oldStatus) {
            $userName = auth()->user()->name;
            DB::table('comments')->insert([
                'commentable_type' => 'project',
                'commentable_id'   => $id,
                'user_id'          => auth()->id(),
                'body'             => "Status changed from \"{$oldStatus}\" to \"{$newStatus}\" by {$userName}.",
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return response()->json($project);
    }

    /**
     * Add a member to a project.
     * Only CTO, CEO, Manager can add members.
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        abort_unless(in_array($this->authRole(), ['cto', 'ceo', 'manager']), 403);
        $request->validate([
            'user_id' => 'required|integer|exists:team_members,id',
        ]);

        $userId = $request->input('user_id');
        $this->projectRepository->addMember($id, $userId);

        try {
            $this->emailService->onProjectMemberAdded($id, $userId);
        } catch (\Throwable $e) {
            // Never let email failure break the action
        }

        return response()->json(['message' => 'Member added successfully.']);
    }

    /**
     * Remove a member from a project.
     */
    public function removeMember(int $projectId, int $userId): JsonResponse
    {
        $this->projectRepository->removeMember($projectId, $userId);

        return response()->json(['message' => 'Member removed successfully.']);
    }
}

