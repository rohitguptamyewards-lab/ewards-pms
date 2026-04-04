<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CeoDashboardController extends Controller
{
    private function isCeo(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['ceo', 'cto']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        abort_unless($this->isCeo(), 403);

        // BR-071: Template-based narrative, NOT AI
        $narrative = $this->generateNarrativeSummary();

        // Roadmap completion %
        $totalFeatures    = DB::table('features')->whereNull('deleted_at')->count();
        $releasedFeatures = DB::table('features')->whereNull('deleted_at')->where('status', 'released')->count();
        $roadmapCompletion = $totalFeatures > 0 ? round(($releasedFeatures / $totalFeatures) * 100, 1) : 0;

        // BR-072: Investment vs Impact matrix — aggregated costs only, no per-person data
        $moduleMetrics = DB::table('modules as m')
            ->leftJoin('features as f', 'f.module_id', '=', 'm.id')
            ->leftJoin('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('feature_usage_snapshots as fus', function ($j) {
                $j->on('f.id', '=', 'fus.feature_id')
                  ->whereRaw('fus.date = (SELECT MAX(date) FROM feature_usage_snapshots WHERE feature_id = fus.feature_id)');
            })
            ->whereNull('m.deleted_at')
            ->groupBy('m.id', 'm.name')
            ->select(
                'm.id as module_id', 'm.name as module_name',
                DB::raw('COALESCE(SUM(fcs.total_cost), 0) as total_cost'),
                DB::raw('COALESCE(SUM(fus.merchants_using_count), 0) as adoption'),
                DB::raw('COALESCE(SUM(fus.revenue_attributed), 0) as revenue'),
                DB::raw('CASE WHEN COALESCE(SUM(fcs.total_cost), 0) > 0 THEN ROUND(COALESCE(SUM(fus.revenue_attributed), 0) / SUM(fcs.total_cost), 2) ELSE 0 END as roi_ratio')
            )
            ->orderByDesc('total_cost')
            ->get();

        // Team health aggregates (BR-072: no individual data)
        $activeSprint = DB::table('sprints')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        $sprintDeliveryRate = 0;
        if ($activeSprint) {
            $committed = $activeSprint->committed_hours ?: 1;
            $delivered = DB::table('sprint_features')
                ->where('sprint_id', $activeSprint->id)
                ->where('carried_over', false)
                ->sum('committed_hours');
            $sprintDeliveryRate = round(($delivered / $committed) * 100, 1);
        }

        $totalCapacity = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->sum('weekly_capacity');

        $totalLogged = DB::table('activity_logs')
            ->where('log_date', '>=', now()->startOfWeek()->toDateString())
            ->whereNull('deleted_at')
            ->sum(DB::raw("CASE duration WHEN '15min' THEN 0.25 WHEN '30min' THEN 0.5 WHEN '1h' THEN 1 WHEN '2h' THEN 2 WHEN '4h' THEN 4 WHEN '8h' THEN 8 ELSE 0 END"));

        $utilisation = $totalCapacity > 0 ? round(($totalLogged / $totalCapacity) * 100, 1) : 0;

        $teamHealth = [
            'sprint_delivery_rate' => $sprintDeliveryRate,
            'utilisation'          => $utilisation,
            'total_capacity'       => $totalCapacity,
        ];

        // Forward-looking risk indicators
        $overdueFeatures = DB::table('features')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['released'])
            ->where('deadline', '<', now()->toDateString())
            ->count();

        $activeBlockers = DB::table('blockers')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        $risks = [
            'overdue_features'  => $overdueFeatures,
            'active_blockers'   => $activeBlockers,
        ];

        if ($request->wantsJson()) {
            return response()->json(compact(
                'narrative', 'roadmapCompletion', 'moduleMetrics', 'teamHealth', 'risks'
            ));
        }

        return Inertia::render('Dashboard/CeoDashboard', [
            'narrative'         => $narrative,
            'roadmapCompletion' => $roadmapCompletion,
            'moduleMetrics'     => $moduleMetrics,
            'teamHealth'        => $teamHealth,
            'risks'             => $risks,
        ]);
    }

    /**
     * BR-071: Template-based narrative summary, NOT AI-generated.
     */
    private function generateNarrativeSummary(): string
    {
        $totalFeatures  = DB::table('features')->whereNull('deleted_at')->count();
        $released       = DB::table('features')->whereNull('deleted_at')->where('status', 'released')->count();
        $inProgress     = DB::table('features')->whereNull('deleted_at')->where('status', 'in_progress')->count();
        $blockers       = DB::table('blockers')->where('status', 'active')->whereNull('deleted_at')->count();

        $weekReleases = DB::table('releases')
            ->where('release_date', '>=', now()->startOfWeek()->toDateString())
            ->whereNull('deleted_at')
            ->count();

        $pct = $totalFeatures > 0 ? round(($released / $totalFeatures) * 100) : 0;

        $lines = [];
        $lines[] = "This week: {$weekReleases} release(s) shipped.";
        $lines[] = "Overall: {$released} of {$totalFeatures} features released ({$pct}% complete).";
        $lines[] = "{$inProgress} feature(s) currently in progress.";

        if ($blockers > 0) {
            $lines[] = "Warning: {$blockers} active blocker(s) require attention.";
        }

        return implode(' ', $lines);
    }
}
