<?php

namespace App\Services;

use App\Repositories\ReportRepository;

class ReportService
{
    public function __construct(
        private readonly ReportRepository $reportRepository,
    ) {}

    /**
     * Generate a work log report filtered by user, project, and/or date range.
     *
     * @param array $filters Supported: user_id, project_id, from, to
     * @return array
     */
    public function generateWorkLogReport(array $filters): array
    {
        return $this->reportRepository->getWorkLogReport($filters);
    }

    /**
     * Generate a detailed report for a single project.
     *
     * @param int $projectId
     * @return array
     */
    public function generateProjectReport(int $projectId): array
    {
        return $this->reportRepository->getProjectReport($projectId);
    }

    /**
     * Generate an individual contributor report.
     *
     * @param int         $userId
     * @param string|null $from
     * @param string|null $to
     * @return array
     */
    public function generateIndividualReport(int $userId, ?string $from = null, ?string $to = null): array
    {
        return $this->reportRepository->getIndividualReport($userId, $from, $to);
    }
}
