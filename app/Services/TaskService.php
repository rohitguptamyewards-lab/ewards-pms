<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Repositories\TaskRepository;
use InvalidArgumentException;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {}

    /**
     * Validate whether a status transition is allowed.
     *
     * @param string $from Current status value
     * @param string $to   Target status value
     * @return bool
     */
    public function validateStatusTransition(string $from, string $to): bool
    {
        $fromEnum = TaskStatus::tryFrom($from);
        $toEnum = TaskStatus::tryFrom($to);

        if (!$fromEnum || !$toEnum) {
            return false;
        }

        $allowed = $fromEnum->allowedTransitions();

        return in_array($toEnum, $allowed, true);
    }

    /**
     * Change the status of a task with transition validation.
     *
     * @param int    $taskId
     * @param string $newStatus
     * @return void
     *
     * @throws InvalidArgumentException If the task is not found or the transition is invalid.
     */
    public function changeStatus(int $taskId, string $newStatus): void
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new InvalidArgumentException("Task #{$taskId} not found.");
        }

        if (!$this->validateStatusTransition($task->status, $newStatus)) {
            throw new InvalidArgumentException(
                "Invalid status transition from '{$task->status}' to '{$newStatus}'."
            );
        }

        $this->taskRepository->update($taskId, [
            'status' => $newStatus,
        ]);
    }
}
