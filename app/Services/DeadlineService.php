<?php

namespace App\Services;

use App\Repositories\DeadlineRepository;

class DeadlineService
{
    public function __construct(
        private readonly DeadlineRepository $deadlineRepository,
    ) {}

    public function create(array $data): int
    {
        $data['state'] = $data['state'] ?? 'on_track';
        return $this->deadlineRepository->create($data);
    }

    /**
     * Cascade deadline change: when a parent deadline slips,
     * shift all children by the same amount.
     */
    public function cascadeDeadlineChange(int $deadlineId, string $newDueDate): void
    {
        $deadline = $this->deadlineRepository->findById($deadlineId);
        if (!$deadline) {
            return;
        }

        $originalDue = new \DateTime($deadline->due_date);
        $newDue = new \DateTime($newDueDate);
        $diff = $originalDue->diff($newDue);
        $days = $diff->days * ($diff->invert ? -1 : 1);

        // Update the parent deadline
        $this->deadlineRepository->update($deadlineId, [
            'due_date' => $newDueDate,
            'state'    => $this->calculateState($newDueDate),
        ]);

        // Cascade to children
        $children = $this->deadlineRepository->findCascadeChildren($deadlineId);
        foreach ($children as $child) {
            $childDue = (new \DateTime($child->due_date))->modify("{$days} days")->format('Y-m-d');
            $this->cascadeDeadlineChange($child->id, $childDue);
        }
    }

    /**
     * Evaluate all deadline states based on current date.
     */
    public function evaluateStates(): array
    {
        $updated = [];

        // Mark overdue deadlines
        $overdue = $this->deadlineRepository->findOverdue();
        foreach ($overdue as $d) {
            $this->deadlineRepository->update($d->id, ['state' => 'overdue']);
            $updated[] = $d->id;
        }

        // Mark at-risk (within 3 days)
        $upcoming = $this->deadlineRepository->findUpcoming(3);
        foreach ($upcoming as $d) {
            if (!in_array($d->id, $updated) && $d->state === 'on_track') {
                $this->deadlineRepository->update($d->id, ['state' => 'at_risk']);
                $updated[] = $d->id;
            }
        }

        return $updated;
    }

    private function calculateState(string $dueDate): string
    {
        $due = new \DateTime($dueDate);
        $today = new \DateTime('today');
        $diff = $today->diff($due);
        $daysRemaining = $diff->days * ($diff->invert ? -1 : 1);

        if ($daysRemaining < 0) {
            return 'overdue';
        }
        if ($daysRemaining <= 3) {
            return 'at_risk';
        }
        return 'on_track';
    }
}
