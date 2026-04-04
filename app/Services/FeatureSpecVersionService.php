<?php

namespace App\Services;

use App\Repositories\FeatureSpecVersionRepository;
use InvalidArgumentException;

class FeatureSpecVersionService
{
    public function __construct(
        private readonly FeatureSpecVersionRepository $specRepo,
    ) {}

    public function create(array $data): int
    {
        $data['version_number'] = $this->specRepo->getNextVersionNumber($data['feature_id']);
        $data['state'] = $data['state'] ?? 'draft';

        // BR-050: Supersede previous version if exists
        $latest = $this->specRepo->getLatestVersion($data['feature_id']);
        if ($latest && $latest->state !== 'superseded') {
            $this->specRepo->update($latest->id, ['state' => 'superseded']);
        }

        return $this->specRepo->create($data);
    }

    /**
     * BR-051: Freeze a spec version — locks the content.
     */
    public function freeze(int $id): void
    {
        $spec = $this->specRepo->findById($id);
        if (!$spec) {
            throw new InvalidArgumentException('Spec version not found.');
        }

        if ($spec->state !== 'draft' && $spec->state !== 'under_review') {
            throw new InvalidArgumentException('Only draft or under-review specs can be frozen.');
        }

        $this->specRepo->update($id, ['state' => 'frozen']);
    }

    /**
     * BR-008: Developer acknowledges a spec update.
     */
    public function acknowledge(int $id, int $teamMemberId): void
    {
        $spec = $this->specRepo->findById($id);
        if (!$spec) {
            throw new InvalidArgumentException('Spec version not found.');
        }

        $this->specRepo->update($id, [
            'acknowledged_by' => $teamMemberId,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * BR-052: Move to under review.
     */
    public function submitForReview(int $id): void
    {
        $spec = $this->specRepo->findById($id);
        if (!$spec) {
            throw new InvalidArgumentException('Spec version not found.');
        }

        if ($spec->state !== 'draft') {
            throw new InvalidArgumentException('Only draft specs can be submitted for review.');
        }

        $this->specRepo->update($id, ['state' => 'under_review']);
    }

    /**
     * BR-007: Check if feature can go In Progress (spec must be frozen).
     */
    public function isSpecFrozen(int $featureId): bool
    {
        $latest = $this->specRepo->getLatestVersion($featureId);
        return $latest && $latest->state === 'frozen';
    }
}
