<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\FeatureCostSnapshotRepository;
use App\Repositories\ModuleDebtScoreRepository;
use App\Services\ModuleDebtScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CostDashboardController extends Controller
{
    public function __construct(
        private readonly FeatureCostSnapshotRepository $costSnapshotRepo,
        private readonly ModuleDebtScoreRepository $debtScoreRepo,
        private readonly ModuleDebtScoreService $debtScoreService,
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

        // Feature cost breakdown
        $featureCosts = DB::table('feature_cost_snapshots as fcs')
            ->join('features as f', 'fcs.feature_id', '=', 'f.id')
            ->leftJoin('modules as m', 'f.module_id', '=', 'm.id')
            ->select(
                'fcs.feature_id', 'f.title as feature_title', 'm.name as module_name',
                'fcs.total_cost', 'fcs.cost_by_person', 'fcs.cost_by_activity_type',
                'fcs.estimated_hours', 'fcs.actual_hours'
            )
            ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)')
            ->orderByDesc('fcs.total_cost')
            ->limit(50)
            ->get();

        // Initiative cost rollup
        $initiativeCosts = DB::table('features as f')
            ->join('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('initiatives as i', 'f.initiative_id', '=', 'i.id')
            ->whereNotNull('f.initiative_id')
            ->groupBy('f.initiative_id', 'i.title')
            ->select(
                'f.initiative_id', 'i.title as initiative_title',
                DB::raw('SUM(fcs.total_cost) as total_cost'),
                DB::raw('SUM(fcs.estimated_hours) as estimated_hours'),
                DB::raw('SUM(fcs.actual_hours) as actual_hours'),
                DB::raw('COUNT(DISTINCT f.id) as feature_count')
            )
            ->orderByDesc('total_cost')
            ->get();

        // Module lifetime cost
        $moduleCosts = DB::table('features as f')
            ->join('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->join('modules as m', 'f.module_id', '=', 'm.id')
            ->groupBy('f.module_id', 'm.name')
            ->select(
                'f.module_id', 'm.name as module_name',
                DB::raw('SUM(fcs.total_cost) as total_cost'),
                DB::raw('COUNT(DISTINCT f.id) as feature_count')
            )
            ->orderByDesc('total_cost')
            ->get();

        // Meeting cost
        $meetingCost = DB::table('activity_logs')
            ->where('activity_type', 'meeting')
            ->whereNull('deleted_at')
            ->sum(DB::raw("CASE duration WHEN '15min' THEN 0.25 WHEN '30min' THEN 0.5 WHEN '1h' THEN 1 WHEN '2h' THEN 2 WHEN '4h' THEN 4 WHEN '8h' THEN 8 ELSE 0 END"));

        // Debt scorecard
        $debtScores = $this->debtScoreRepo->findLatestAll();

        if ($request->wantsJson()) {
            return response()->json(compact(
                'featureCosts', 'initiativeCosts', 'moduleCosts', 'meetingCost', 'debtScores'
            ));
        }

        return Inertia::render('Dashboard/CostDashboard', [
            'featureCosts'    => $featureCosts,
            'initiativeCosts' => $initiativeCosts,
            'moduleCosts'     => $moduleCosts,
            'meetingCost'     => round($meetingCost, 1),
            'debtScores'      => $debtScores,
        ]);
    }
}
