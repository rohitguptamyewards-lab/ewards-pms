<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 33/62 — Auto-generated monthly report (1st of month for CEO).
 */
class GenerateMonthlyReport extends Command
{
    protected $signature   = 'pms:generate-monthly-report';
    protected $description = 'Generate and send monthly executive summary report to CEO (1st of month)';

    public function __construct(private readonly EmailNotificationService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $monthStart = Carbon::now()->subMonth()->startOfMonth();
        $monthEnd   = Carbon::now()->subMonth()->endOfMonth();

        $report = $this->buildMonthlyReport($monthStart, $monthEnd);

        // Send to CEO + CTO
        $recipients = DB::table('team_members')
            ->whereIn('role', ['ceo', 'cto'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'role')
            ->get();

        $sent = 0;
        foreach ($recipients as $member) {
            try {
                $this->emailService->sendMonthlyReport($member, $report, $monthStart, $monthEnd);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Failed to send monthly report to {$member->email}: " . $e->getMessage());
            }
        }

        $this->info("Monthly report sent to {$sent} recipients.");
        Log::info("pms:generate-monthly-report completed. Recipients: {$sent}");

        return self::SUCCESS;
    }

    private function buildMonthlyReport(Carbon $from, Carbon $to): array
    {
        $fromStr = $from->toDateString();
        $toStr   = $to->toDateString();

        // Features shipped
        $featuresShipped = DB::table('features')
            ->whereNull('deleted_at')
            ->where('status', 'released')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // Feature pipeline snapshot
        $pipelineRows = DB::table('features')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();
        $pipeline = [];
        foreach ($pipelineRows as $row) {
            $pipeline[$row->status] = (int) $row->count;
        }

        // Total hours logged this month
        $hoursLogged = DB::table('work_logs')
            ->whereNull('deleted_at')
            ->whereBetween('log_date', [$fromStr, $toStr])
            ->sum('hours_spent');

        // Per-member hours
        $memberHours = DB::table('work_logs')
            ->join('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->whereNull('work_logs.deleted_at')
            ->whereBetween('work_logs.log_date', [$fromStr, $toStr])
            ->select('team_members.name', 'team_members.role', DB::raw('SUM(work_logs.hours_spent) as hours'))
            ->groupBy('team_members.id', 'team_members.name', 'team_members.role')
            ->orderByDesc('hours')
            ->get()
            ->toArray();

        // Requests summary
        $requestStats = DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();
        $requests = [];
        foreach ($requestStats as $row) {
            $requests[$row->status] = (int) $row->count;
        }

        // New requests this month
        $newRequests = DB::table('requests')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Bug SLA summary
        $bugStats = DB::table('bug_sla_records')
            ->select('severity', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN breached_at IS NOT NULL THEN 1 ELSE 0 END) as breached'))
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('severity')
            ->get()
            ->toArray();

        // Team size
        $teamSize = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        // Active projects
        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        // Releases this month
        $releases = DB::table('releases')
            ->whereNull('deleted_at')
            ->whereBetween('release_date', [$fromStr, $toStr])
            ->select('version', 'release_date', 'environment')
            ->get()
            ->toArray();

        // Cost summary (if cost snapshots exist)
        $costSnapshot = DB::table('feature_cost_snapshots')
            ->whereNull('deleted_at')
            ->whereBetween('snapshot_date', [$fromStr, $toStr])
            ->select(DB::raw('SUM(total_cost) as total_cost'), DB::raw('COUNT(DISTINCT feature_id) as features_tracked'))
            ->first();

        // Generate narrative summary
        $narrative = $this->generateNarrative([
            'features_shipped' => $featuresShipped,
            'pipeline'         => $pipeline,
            'hours_logged'     => round((float) $hoursLogged, 1),
            'new_requests'     => $newRequests,
            'team_size'        => $teamSize,
            'active_projects'  => $activeProjects,
        ]);

        return [
            'period'           => ['from' => $fromStr, 'to' => $toStr],
            'narrative'        => $narrative,
            'features_shipped' => $featuresShipped,
            'pipeline'         => $pipeline,
            'hours_logged'     => round((float) $hoursLogged, 1),
            'member_hours'     => $memberHours,
            'requests'         => $requests,
            'new_requests'     => $newRequests,
            'bug_stats'        => $bugStats,
            'team_size'        => $teamSize,
            'active_projects'  => $activeProjects,
            'releases'         => $releases,
            'cost_summary'     => $costSnapshot ? (array) $costSnapshot : null,
            'data_freshness'   => now()->toIso8601String(),
        ];
    }

    /**
     * Item 39 — Auto-generated narrative summary for CEO.
     */
    private function generateNarrative(array $data): string
    {
        $monthName   = Carbon::now()->subMonth()->format('F Y');
        $inProgress  = $data['pipeline']['in_progress'] ?? 0;
        $inReview    = $data['pipeline']['in_review'] ?? 0;
        $backlog     = $data['pipeline']['backlog'] ?? 0;
        $shipped     = $data['features_shipped'];
        $hours       = $data['hours_logged'];
        $requests    = $data['new_requests'];

        return "In {$monthName}, the team shipped {$shipped} feature(s) and logged {$hours}h of work across "
            . "{$data['active_projects']} active project(s). There are currently {$inProgress} features in progress, "
            . "{$inReview} in review, and {$backlog} in the backlog. "
            . "{$requests} new merchant request(s) were received. "
            . "Team size: {$data['team_size']} active members.";
    }
}
