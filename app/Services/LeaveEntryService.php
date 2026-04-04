<?php

namespace App\Services;

use App\Repositories\LeaveEntryRepository;

class LeaveEntryService
{
    public function __construct(
        private readonly LeaveEntryRepository $leaveEntryRepository,
    ) {}

    public function create(array $data): int
    {
        return $this->leaveEntryRepository->create($data);
    }

    public function update(int $id, array $data): int
    {
        return $this->leaveEntryRepository->update($id, $data);
    }

    public function delete(int $id): int
    {
        return $this->leaveEntryRepository->delete($id);
    }
}
