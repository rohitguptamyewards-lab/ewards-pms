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

    /**
     * Work logs report with date/user/project filters.
     */
    public function workLogs(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['user_id', 'project_id', 'date_from', 'date_to']);
        $data = $this->reportService->generateWorkLogReport($filters);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Reports/WorkLogs', [
            'report' => $data,
            'filters' => $filters,
        ]);
    }

    /**
     * Projects report with status/progress stats.
     */
    public function projects(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['status', 'owner_id']);
        $paginator = $this->projectRepository->findAll($filters, 100);
        $data = ['projects' => $paginator->items()];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Reports/Projects', [
            'report' => $data,
            'filters' => $filters,
        ]);
    }

    /**
     * Individual user report.
     */
    public function individual(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['user_id', 'date_from', 'date_to']);
        $userId = (int) ($filters['user_id'] ?? auth()->id());
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $data = $this->reportService->generateIndividualReport($userId, $from, $to);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Reports/Individual', [
            'report' => $data,
            'filters' => $filters,
        ]);
    }
}
