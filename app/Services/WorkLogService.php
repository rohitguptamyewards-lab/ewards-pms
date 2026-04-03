<?php

namespace App\Services;

use App\Repositories\WorkLogRepository;
use InvalidArgumentException;

class WorkLogService
{
    public function __construct(
        private readonly WorkLogRepository $workLogRepository,
    ) {}

    /**
     * Create a work log entry with validation.
     *
     * If the associated task is blocked, the blocker field must not be empty.
     *
     * @param array $data
     * @return int The new work log ID.
     *
     * @throws InvalidArgumentException If a blocked task log is missing the blocker field.
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

        // Remove non-column keys before passing to repository
        unset($data['task_status']);

        return $this->workLogRepository->create($data);
    }

    /**
     * Get total hours logged by a user on a specific date.
     *
     * @param int    $userId
     * @param string $date Date in Y-m-d format
     * @return float
     */
    public function getDailyTotals(int $userId, string $date): float
    {
        return $this->workLogRepository->sumHoursForUser($userId, $date, $date);
    }
}
