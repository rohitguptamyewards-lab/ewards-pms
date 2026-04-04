<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\AiToolRepository;
use App\Repositories\AiUsageLogRepository;
use App\Repositories\PromptTemplateRepository;
use App\Services\AiUsageLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AiDashboardController extends Controller
{
    public function __construct(
        private readonly AiToolRepository $aiToolRepository,
        private readonly AiUsageLogRepository $aiUsageLogRepository,
        private readonly AiUsageLogService $aiUsageLogService,
        private readonly PromptTemplateRepository $promptTemplateRepository,
    ) {}

    private function isCto(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return $value === 'cto';
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        abort_unless($this->isCto(), 403);

        // AI tool registry with total spend
        $tools = DB::table('ai_tools')
            ->whereNull('deleted_at')
            ->select('*', DB::raw('cost_per_seat_monthly * seats_purchased as monthly_spend'))
            ->orderByDesc('monthly_spend')
            ->get();

        $totalSpend = $tools->sum('monthly_spend');

        // Effectiveness matrix
        $matrix = $this->aiUsageLogRepository->getEffectivenessMatrix();

        // Per-person AI profiles
        $profiles = DB::table('ai_usage_logs as aul')
            ->join('team_members as tm', 'aul.team_member_id', '=', 'tm.id')
            ->leftJoin('ai_tools as at', 'aul.ai_tool_id', '=', 'at.id')
            ->groupBy('tm.id', 'tm.name')
            ->select(
                'tm.id as member_id', 'tm.name as member_name',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw("STRING_AGG(DISTINCT at.name, ',') as tools_used"),
                DB::raw("STRING_AGG(DISTINCT aul.capability, ',') as capabilities_used")
            )
            ->orderByDesc('usage_count')
            ->get();

        // AI ROI
        $roi = $this->aiUsageLogService->calculateRoi();

        // Top templates
        $topTemplates = $this->promptTemplateRepository->getMostUsed();

        if ($request->wantsJson()) {
            return response()->json(compact(
                'tools', 'totalSpend', 'matrix', 'profiles', 'roi', 'topTemplates'
            ));
        }

        return Inertia::render('Dashboard/AiDashboard', [
            'tools'        => $tools,
            'totalSpend'   => round($totalSpend, 2),
            'matrix'       => $matrix,
            'profiles'     => $profiles,
            'roi'          => $roi,
            'topTemplates' => $topTemplates,
        ]);
    }
}
