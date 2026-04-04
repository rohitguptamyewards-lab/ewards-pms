<?php

namespace App\Services;

use App\Repositories\FeatureAssignmentRepository;
use Illuminate\Support\Facades\DB;

class FeatureAssignmentService
{
    public function __construct(
        private readonly FeatureAssignmentRepository $assignmentRepository,
    ) {}

    public function create(array $data): int
    {
        $data['state']       = 'assigned';
        $data['assigned_at'] = now();

        return $this->assignmentRepository->create($data);
    }

    public function complete(int $id): int
    {
        return $this->assignmentRepository->update($id, [
            'state'        => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function remove(int $id): int
    {
        return $this->assignmentRepository->update($id, [
            'state' => 'removed',
        ]);
    }

    /**
     * Auto-recalculate actual_hours from activity logs for a feature assignment.
     */
    public function syncActualHours(int $assignmentId): void
    {
        $assignment = $this->assignmentRepository->findById($assignmentId);
        if (!$assignment) return;

        $totalHours = DB::table('activity_logs')
            ->where('feature_id', $assignment->feature_id)
            ->where('team_member_id', $assignment->team_member_id)
            ->whereNull('deleted_at')
            ->get()
            ->sum(function ($log) {
                return match ($log->duration ?? '') {
                    '15min' => 0.25, '30min' => 0.5, '1h' => 1, '2h' => 2, '4h' => 4, '8h' => 8,
                    default => 0,
                };
            });

        $this->assignmentRepository->updateActualHours($assignmentId, (int) ceil($totalHours));
    }

    /**
     * Compare estimated vs actual hours per person. Returns accuracy metrics.
     */
    public function getEstimationAccuracy(int $memberId): array
    {
        $completed = DB::table('feature_assignments')
            ->where('team_member_id', $memberId)
            ->where('state', 'completed')
            ->where('estimated_hours', '>', 0)
            ->whereNull('deleted_at')
            ->get();

        if ($completed->isEmpty()) {
            return ['accuracy' => null, 'avg_deviation' => null, 'suggested_buffer' => 0];
        }

        $totalEstimated = $completed->sum('estimated_hours');
        $totalActual    = $completed->sum('actual_hours');
        $deviation       = $totalActual > 0 ? round((($totalActual - $totalEstimated) / $totalEstimated) * 100, 1) : 0;
        $suggestedBuffer = max(0, (int) ceil($deviation));

        return [
            'accuracy'         => round(100 - abs($deviation), 1),
            'avg_deviation'    => $deviation,
            'suggested_buffer' => $suggestedBuffer,
            'sample_size'      => $completed->count(),
        ];
    }
}
