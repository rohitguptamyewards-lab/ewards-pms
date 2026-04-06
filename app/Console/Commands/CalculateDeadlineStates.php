<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 60 — Deadline state calculator cron.
 * States: on_track → at_risk (≤7 days) → breached (past due).
 */
class CalculateDeadlineStates extends Command
{
    protected $signature   = 'pms:calculate-deadline-states';
    protected $description = 'Recalculate on_track / at_risk / breached states for all deadlines';

    public function handle(): int
    {
        $today    = Carbon::today();
        $updated  = 0;

        $deadlines = DB::table('deadlines')
            ->whereNull('deleted_at')
            ->get();

        foreach ($deadlines as $deadline) {
            $dueDate   = Carbon::parse($deadline->due_date);
            $daysUntil = $today->diffInDays($dueDate, false);

            $newState = match (true) {
                $daysUntil < 0  => 'breached',
                $daysUntil <= 7 => 'at_risk',
                default         => 'on_track',
            };

            if ($deadline->state !== $newState) {
                DB::table('deadlines')
                    ->where('id', $deadline->id)
                    ->update(['state' => $newState, 'updated_at' => now()]);
                $updated++;
            }
        }

        $this->info("Updated {$updated} deadline states.");
        Log::info("pms:calculate-deadline-states completed. Updated: {$updated}");

        return self::SUCCESS;
    }
}
