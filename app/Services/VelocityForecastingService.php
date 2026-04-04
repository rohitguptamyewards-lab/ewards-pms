<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VelocityForecastingService
{
    /**
     * Get per-sprint velocity baseline from completed sprints.
     */
    public function getVelocityBaseline(): array
    {
        $sprints = DB::table('sprints')
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->orderByDesc('end_date')
            ->limit(10)
            ->get();

        if ($sprints->isEmpty()) {
            return ['avg_velocity' => 0, 'sprints_analysed' => 0, 'velocities' => []];
        }

        $velocities = [];
        foreach ($sprints as $sprint) {
            $delivered = DB::table('sprint_features')
                ->where('sprint_id', $sprint->id)
                ->where('carried_over', false)
                ->sum('committed_hours');
            $velocities[] = [
                'sprint_id'    => $sprint->id,
                'sprint_number' => $sprint->sprint_number,
                'delivered'    => (int) $delivered,
                'committed'    => $sprint->committed_hours ?? 0,
            ];
        }

        $avgVelocity = count($velocities) > 0
            ? round(array_sum(array_column($velocities, 'delivered')) / count($velocities), 1)
            : 0;

        return [
            'avg_velocity'     => $avgVelocity,
            'sprints_analysed' => count($velocities),
            'velocities'       => $velocities,
        ];
    }

    /**
     * Forecast initiative completion with confidence range.
     */
    public function forecastInitiativeCompletion(int $initiativeId): array
    {
        $features = DB::table('features')
            ->where('initiative_id', $initiativeId)
            ->whereNull('deleted_at')
            ->get();

        $totalEstimated  = $features->sum('estimated_hours');
        $completedHours  = $features->where('status', 'released')->sum('estimated_hours');
        $remainingHours  = $totalEstimated - $completedHours;

        $baseline = $this->getVelocityBaseline();
        $velocity = $baseline['avg_velocity'];

        if ($velocity <= 0) {
            return [
                'remaining_hours' => $remainingHours,
                'sprints_needed'  => null,
                'estimated_date'  => null,
                'confidence'      => 'low',
            ];
        }

        $sprintsNeeded = ceil($remainingHours / $velocity);
        $activeSprint = DB::table('sprints')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        $sprintLengthDays = 14;
        if ($activeSprint) {
            $sprintLengthDays = max(1, now()->parse($activeSprint->start_date)
                ->diffInDays(now()->parse($activeSprint->end_date)));
        }

        $estimatedDate = now()->addDays($sprintsNeeded * $sprintLengthDays)->toDateString();

        // Confidence range: optimistic (-20%), pessimistic (+30%)
        $optimistic  = now()->addDays((int) ($sprintsNeeded * 0.8 * $sprintLengthDays))->toDateString();
        $pessimistic = now()->addDays((int) ($sprintsNeeded * 1.3 * $sprintLengthDays))->toDateString();

        return [
            'remaining_hours' => $remainingHours,
            'sprints_needed'  => $sprintsNeeded,
            'estimated_date'  => $estimatedDate,
            'optimistic_date' => $optimistic,
            'pessimistic_date' => $pessimistic,
            'confidence'      => $baseline['sprints_analysed'] >= 5 ? 'high' : 'medium',
        ];
    }

    /**
     * Scope creep tracking: compare current feature count/hours vs initial plan.
     */
    public function getScopeCreep(int $initiativeId): array
    {
        $features = DB::table('features')
            ->where('initiative_id', $initiativeId)
            ->whereNull('deleted_at')
            ->get();

        $totalFeatures    = $features->count();
        $totalEstimated   = $features->sum('estimated_hours');

        // Scope additions are features created after the initiative was created
        $initiative = DB::table('initiatives')->where('id', $initiativeId)->first();
        $initiativeCreated = $initiative->created_at ?? '1970-01-01';
        $originalFeatures = $features->where('created_at', '<=', $initiativeCreated)->count();
        $addedFeatures    = $features->where('created_at', '>', $initiativeCreated)->count();

        // If no features existed at creation time, treat all as original
        if ($originalFeatures === 0) {
            $originalFeatures = $totalFeatures;
            $addedFeatures = 0;
        }

        return [
            'total_features'    => $totalFeatures,
            'original_features' => $originalFeatures,
            'added_features'    => $addedFeatures,
            'scope_increase'    => $originalFeatures > 0
                ? round(($addedFeatures / $originalFeatures) * 100, 1)
                : 0,
            'total_hours'       => (int) $totalEstimated,
        ];
    }
}
