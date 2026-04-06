<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 34 — Auto-generated quarterly deep-dive report (CTO + CEO).
 */
class GenerateQuarterlyReport extends Command
{
    protected $signature   = 'pms:generate-quarterly-report';
    protected $description = 'Generate and send quarterly deep-dive report to CTO and CEO';

    public function __construct(private readonly EmailNotificationService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $quarterStart = Carbon::now()->subQuarter()->startOfQuarter();
        $quarterEnd   = Carbon::now()->subQuarter()->endOfQuarter();

        $report = $this->buildQuarterlyReport($quarterStart, $quarterEnd);

        $recipients = DB::table('team_members')
            ->whereIn('role', ['ceo', 'cto'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'role')
            ->get();

        $sent = 0;
        foreach ($recipients as $member) {
            try {
                $this->emailService->sendQuarterlyReport($member, $report, $quarterStart, $quarterEnd);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Failed to send quarterly report to {$member->email}: " . $e->getMessage());
            }
        }

        $this->info("Quarterly report sent to {$sent} recipients.");
        Log::info("pms:generate-quarterly-report completed. Recipients: {$sent}");

        return self::SUCCESS;
    }

    private function buildQuarterlyReport(Carbon $from, Carbon $to): array
    {
        $fromStr = $from->toDateString();
        $toStr   = $to->toDateString();
        $qLabel  = 'Q' . $from->quarter . ' ' . $from->year;

        // Initiative ROI
        $initiativeMetrics = DB::table('initiatives as i')
            ->leftJoin('features as f', 'f.initiative_id', '=', 'i.id')
            ->leftJoin('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('feature_usage_snapshots as fus', function ($j) {
                $j->on('f.id', '=', 'fus.feature_id')
                  ->whereRaw('fus.date = (SELECT MAX(date) FROM feature_usage_snapshots WHERE feature_id = fus.feature_id)');
            })
            ->whereNull('i.deleted_at')
            ->groupBy('i.id', 'i.title', 'i.status')
            ->select(
                'i.id', 'i.title', 'i.status',
                DB::raw('COUNT(DISTINCT f.id) as feature_count'),
                DB::raw('COALESCE(SUM(fcs.total_cost), 0) as total_cost'),
                DB::raw('COALESCE(SUM(fus.revenue_attributed), 0) as revenue'),
                DB::raw('SUM(CASE WHEN f.status = \'released\' THEN 1 ELSE 0 END) as features_released')
            )
            ->orderByDesc('total_cost')
            ->get()
            ->toArray();

        // Module economics
        $moduleEconomics = DB::table('features as f')
            ->join('modules as m', 'f.module_id', '=', 'm.id')
            ->leftJoin('feature_cost_snapshots as fcs', function ($j) {
                $j->on('f.id', '=', 'fcs.feature_id')
                  ->whereRaw('fcs.snapshot_date = (SELECT MAX(snapshot_date) FROM feature_cost_snapshots WHERE feature_id = fcs.feature_id)');
            })
            ->leftJoin('feature_usage_snapshots as fus', function ($j) {
                $j->on('f.id', '=', 'fus.feature_id')
                  ->whereRaw('fus.date = (SELECT MAX(date) FROM feature_usage_snapshots WHERE feature_id = fus.feature_id)');
            })
            ->whereNull('f.deleted_at')
            ->groupBy('m.id', 'm.name')
            ->select(
                'm.id', 'm.name',
                DB::raw('COALESCE(SUM(fcs.total_cost), 0) as lifetime_cost'),
                DB::raw('COALESCE(SUM(fus.merchants_using_count), 0) as merchant_count'),
                DB::raw('COALESCE(SUM(fus.revenue_attributed), 0) as total_revenue'),
                DB::raw('COUNT(DISTINCT f.id) as feature_count')
            )
            ->orderByDesc('lifetime_cost')
            ->get()
            ->toArray();

        // AI strategy review
        $aiUsage = DB::table('ai_usage_logs as a')
            ->join('ai_tools as t', 'a.ai_tool_id', '=', 't.id')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.created_at', [$from, $to])
            ->groupBy('t.id', 't.name')
            ->select(
                't.name as tool_name',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw("SUM(CASE WHEN a.outcome = 'helpful' THEN 1 ELSE 0 END) as helpful_count")
            )
            ->orderByDesc('usage_count')
            ->get()
            ->toArray();

        // Knowledge risk (bus factor)
        $busFactor = DB::table('work_logs as wl')
            ->join('tasks as t', 'wl.task_id', '=', 't.id')
            ->join('projects as p', 't.project_id', '=', 'p.id')
            ->join('team_members as tm', 'wl.user_id', '=', 'tm.id')
            ->whereNull('wl.deleted_at')
            ->groupBy('p.id', 'p.name', 'tm.id', 'tm.name')
            ->select('p.id as project_id', 'p.name as project_name', 'tm.name as member_name', DB::raw('SUM(wl.hours_spent) as hours'))
            ->orderByDesc('hours')
            ->limit(50)
            ->get()
            ->toArray();

        // Sprint velocity trend
        $sprintVelocity = DB::table('sprints')
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->whereBetween('end_date', [$fromStr, $toStr])
            ->select('sprint_number', 'committed_hours', 'total_capacity_hours', 'start_date', 'end_date')
            ->orderBy('sprint_number')
            ->get()
            ->toArray();

        // Features shipped this quarter
        $featuresShipped = DB::table('features')
            ->whereNull('deleted_at')
            ->where('status', 'released')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // Total cost this quarter
        $totalCost = DB::table('feature_cost_snapshots')
            ->whereBetween('snapshot_date', [$fromStr, $toStr])
            ->sum('total_cost');

        // Hours logged
        $hoursLogged = DB::table('work_logs')
            ->whereNull('deleted_at')
            ->whereBetween('log_date', [$fromStr, $toStr])
            ->sum('hours_spent');

        // Bug stats
        $bugStats = DB::table('bug_sla_records')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN breached_at IS NOT NULL THEN 1 ELSE 0 END) as breached'),
                DB::raw('AVG(reopen_count) as avg_reopen')
            )
            ->first();

        // Debt health
        $debtScores = DB::table('module_debt_scores as mds')
            ->join('modules as m', 'mds.module_id', '=', 'm.id')
            ->whereRaw('mds.scored_at = (SELECT MAX(scored_at) FROM module_debt_scores WHERE module_id = mds.module_id)')
            ->select('m.name', 'mds.health_score', 'mds.backlog_size', 'mds.debt_velocity')
            ->orderBy('mds.health_score')
            ->get()
            ->toArray();

        return [
            'quarter'            => $qLabel,
            'period'             => ['from' => $fromStr, 'to' => $toStr],
            'features_shipped'   => $featuresShipped,
            'total_cost'         => round((float) $totalCost, 2),
            'hours_logged'       => round((float) $hoursLogged, 1),
            'initiative_metrics' => $initiativeMetrics,
            'module_economics'   => $moduleEconomics,
            'ai_usage'           => $aiUsage,
            'bus_factor'         => $busFactor,
            'sprint_velocity'    => $sprintVelocity,
            'bug_stats'          => $bugStats ? (array) $bugStats : null,
            'debt_scores'        => $debtScores,
            'data_freshness'     => now()->toIso8601String(),
        ];
    }
}
