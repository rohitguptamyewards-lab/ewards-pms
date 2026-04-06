<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 58 — Daily activity log reminder cron.
 * Runs at ~5 PM — reminds active devs/analysts/testers who haven't logged work today.
 */
class SendDailyActivityReminder extends Command
{
    protected $signature   = 'pms:daily-activity-reminder';
    protected $description = 'Remind team members who have not logged work or activity today';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();
        $sent  = 0;

        // Active members in roles that should log daily
        $activeMembers = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('role', ['developer', 'analyst', 'tester', 'manager', 'cto'])
            ->select('id', 'name', 'role')
            ->get();

        foreach ($activeMembers as $member) {
            $hasWorkLog = DB::table('work_logs')
                ->where('user_id', $member->id)
                ->where('log_date', $today)
                ->whereNull('deleted_at')
                ->exists();

            $hasActivityLog = DB::table('activity_logs')
                ->where('team_member_id', $member->id)
                ->where('log_date', $today)
                ->whereNull('deleted_at')
                ->exists();

            if (! $hasWorkLog && ! $hasActivityLog) {
                $this->notificationService->send($member->id, 'daily_activity_reminder', [
                    'message' => "Don't forget to log your work for today, {$member->name}!",
                    'date'    => $today,
                ]);
                $sent++;
            }
        }

        $this->info("Sent {$sent} daily activity reminders.");
        Log::info("pms:daily-activity-reminder completed. Sent: {$sent}");

        return self::SUCCESS;
    }
}
