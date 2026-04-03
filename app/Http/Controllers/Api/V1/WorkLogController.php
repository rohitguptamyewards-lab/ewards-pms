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

        // Default to current user's logs for web
        if (! $request->wantsJson() && empty($filters['user_id'])) {
            $filters['user_id'] = auth()->id();
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

        return Inertia::render('WorkLogs/Index', [
            'workLogs' => $workLogs,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the create form with projects and tasks dropdowns.
     */
    public function create(): InertiaResponse
    {
        $projects = DB::table('projects')
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Attach tasks per project
        foreach ($projects as $project) {
            $project->tasks = DB::table('tasks')
                ->select('id', 'project_id', 'title', 'status')
                ->where('project_id', $project->id)
                ->whereNull('deleted_at')
                ->get()
                ->toArray();
        }

        return Inertia::render('WorkLogs/Create', [
            'projects' => $projects,
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
     * Update a work log.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->workLogRepository->update($id, $request->all());
        $workLog = DB::table('work_logs')->where('id', $id)->first();

        return response()->json($workLog);
    }

    /**
     * Delete a work log.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->workLogRepository->delete($id);

        return response()->json(['message' => 'Work log deleted successfully.']);
    }
}
