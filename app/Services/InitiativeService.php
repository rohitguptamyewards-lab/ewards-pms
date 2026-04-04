<?php

namespace App\Services;

use App\Repositories\InitiativeRepository;

class InitiativeService
{
    public function __construct(
        protected InitiativeRepository $repository
    ) {}

    public function getInitiativeWithDetails(int $id): ?object
    {
        $initiative = $this->repository->findById($id);

        if (! $initiative) {
            return null;
        }

        $features = $this->repository->getFeatures($id);

        $initiative->features = $features;
        $initiative->feature_count = $features->count();
        $initiative->completed_feature_count = $features->where('status', 'released')->count();
        $initiative->progress = $initiative->feature_count > 0
            ? round(($initiative->completed_feature_count / $initiative->feature_count) * 100)
            : 0;

        $initiative->total_estimated_hours = $features->sum('estimated_hours');

        return $initiative;
    }
}
