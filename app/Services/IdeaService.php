<?php

namespace App\Services;

use App\Repositories\IdeaRepository;
use Illuminate\Support\Facades\DB;

class IdeaService
{
    public function __construct(
        protected IdeaRepository $repository
    ) {}

    /**
     * Promote an idea to a Feature or Initiative.
     */
    public function promote(int $ideaId, string $targetType, int $targetId): void
    {
        $this->repository->update($ideaId, [
            'status'           => 'promoted',
            'promoted_to_type' => $targetType,
            'promoted_to_id'   => $targetId,
        ]);
    }

    /**
     * BR-006: Auto-archive ideas older than 90 days with no activity.
     */
    public function archiveStaleIdeas(): int
    {
        return $this->repository->archiveStale();
    }
}
