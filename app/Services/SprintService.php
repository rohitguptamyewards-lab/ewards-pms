<?php

namespace App\Services;

use App\Repositories\SprintRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SprintService
{
    public function __construct(
        private readonly SprintRepository $sprintRepository,
    ) {}

    public function create(array $data): int
    {
        // Auto-assign sprint number
        $data['sprint_number'] = $this->sprintRepository->getNextSprintNumber();
        $data['status'] = $data['status'] ?? 'planning';

        return $this->sprintRepository->create($data);
    }

    public function activate(int $sprintId): void
    {
        // BR-010: Only one sprint can be active at a time
        $active = $this->sprintRepository->getActiveSprint();
        if ($active && $active->id !== $sprintId) {
            throw new InvalidArgumentException('Another sprint is already active. Complete it first.');
        }

        $this->sprintRepository->update($sprintId, ['status' => 'active']);
    }

    public function complete(int $sprintId): void
    {
        $sprint = $this->sprintRepository->findByIdWithFeatures($sprintId);
        if (!$sprint) {
            throw new InvalidArgumentException('Sprint not found.');
        }

        // Mark incomplete features as carried over
        foreach ($sprint->features as $sf) {
            if (!in_array($sf->feature_status, ['released', 'deferred', 'rejected'])) {
                DB::table('sprint_features')
                    ->where('sprint_id', $sprintId)
                    ->where('feature_id', $sf->feature_id)
                    ->update([
                        'carried_over'      => true,
                        'carry_over_reason' => $sf->carry_over_reason ?? 'deprioritised',
                        'updated_at'        => now(),
                    ]);
            }
        }

        $this->sprintRepository->update($sprintId, ['status' => 'completed']);
    }

    public function addFeature(int $sprintId, array $data): int
    {
        $sprint = $this->sprintRepository->findById($sprintId);
        if (!$sprint) {
            throw new InvalidArgumentException('Sprint not found.');
        }

        // BR-011: Cannot add features to completed/archived sprints
        if (in_array($sprint->status, ['completed', 'archived'])) {
            throw new InvalidArgumentException('Cannot add features to a completed or archived sprint.');
        }

        // Update committed hours
        $committedHours = ($data['committed_hours'] ?? 0) + $sprint->committed_hours;
        $this->sprintRepository->update($sprintId, ['committed_hours' => $committedHours]);

        return $this->sprintRepository->addFeature($sprintId, $data);
    }

    public function calculateHealthScore(int $sprintId): array
    {
        $sprint = $this->sprintRepository->findByIdWithFeatures($sprintId);
        if (!$sprint) {
            return ['score' => 0, 'label' => 'Unknown'];
        }

        $totalFeatures = count($sprint->features);
        if ($totalFeatures === 0) {
            return ['score' => 100, 'label' => 'Healthy'];
        }

        $delivered = $sprint->delivered_count;
        $carryOver = $sprint->carry_over_count;
        $blocked = collect($sprint->features)->filter(fn ($f) => $f->feature_status === 'blocked')->count();

        $score = round(($delivered / $totalFeatures) * 100 - ($blocked * 10) - ($carryOver * 5));
        $score = max(0, min(100, $score));

        $label = match (true) {
            $score >= 80 => 'Healthy',
            $score >= 60 => 'At Risk',
            default      => 'Unhealthy',
        };

        return ['score' => $score, 'label' => $label];
    }
}
