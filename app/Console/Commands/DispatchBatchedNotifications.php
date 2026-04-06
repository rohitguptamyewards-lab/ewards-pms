<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 30 — Smart notification batching (9AM digest + 5:30PM digest).
 * Item 31 — Critical P0 bypass (already sent immediately via NotificationService).
 */
class DispatchBatchedNotifications extends Command
{
    protected $signature   = 'pms:dispatch-batched-notifications';
    protected $description = 'Send batched notifications scheduled for delivery now (9AM and 5:30PM digests)';

    public function handle(): int
    {
        $now        = Carbon::now();
        $dispatched = 0;

        // Fetch all non-critical notifications scheduled for now or in the past that haven't been sent
        $pending = DB::table('pms_notifications')
            ->whereNull('sent_at')
            ->whereNull('read_at')
            ->where('scheduled_for', '<=', $now->toDateTimeString())
            ->where('is_critical', false)
            ->orderBy('scheduled_for')
            ->get();

        foreach ($pending as $notification) {
            DB::table('pms_notifications')
                ->where('id', $notification->id)
                ->update(['sent_at' => $now->toDateTimeString()]);

            $dispatched++;
        }

        // Also flush critical notifications that haven't been sent yet (belt-and-suspenders)
        $criticalPending = DB::table('pms_notifications')
            ->whereNull('sent_at')
            ->where('is_critical', true)
            ->where('scheduled_for', '<=', $now->toDateTimeString())
            ->get();

        foreach ($criticalPending as $notification) {
            DB::table('pms_notifications')
                ->where('id', $notification->id)
                ->update(['sent_at' => $now->toDateTimeString()]);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} notifications.");
        Log::info("pms:dispatch-batched-notifications completed. Dispatched: {$dispatched}");

        return self::SUCCESS;
    }
}
