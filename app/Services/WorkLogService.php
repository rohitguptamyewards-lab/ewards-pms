<?php

namespace App\Services;

use App\Repositories\WorkLogRepository;
use Carbon\Carbon;
use InvalidArgumentException;

class WorkLogService
{
    public function __construct(
        private readonly WorkLogRepository $workLogRepository,
    ) {}

    /**
     * Create a work log entry with validation.
     *
     * Calculates hours_spent from start_time and end_time.
     */
    public function create(array $data): int
    {
        // If a task status indicates blocked, require the blocker field
        if (!empty($data['task_status']) && $data['task_status'] === 'blocked') {
            if (empty($data['blocker'])) {
                throw new InvalidArgumentException(
                    'A blocker description is required when the task is blocked.'
                );
            }
        }

        // Calculate hours_spent from start_time and end_time
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $data['start_time']);
            $end = Carbon::createFromFormat('H:i', $data['end_time']);
            $data['hours_spent'] = round($end->diffInMinutes($start) / 60, 2);
        }

        // Remove non-column keys before passing to repository
        unset($data['task_status']);

        return $this->workLogRepository->create($data);
    }

    /**
     * Get the last work log's end_time for a user on a given date.
     */
    public function getLastEndTime(int $userId, string $date): ?string
    {
        return $this->workLogRepository->getLastEndTime($userId, $date);
    }

    /**
     * Get total hours logged by a user on a specific date.
     */
    public function getDailyTotals(int $userId, string $date): float
    {
        return $this->workLogRepository->sumHoursForUser($userId, $date, $date);
    }
}
