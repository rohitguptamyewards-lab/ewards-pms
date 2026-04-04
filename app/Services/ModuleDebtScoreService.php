<?php

namespace App\Services;

use App\Repositories\ModuleDebtScoreRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleDebtScoreService
{
    public function __construct(
        private readonly ModuleDebtScoreRepository $debtScoreRepository,
    ) {}

    /**
     * Calculate weekly debt scores for all modules.
     * health_score = 100 - growth_rate - bug_rate - age_penalty
     * Red <50, Yellow 50-70, Green >70
     */
    public function calculateWeeklyScores(string $weekDate = null): void
    {
        $weekDate = $weekDate ?? now()->startOfWeek()->toDateString();

        try {
            $modules = DB::table('modules')->whereNull('deleted_at')->get();

            foreach ($modules as $module) {
                $this->calculateForModule($module->id, $weekDate);
            }
        } catch (\Throwable $e) {
            Log::error('Debt score calculation failed', [
                'week_date' => $weekDate,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function calculateForModule(int $moduleId, string $weekDate): void
    {
        try {
            // Count open tech debt features for this module
            $debtFeatures = DB::table('features')
                ->where('module_id', $moduleId)
                ->where('cost_type', 'recurring')
                ->whereNotIn('status', ['released', 'archived'])
                ->whereNull('deleted_at')
                ->get();

            $backlogSize  = $debtFeatures->count();
            $backlogHours = 0;

            $under30  = 0;
            $mid30to90 = 0;
            $over90   = 0;
            $now = now();

            foreach ($debtFeatures as $f) {
                $backlogHours += (int) ($f->estimated_hours ?? 0);
                $age = $now->diffInDays($f->created_at);
                if ($age < 30) $under30++;
                elseif ($age <= 90) $mid30to90++;
                else $over90++;
            }

            // Debt velocity: added minus closed this quarter
            $quarterStart = now()->startOfQuarter()->toDateString();
            $addedThisQ = DB::table('features')
                ->where('module_id', $moduleId)
                ->where('cost_type', 'recurring')
                ->where('created_at', '>=', $quarterStart)
                ->whereNull('deleted_at')
                ->count();

            $closedThisQ = DB::table('features')
                ->where('module_id', $moduleId)
                ->where('cost_type', 'recurring')
                ->whereIn('status', ['released', 'archived'])
                ->where('updated_at', '>=', $quarterStart)
                ->whereNull('deleted_at')
                ->count();

            $debtVelocity = $addedThisQ - $closedThisQ;

            // Total features for ratio
            $totalFeatures = DB::table('features')
                ->where('module_id', $moduleId)
                ->whereNull('deleted_at')
                ->count();

            $ratio = $totalFeatures > 0 ? round($backlogSize / $totalFeatures, 4) : 0;

            // Health score: 100 - growth_rate - bug_rate - age_penalty
            $growthPenalty = max(0, $debtVelocity * 5);
            $agePenalty    = $over90 * 3;
            $bugRate = DB::table('bug_sla_records')
                ->join('features', 'bug_sla_records.feature_id', '=', 'features.id')
                ->where('features.module_id', $moduleId)
                ->whereNull('bug_sla_records.deleted_at')
                ->count();
            $bugPenalty = min($bugRate * 2, 30);

            $healthScore = max(0, min(100, 100 - $growthPenalty - $bugPenalty - $agePenalty));

            $this->debtScoreRepository->upsert([
                'module_id'             => $moduleId,
                'week_date'             => $weekDate,
                'debt_backlog_size'     => $backlogSize,
                'debt_backlog_hours'    => $backlogHours,
                'debt_velocity'         => $debtVelocity,
                'debt_to_feature_ratio' => $ratio,
                'debt_age_distribution' => json_encode([
                    'under_30d'  => $under30,
                    '30_to_90d'  => $mid30to90,
                    'over_90d'   => $over90,
                ]),
                'health_score'          => $healthScore,
                'calculated_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Debt score failed for module', [
                'module_id' => $moduleId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function getHealthLabel(int $score): string
    {
        if ($score < 50) return 'Red';
        if ($score <= 70) return 'Yellow';
        return 'Green';
    }
}
