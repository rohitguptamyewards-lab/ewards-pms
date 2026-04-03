<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {}

    /**
     * Get a project with full stats: progress, effort, members, and task counts.
     *
     * @param int $id
     * @return array
     *
     * @throws \InvalidArgumentException If project not found.
     */
    public function getProjectWithStats(int $id): array
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw new \InvalidArgumentException("Project #{$id} not found.");
        }

        $progress = $this->projectRepository->calculateProgress($id);
        $effort = $this->projectRepository->calculateEffort($id);
        $members = $this->projectRepository->getMembers($id);

        return [
            'project'  => $project,
            'progress' => $progress,
            'effort'   => $effort,
            'members'  => $members,
        ];
    }
}
