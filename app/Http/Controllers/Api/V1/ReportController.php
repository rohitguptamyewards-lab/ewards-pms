<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\ProjectRepository;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ProjectRepository $projectRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    /**
     * Work logs report with date/user/project filters.
     */
    public function workLogs(Request $request): InertiaResponse|JsonResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }

        $filters = $request->only(['user_id', 'project_id', 'task_id', 'date_from', 'date_to']);
        $data = $this->reportService->generateWorkLogReport($filters);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        $teamMembers = \Illuminate\Support\Facades\DB::table('team_members')
            ->where('is_active', true)->orderBy('name')->select('id', 'name')->get();

        $projects = \Illuminate\Support\Facades\DB::table('projects')
            ->whereNull('deleted_at')->orderBy('name')->select('id', 'name')->get();

        $tasks = \Illuminate\Support\Facades\DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.title')
            ->select('tasks.id', 'tasks.title', 'tasks.project_id', 'projects.name as project_name')
            ->get();

        return Inertia::render('Reports/WorkLogs', [
            'report'       => $data,
            'filters'      => $filters,
            'teamMembers'  => $teamMembers,
            'projects'     => $projects,
            'tasks'        => $tasks,
            'dataFreshness' => now()->toIso8601String(), // Item 37
        ]);
    }

    /**
     * Projects report with status/progress stats.
     */
    public function projects(Request $request): InertiaResponse|JsonResponse
    {
        if (! $this->isManager()) {
            abort(403);
        }

        $filters = $request->only(['status', 'owner_id']);
        $paginator = $this->projectRepository->findAll($filters, 100);
        $data = ['projects' => $paginator->items()];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        $teamMembers = \Illuminate\Support\Facades\DB::table('team_members')
            ->where('is_active', true)->orderBy('name')->select('id', 'name')->get();

        return Inertia::render('Reports/Projects', [
            'report'       => $data,
            'filters'      => $filters,
            'teamMembers'  => $teamMembers,
            'dataFreshness' => now()->toIso8601String(), // Item 37
        ]);
    }

    /**
     * Individual user report.
     */
    public function individual(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['user_id', 'date_from', 'date_to']);
        // Non-managers can only view their own individual report
        $userId = $this->isManager()
            ? (int) ($filters['user_id'] ?? auth()->id())
            : auth()->id();
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $data = $this->reportService->generateIndividualReport($userId, $from, $to);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        $teamMembers = $this->isManager()
            ? \Illuminate\Support\Facades\DB::table('team_members')
                ->where('is_active', true)->orderBy('name')->select('id', 'name')->get()
            : collect();

        return Inertia::render('Reports/Individual', [
            'report'       => $data,
            'filters'      => $filters,
            'teamMembers'  => $teamMembers,
            'isManager'    => $this->isManager(),
            'dataFreshness' => now()->toIso8601String(), // Item 37
        ]);
    }
}
