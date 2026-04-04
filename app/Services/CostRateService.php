<?php

namespace App\Services;

use App\Repositories\CostRateRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CostRateService
{
    public function __construct(
        private readonly CostRateRepository $costRateRepository,
    ) {}

    /**
     * Create a new cost rate (append-only, BR-020).
     * Auto-calculates loaded_hourly_rate = ctc / hours * multiplier.
     * Contractor flag on team_member overrides overhead_multiplier to 1.0.
     */
    public function create(array $data): int
    {
        $memberId = $data['team_member_id'];
        $member = DB::table('team_members')->where('id', $memberId)->first();

        $ctc   = (float) $data['monthly_ctc'];
        $hours = (int) ($data['working_hours_per_month'] ?? 168);

        // Contractors get 1.0 multiplier (BR-022)
        $multiplier = (!empty($member->contractor_flag) && $member->contractor_flag)
            ? 1.0
            : (float) ($data['overhead_multiplier'] ?? 1.3);

        $loadedRate = $hours > 0 ? round(($ctc / $hours) * $multiplier, 2) : 0;

        $insertData = [
            'team_member_id'         => $memberId,
            'monthly_ctc'            => Crypt::encryptString((string) $ctc),
            'working_hours_per_month' => $hours,
            'overhead_multiplier'    => $multiplier,
            'loaded_hourly_rate'     => Crypt::encryptString((string) $loadedRate),
            'effective_from'         => $data['effective_from'],
            'effective_to'           => $data['effective_to'] ?? null,
            'key_version'            => $data['key_version'] ?? 1,
            'created_by'             => $data['created_by'],
        ];

        return $this->costRateRepository->create($insertData);
    }
}
