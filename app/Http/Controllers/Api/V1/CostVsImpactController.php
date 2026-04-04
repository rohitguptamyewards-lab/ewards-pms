<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CostVsImpactController extends Controller
{
    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        abort_unless($this->isManager(), 403);

        // Per-feature cost vs impact
        $featureMetrics = DB::table('features as f')
            ->leftJoin('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('feature_usage_snapshots as fus', function ($j) {
                $j->on('f.id', '=', 'fus.feature_id')
                  ->whereRaw('fus.date = (SELECT MAX(date) FROM feature_usage_snapshots WHERE feature_id = fus.feature_id)');
            })
            ->leftJoin('modules as m', 'f.module_id', '=', 'm.id')
            ->whereNull('f.deleted_at')
            ->select(
                'f.id', 'f.title', 'm.name as module_name',
                DB::raw('COALESCE(fcs.total_cost, 0) as total_cost'),
                DB::raw('COALESCE(fus.merchants_using_count, 0) as merchants_adopted'),
                DB::raw('COALESCE(fus.revenue_attributed, 0) as revenue_attributed'),
                DB::raw('CASE WHEN COALESCE(fcs.total_cost, 0) > 0 THEN ROUND(COALESCE(fus.revenue_attributed, 0) / fcs.total_cost, 2) ELSE 0 END as roi_ratio'),
                DB::raw('CASE WHEN COALESCE(fus.merchants_using_count, 0) > 0 THEN ROUND(COALESCE(fcs.total_cost, 0) / fus.merchants_using_count, 2) ELSE 0 END as cost_per_merchant')
            )
            ->orderByDesc('total_cost')
            ->limit(100)
            ->get();

        // Per-module rollup
        $moduleMetrics = DB::table('features as f')
            ->leftJoin('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('feature_usage_snapshots as fus', function ($j) {
                $j->on('f.id', '=', 'fus.feature_id')
                  ->whereRaw('fus.date = (SELECT MAX(date) FROM feature_usage_snapshots WHERE feature_id = fus.feature_id)');
            })
            ->join('modules as m', 'f.module_id', '=', 'm.id')
            ->whereNull('f.deleted_at')
            ->groupBy('m.id', 'm.name')
            ->select(
                'm.id as module_id', 'm.name as module_name',
                DB::raw('COALESCE(SUM(fcs.total_cost), 0) as lifetime_investment'),
                DB::raw('COALESCE(SUM(fus.merchants_using_count), 0) as merchant_count'),
                DB::raw('COALESCE(SUM(fus.revenue_attributed), 0) as total_revenue'),
                DB::raw('CASE WHEN COALESCE(SUM(fcs.total_cost), 0) > 0 THEN ROUND(COALESCE(SUM(fus.revenue_attributed), 0) / SUM(fcs.total_cost), 2) ELSE 0 END as roi'),
                DB::raw("COALESCE(SUM(CASE WHEN f.cost_type = 'recurring' THEN f.maintenance_cost_monthly ELSE 0 END), 0) as maintenance_cost")
            )
            ->orderByDesc('lifetime_investment')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(compact('featureMetrics', 'moduleMetrics'));
        }

        return Inertia::render('CostIntelligence/CostVsImpact', [
            'featureMetrics' => $featureMetrics,
            'moduleMetrics'  => $moduleMetrics,
        ]);
    }
}
