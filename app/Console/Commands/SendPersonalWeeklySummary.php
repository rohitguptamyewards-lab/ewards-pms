<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 35 — Personal weekly summary email sent Monday 9AM to every team member.
 */
class SendPersonalWeeklySummary extends Command
{
    protected $signature   = 'pms:send-personal-weekly-summary';
    protected $description = 'Send each team member a personal weekly summary of their work, deadlines, and achievements';

    public function __construct(private readonly EmailNotificationService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $weekStart = Carbon::now()->startOfWeek()->subWeek();
        $weekEnd   = Carbon::now()->startOfWeek()->subDay();
        $fromStr   = $weekStart->toDateString();
        $toStr     = $weekEnd->toDateString();

        $members = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'role')
            ->get();

        $sent = 0;
        foreach ($members as $member) {
            try {
                $summary = $this->buildSummary($member->id, $fromStr, $toStr);
                $this->emailService->sendPersonalWeeklySummary($member, $summary, $weekStart, $weekEnd);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Failed to send personal summary to {$member->email}: " . $e->getMessage());
            }
        }

        $this->info("Personal weekly summary sent to {$sent} members.");
        Log::info("pms:send-personal-weekly-summary completed. Recipients: {$sent}");

        return self::SUCCESS;
    }

    private function buildSummary(int $memberId, string $from, string $to): array
    {
        // Hours logged this week
        $hoursLogged = (float) DB::table('work_logs')
            ->where('user_id', $memberId)
            ->whereNull('deleted_at')
            ->whereBetween('log_date', [$from, $to])
            ->sum('hours_spent');

        // Tasks completed
        $tasksCompleted = DB::table('tasks')
            ->where('assigned_to', $memberId)
            ->where('status', 'done')
            ->whereNull('deleted_at')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // Features worked on
        $featuresWorked = DB::table('work_logs')
            ->where('user_id', $memberId)
            ->whereNull('deleted_at')
            ->whereNotNull('feature_id')
            ->whereBetween('log_date', [$from, $to])
            ->distinct('feature_id')
            ->count('feature_id');

        // Upcoming deadlines (next 7 days)
        $upcomingDeadlines = DB::table('deadlines as d')
            ->leftJoin('features as f', function ($j) {
                $j->on('d.deadlineable_id', '=', 'f.id')
                  ->where('d.deadlineable_type', '=', 'feature');
            })
            ->where(function ($q) use ($memberId) {
                $q->where('f.assigned_to', $memberId)
                  ->orWhere('f.qa_owner_id', $memberId);
            })
            ->whereNull('d.deleted_at')
            ->where('d.state', '!=', 'completed')
            ->whereBetween('d.due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->select('d.due_date', 'f.title as feature_title', 'd.state', 'd.type')
            ->orderBy('d.due_date')
            ->get()
            ->toArray();

        // Logging streak
        $streak = DB::table('team_members')
            ->where('id', $memberId)
            ->value('current_streak') ?? 0;

        // Activity log count
        $logCount = DB::table('activity_logs')
            ->where('team_member_id', $memberId)
            ->whereNull('deleted_at')
            ->whereBetween('log_date', [$from, $to])
            ->count();

        // Blocked items
        $blockers = DB::table('blockers')
            ->join('features as f', 'blockers.feature_id', '=', 'f.id')
            ->where('blockers.reported_by', $memberId)
            ->where('blockers.status', 'active')
            ->whereNull('blockers.deleted_at')
            ->select('f.title as feature_title', 'blockers.description')
            ->get()
            ->toArray();

        return [
            'hours_logged'       => round($hoursLogged, 1),
            'tasks_completed'    => $tasksCompleted,
            'features_worked'    => $featuresWorked,
            'upcoming_deadlines' => $upcomingDeadlines,
            'streak'             => (int) $streak,
            'activity_log_count' => $logCount,
            'blockers'           => $blockers,
        ];
    }
}
