<?php

namespace App\Services;

use App\Repositories\MerchantCommunicationRepository;

class MerchantCommunicationService
{
    public function __construct(
        private readonly MerchantCommunicationRepository $merchantCommRepo,
    ) {}

    public function create(array $data): int
    {
        return $this->merchantCommRepo->create($data);
    }

    /**
     * Find communications where a commitment was made but the linked
     * feature has slipped past the commitment date.
     */
    public function findSlippedCommitments(): array
    {
        return $this->merchantCommRepo->findWithSlippedFeatures();
    }
}
