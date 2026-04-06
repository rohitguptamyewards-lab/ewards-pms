<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 61 — Bug SLA state calculator cron.
 * Item 28 — Bug SLA escalation chain.
 */
class CalculateBugSlaStates extends Command
{
    protected $signature   = 'pms:calculate-bug-sla-states';
    protected $description = 'Mark bug SLA records as breached and send escalation notifications';

    // SLA hours by severity
    private const SLA_HOURS = [
        'p0' => 4,
        'p1' => 24,
        'p2' => 72,
        'p3' => 168,
    ];

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now     = Carbon::now();
        $breached = 0;
        $escalated = 0;

        $records = DB::table('bug_sla_records')
            ->whereNull('deleted_at')
            ->whereNull('breached_at')
            ->whereNotNull('sla_deadline')
            ->get();

        foreach ($records as $record) {
            $slaDeadline = Carbon::parse($record->sla_deadline);

            if ($now->isAfter($slaDeadline)) {
                // Mark as breached
                DB::table('bug_sla_records')
                    ->where('id', $record->id)
                    ->update([
                        'breached_at' => $now->toDateTimeString(),
                        'updated_at'  => $now->toDateTimeString(),
                    ]);

                $this->sendEscalationNotifications($record);
                $breached++;
            }
        }

        // Send escalation warnings for bugs approaching SLA breach (within 20% of SLA window)
        $atRiskRecords = DB::table('bug_sla_records')
            ->whereNull('deleted_at')
            ->whereNull('breached_at')
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '>', $now)
            ->get();

        foreach ($atRiskRecords as $record) {
            $slaDeadline   = Carbon::parse($record->sla_deadline);
            $createdAt     = Carbon::parse($record->created_at);
            $totalWindow   = $createdAt->diffInHours($slaDeadline);
            $remaining     = $now->diffInHours($slaDeadline, false);
            $warningWindow = max(1, $totalWindow * 0.2);

            if ($remaining > 0 && $remaining <= $warningWindow) {
                $this->sendAtRiskNotifications($record, (int) $remaining);
                $escalated++;
            }
        }

        $this->info("Breached: {$breached}, At-risk escalations: {$escalated}");
        Log::info("pms:calculate-bug-sla-states completed. Breached: {$breached}, escalations: {$escalated}");

        return self::SUCCESS;
    }

    private function sendEscalationNotifications(object $record): void
    {
        $userIds  = $this->resolveEscalationChain($record);
        $severity = strtoupper($record->severity ?? 'unknown');

        foreach ($userIds as $userId) {
            $this->notificationService->send($userId, 'bug_sla_breached', [
                'bug_sla_id'  => $record->id,
                'feature_id'  => $record->feature_id,
                'severity'    => $record->severity,
                'message'     => "[{$severity}] Bug SLA BREACHED for feature #{$record->feature_id}. Immediate action required.",
                'breached_at' => now()->toDateTimeString(),
            ], isCritical: true);
        }
    }

    private function sendAtRiskNotifications(object $record, int $hoursRemaining): void
    {
        $userIds  = $this->resolveEscalationChain($record, escalate: false);
        $severity = strtoupper($record->severity ?? 'unknown');

        foreach ($userIds as $userId) {
            $this->notificationService->send($userId, 'bug_sla_at_risk', [
                'bug_sla_id'       => $record->id,
                'feature_id'       => $record->feature_id,
                'severity'         => $record->severity,
                'hours_remaining'  => $hoursRemaining,
                'message'          => "[{$severity}] Bug SLA at risk — {$hoursRemaining}h remaining for feature #{$record->feature_id}.",
            ], isCritical: ($record->severity === 'p0'));
        }
    }

    /**
     * Escalation chain:
     *   - P0: notify feature assignee + all CTOs/Managers (critical)
     *   - P1: notify feature assignee + managers
     *   - P2/P3: notify feature assignee only
     */
    private function resolveEscalationChain(object $record, bool $escalate = true): array
    {
        $feature = DB::table('features')->where('id', $record->feature_id)->first();
        $userIds = [];

        if ($feature && $feature->assigned_to) {
            $userIds[] = (int) $feature->assigned_to;
        }

        $severity = $record->severity ?? 'p3';

        if ($escalate || in_array($severity, ['p0', 'p1'])) {
            $roles = $severity === 'p0' ? ['cto', 'manager', 'ceo'] : ['cto', 'manager'];
            $managers = DB::table('team_members')
                ->whereIn('role', $roles)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            $userIds = array_unique(array_merge($userIds, $managers));
        }

        return $userIds;
    }
}
