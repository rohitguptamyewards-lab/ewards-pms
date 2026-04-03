<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {}

    /**
     * Assemble the individual dashboard payload for a user.
     *
     * @param int $userId
     * @return array
     */
    public function assembleIndividual(int $userId): array
    {
        return $this->dashboardRepository->getIndividualData($userId);
    }

    /**
     * Assemble the manager dashboard payload.
     *
     * @return array
     */
    public function assembleManager(): array
    {
        return $this->dashboardRepository->getManagerData();
    }

    public function assembleCEO(): array
    {
        return $this->dashboardRepository->getCEOData();
    }

    public function assembleMCTeam(): array
    {
        return $this->dashboardRepository->getMCTeamData();
    }

    public function assembleSales(int $userId): array
    {
        return $this->dashboardRepository->getSalesData($userId);
    }
}
