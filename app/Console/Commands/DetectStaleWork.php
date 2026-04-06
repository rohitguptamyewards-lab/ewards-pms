<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 59 — Stale work detection cron.
 * Flags features/tasks with no activity for more than the threshold (default: 5 business days).
 */
class DetectStaleWork extends Command
{
    protected $signature   = 'pms:detect-stale-work {--days=5 : Days of inactivity to flag as stale}';
    protected $description = 'Detect and notify about stale features and tasks with no recent activity';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $threshold = Carbon::now()->subDays($days);
        $stale     = 0;

        // Stale in-progress features — no work log in the past N days
        $staleFeatures = DB::table('features')
            ->whereNull('deleted_at')
            ->whereIn('status', ['in_progress', 'in_review', 'in_qa'])
            ->whereNotNull('assigned_to')
            ->get();

        foreach ($staleFeatures as $feature) {
            $lastActivity = DB::table('work_logs')
                ->where('feature_id', $feature->id)
                ->whereNull('deleted_at')
                ->max('log_date');

            $lastUpdated = Carbon::parse($feature->updated_at ?? $feature->created_at);
            $lastLog     = $lastActivity ? Carbon::parse($lastActivity) : null;

            $latestActivity = $lastLog ? max($lastUpdated, $lastLog) : $lastUpdated;

            if ($latestActivity->lessThan($threshold)) {
                $this->notifyStaleFeature($feature, $latestActivity, $days);
                $stale++;
            }
        }

        // Stale in-progress tasks — no work log in the past N days
        $staleTasks = DB::table('tasks')
            ->whereNull('deleted_at')
            ->whereIn('status', ['in_progress', 'blocked'])
            ->whereNotNull('assigned_to')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($staleTasks as $task) {
            $lastLog = DB::table('work_logs')
                ->where('task_id', $task->id)
                ->whereNull('deleted_at')
                ->max('log_date');

            $lastActivity = $lastLog ? Carbon::parse($lastLog) : Carbon::parse($task->updated_at);

            if ($lastActivity->lessThan($threshold)) {
                $this->notifyStaleTask($task, $lastActivity, $days);
                $stale++;
            }
        }

        $this->info("Detected and notified {$stale} stale items.");
        Log::info("pms:detect-stale-work completed. Stale items: {$stale}");

        return self::SUCCESS;
    }

    private function notifyStaleFeature(object $feature, Carbon $lastActivity, int $days): void
    {
        $daysSince = $lastActivity->diffInDays(now());
        $data = [
            'entity_type'   => 'feature',
            'entity_id'     => $feature->id,
            'entity_title'  => $feature->title,
            'days_inactive' => $daysSince,
            'message'       => "Feature \"{$feature->title}\" has had no activity for {$daysSince} days.",
        ];

        // Notify assignee
        if ($feature->assigned_to) {
            $this->notificationService->send((int) $feature->assigned_to, 'stale_work_detected', $data);
        }

        // Also notify managers
        $managers = DB::table('team_members')
            ->whereIn('role', ['cto', 'manager'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($managers as $managerId) {
            $this->notificationService->send((int) $managerId, 'stale_work_detected', $data);
        }
    }

    private function notifyStaleTask(object $task, Carbon $lastActivity, int $days): void
    {
        $daysSince = $lastActivity->diffInDays(now());
        $data = [
            'entity_type'   => 'task',
            'entity_id'     => $task->id,
            'entity_title'  => $task->title,
            'days_inactive' => $daysSince,
            'message'       => "Task \"{$task->title}\" has had no activity for {$daysSince} days.",
        ];

        if ($task->assigned_to) {
            $this->notificationService->send((int) $task->assigned_to, 'stale_work_detected', $data);
        }
    }
}
