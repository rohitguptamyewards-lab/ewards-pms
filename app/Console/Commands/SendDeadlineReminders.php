<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 27 — Deadline-based notifications (7d, 3d, 1d reminders).
 */
class SendDeadlineReminders extends Command
{
    protected $signature   = 'pms:send-deadline-reminders';
    protected $description = 'Send 7-day, 3-day, and 1-day deadline reminder notifications';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();

        $deadlines = DB::table('deadlines')
            ->whereNull('deleted_at')
            ->where('state', '!=', 'breached')
            ->get();

        $sent = 0;

        foreach ($deadlines as $deadline) {
            $dueDate   = Carbon::parse($deadline->due_date);
            $daysUntil = $today->diffInDays($dueDate, false); // negative = overdue

            // Determine reminder window and which flag to set
            $reminders = [
                ['days' => 7, 'flag' => 'reminder_sent_7d', 'sent' => (bool) $deadline->reminder_sent_7d],
                ['days' => 3, 'flag' => 'reminder_sent_3d', 'sent' => (bool) $deadline->reminder_sent_3d],
                ['days' => 1, 'flag' => 'reminder_sent_1d', 'sent' => (bool) $deadline->reminder_sent_1d],
            ];

            foreach ($reminders as $reminder) {
                if ($reminder['sent']) {
                    continue;
                }

                // Fire when we are within the reminder window (daysUntil <= threshold)
                if ($daysUntil <= $reminder['days'] && $daysUntil >= 0) {
                    $this->sendReminderNotifications($deadline, $reminder['days'], $daysUntil);

                    DB::table('deadlines')
                        ->where('id', $deadline->id)
                        ->update([$reminder['flag'] => true]);

                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} deadline reminder notification batches.");
        Log::info("pms:send-deadline-reminders completed. Batches sent: {$sent}");

        return self::SUCCESS;
    }

    private function sendReminderNotifications(object $deadline, int $days, int $daysUntil): void
    {
        // Gather relevant user IDs — assignee or owner of the deadlineable entity
        $userIds = $this->resolveOwnerIds($deadline);

        $message = $daysUntil === 0
            ? "Deadline is TODAY for: {$deadline->deadlineable_type} #{$deadline->deadlineable_id}"
            : "Deadline in {$daysUntil} day(s) for: {$deadline->deadlineable_type} #{$deadline->deadlineable_id}";

        $isCritical = $daysUntil <= 1;

        foreach ($userIds as $userId) {
            $this->notificationService->send($userId, 'deadline_reminder', [
                'deadline_id'       => $deadline->id,
                'deadlineable_type' => $deadline->deadlineable_type,
                'deadlineable_id'   => $deadline->deadlineable_id,
                'due_date'          => $deadline->due_date,
                'days_until'        => $daysUntil,
                'reminder_window'   => $days,
                'message'           => $message,
            ], $isCritical);
        }
    }

    private function resolveOwnerIds(object $deadline): array
    {
        $type = $deadline->deadlineable_type;
        $id   = $deadline->deadlineable_id;

        if ($type === 'feature') {
            $row = DB::table('features')->where('id', $id)->whereNull('deleted_at')->first();
            if ($row) {
                return array_filter([$row->assigned_to, $row->qa_owner_id ?? null]);
            }
        }

        if ($type === 'initiative') {
            $row = DB::table('initiatives')->where('id', $id)->whereNull('deleted_at')->first();
            if ($row) {
                return array_filter([$row->owner_id ?? null]);
            }
        }

        if ($type === 'project') {
            $row = DB::table('projects')->where('id', $id)->whereNull('deleted_at')->first();
            if ($row) {
                return array_filter([$row->owner_id ?? null]);
            }
        }

        // Fallback: notify all managers/CTOs
        return DB::table('team_members')
            ->whereIn('role', ['cto', 'manager'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();
    }
}
