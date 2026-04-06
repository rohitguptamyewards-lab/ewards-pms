<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 32/62 — Auto-generated weekly report (Monday 9AM for sales team).
 */
class GenerateWeeklyReport extends Command
{
    protected $signature   = 'pms:generate-weekly-report';
    protected $description = 'Generate and send weekly product summary report to sales team (Monday 9AM)';

    public function __construct(private readonly EmailNotificationService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $weekStart = Carbon::now()->startOfWeek()->subWeek(); // last week
        $weekEnd   = Carbon::now()->startOfWeek()->subDay();

        $report = $this->buildWeeklyReport($weekStart, $weekEnd);

        // Send to sales team + managers
        $recipients = DB::table('team_members')
            ->whereIn('role', ['sales', 'manager', 'cto'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email')
            ->get();

        $sent = 0;
        foreach ($recipients as $member) {
            try {
                $this->emailService->sendWeeklyReport($member, $report, $weekStart, $weekEnd);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Failed to send weekly report to {$member->email}: " . $e->getMessage());
            }
        }

        $this->info("Weekly report sent to {$sent} recipients.");
        Log::info("pms:generate-weekly-report completed. Recipients: {$sent}");

        return self::SUCCESS;
    }

    private function buildWeeklyReport(Carbon $from, Carbon $to): array
    {
        $fromStr = $from->toDateString();
        $toStr   = $to->toDateString();

        // Features shipped this week
        $featuresShipped = DB::table('features')
            ->whereNull('deleted_at')
            ->where('status', 'released')
            ->whereBetween('updated_at', [$from, $to])
            ->select('id', 'title', 'priority', 'status')
            ->get()
            ->toArray();

        // Features moved to in_progress
        $featuresStarted = DB::table('features')
            ->whereNull('deleted_at')
            ->where('status', 'in_progress')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // New requests received
        $newRequests = DB::table('requests')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->select('id', 'title', 'type', 'urgency', 'status')
            ->get()
            ->toArray();

        // Requests triaged
        $requestsTriaged = DB::table('requests')
            ->whereNull('deleted_at')
            ->whereIn('status', ['linked', 'fulfilled', 'deferred', 'rejected'])
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // Team hours logged
        $hoursLogged = DB::table('work_logs')
            ->whereNull('deleted_at')
            ->whereBetween('log_date', [$fromStr, $toStr])
            ->sum('hours_spent');

        // Active bugs (P0/P1)
        $criticalBugs = DB::table('bug_sla_records')
            ->whereNull('deleted_at')
            ->whereIn('severity', ['p0', 'p1'])
            ->whereNull('breached_at')
            ->count();

        // Releases this week
        $releases = DB::table('releases')
            ->whereNull('deleted_at')
            ->whereBetween('release_date', [$fromStr, $toStr])
            ->select('id', 'version', 'release_date', 'environment')
            ->get()
            ->toArray();

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

        return [
            'period'          => ['from' => $fromStr, 'to' => $toStr],
            'features_shipped' => $featuresShipped,
            'features_started' => $featuresStarted,
            'new_requests'     => $newRequests,
            'requests_triaged' => $requestsTriaged,
            'hours_logged'     => round((float) $hoursLogged, 1),
            'critical_bugs'    => $criticalBugs,
            'releases'         => $releases,
            'pipeline'         => $pipeline,
            'data_freshness'   => now()->toIso8601String(),
        ];
    }
}
