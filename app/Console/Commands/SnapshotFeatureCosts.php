<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Item 63 — Feature cost/usage snapshot cron.
 */
class SnapshotFeatureCosts extends Command
{
    protected $signature   = 'pms:snapshot-feature-costs';
    protected $description = 'Take daily cost and usage snapshots for all active features';

    public function handle(): int
    {
        $today    = Carbon::today()->toDateString();
        $snapped  = 0;

        // Get all in-progress / recently released features
        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->whereIn('status', ['in_progress', 'in_review', 'in_qa', 'ready_for_release', 'released'])
            ->get();

        foreach ($features as $feature) {
            // Skip if snapshot for today already exists
            $exists = DB::table('feature_cost_snapshots')
                ->where('feature_id', $feature->id)
                ->where('snapshot_date', $today)
                ->exists();

            if ($exists) {
                continue;
            }

            // Calculate hours logged on this feature
            $hoursLogged = DB::table('work_logs')
                ->where('feature_id', $feature->id)
                ->whereNull('deleted_at')
                ->sum('hours_spent');

            // Calculate cost using most recent cost rates of contributors
            $contributors = DB::table('work_logs')
                ->where('feature_id', $feature->id)
                ->whereNull('deleted_at')
                ->select('user_id', DB::raw('SUM(hours_spent) as hours'))
                ->groupBy('user_id')
                ->get();

            $totalCost = 0;
            foreach ($contributors as $contributor) {
                $costRate = DB::table('cost_rates')
                    ->where('team_member_id', $contributor->user_id)
                    ->where('effective_from', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
                    })
                    ->orderByDesc('effective_from')
                    ->first();

                if ($costRate) {
                    // Decrypt the loaded_hourly_rate (stored encrypted via CostRate model)
                    try {
                        $decrypted = Crypt::decryptString($costRate->loaded_hourly_rate);
                        $rate = is_numeric($decrypted) ? (float) $decrypted : 0;
                    } catch (\Throwable $e) {
                        // Fallback: value stored plain (e.g. in tests/local env)
                        $rate = is_numeric($costRate->loaded_hourly_rate)
                            ? (float) $costRate->loaded_hourly_rate
                            : 0;
                    }

                    $totalCost += $contributor->hours * $rate;
                }
            }

            // Overhead multiplier from feature cost type
            $overheadMultiplier = $feature->overhead_multiplier ?? 1.0;
            $totalCostWithOverhead = $totalCost * $overheadMultiplier;

            DB::table('feature_cost_snapshots')->insert([
                'feature_id'    => $feature->id,
                'snapshot_date' => $today,
                'actual_hours'  => (int) round((float) $hoursLogged),
                'total_cost'    => round($totalCostWithOverhead, 2),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $snapped++;
        }

        $this->info("Created {$snapped} cost snapshots.");
        Log::info("pms:snapshot-feature-costs completed. Snapshots: {$snapped}");

        return self::SUCCESS;
    }
}
