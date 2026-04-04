<?php

namespace App\Services;

use App\Repositories\AiUsageLogRepository;

class AiUsageLogService
{
    public function __construct(
        private readonly AiUsageLogRepository $aiUsageLogRepository,
    ) {}

    public function create(array $data): int
    {
        return $this->aiUsageLogRepository->create($data);
    }

    /**
     * Get effectiveness matrix: counts by capability x outcome.
     */
    public function getEffectivenessMatrix(array $filters = []): array
    {
        return $this->aiUsageLogRepository->getEffectivenessMatrix();
    }

    /**
     * Get AI knowledge base items (helpful/partially helpful notes).
     */
    public function getKnowledgeBase(array $filters = []): array
    {
        return $this->aiUsageLogRepository->getKnowledgeBaseItems();
    }

    /**
     * Calculate AI ROI: estimated time saved vs tool cost.
     */
    public function calculateRoi(): array
    {
        $logs = $this->aiUsageLogRepository->findAll(['per_page' => 10000])->items();

        $totalTimeSaved = 0;
        foreach ($logs as $log) {
            $totalTimeSaved += match ($log->time_saved ?? '') {
                'same'          => 0,
                '30min'         => 0.5,
                '1_to_2h'       => 1.5,
                'half_day_plus' => 4,
                'cost_more'     => -1,
                default         => 0,
            };
        }

        $tools = \Illuminate\Support\Facades\DB::table('ai_tools')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->get();

        $totalMonthlyCost = $tools->sum(fn ($t) => $t->cost_per_seat_monthly * $t->seats_purchased);
        $avgHourlyCost    = 50; // fallback if no cost rates available

        $timeSavedValue = $totalTimeSaved * $avgHourlyCost;

        return [
            'total_time_saved_hours' => round($totalTimeSaved, 1),
            'total_tool_cost'        => round($totalMonthlyCost, 2),
            'estimated_value_saved'  => round($timeSavedValue, 2),
            'roi_ratio'              => $totalMonthlyCost > 0 ? round($timeSavedValue / $totalMonthlyCost, 2) : 0,
        ];
    }
}
