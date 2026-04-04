<?php

namespace App\Services;

use App\Repositories\DecisionRepository;

class DecisionService
{
    public function __construct(
        protected DecisionRepository $repository
    ) {}

    /**
     * BR-032: Create a new decision that supersedes an existing one.
     */
    public function supersedeDecision(int $oldDecisionId, array $newDecisionData): int
    {
        $newId = $this->repository->create($newDecisionData);
        $this->repository->supersede($oldDecisionId, $newId);

        return $newId;
    }
}
