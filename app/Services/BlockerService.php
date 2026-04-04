<?php

namespace App\Services;

use App\Repositories\BlockerRepository;

class BlockerService
{
    public function __construct(
        private readonly BlockerRepository $blockerRepository,
    ) {}

    public function create(array $data): int
    {
        $data['status'] = 'active';
        return $this->blockerRepository->create($data);
    }

    public function resolve(int $id, int $resolvedBy, string $resolutionNote): void
    {
        $this->blockerRepository->update($id, [
            'status'          => 'resolved',
            'resolved_at'     => now(),
            'resolved_by'     => $resolvedBy,
            'resolution_note' => $resolutionNote,
        ]);
    }
}
