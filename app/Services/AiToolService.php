<?php

namespace App\Services;

use App\Repositories\AiToolRepository;
use App\Repositories\AiToolAssignmentRepository;

class AiToolService
{
    public function __construct(
        private readonly AiToolRepository $aiToolRepository,
        private readonly AiToolAssignmentRepository $assignmentRepository,
    ) {}

    public function create(array $data): int
    {
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $data['capabilities'] = json_encode($data['capabilities']);
        }

        return $this->aiToolRepository->create($data);
    }

    public function update(int $id, array $data): int
    {
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $data['capabilities'] = json_encode($data['capabilities']);
        }

        return $this->aiToolRepository->update($id, $data);
    }

    public function assignToMember(int $toolId, int $memberId): int
    {
        if ($this->assignmentRepository->isAssigned($toolId, $memberId)) {
            return 0;
        }

        return $this->assignmentRepository->assign($toolId, $memberId);
    }

    public function revokeFromMember(int $toolId, int $memberId): int
    {
        return $this->assignmentRepository->revoke($toolId, $memberId);
    }
}
