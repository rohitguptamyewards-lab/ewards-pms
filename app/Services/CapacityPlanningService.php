<?php

namespace App\Services;

use App\Repositories\FeatureAssignmentRepository;
use App\Repositories\LeaveEntryRepository;
use Illuminate\Support\Facades\DB;

class CapacityPlanningService
{
    public function __construct(
        private readonly FeatureAssignmentRepository $assignmentRepository,
        private readonly LeaveEntryRepository $leaveEntryRepository,
    ) {}

    /**
     * Get capacity planning data for all team members.
     */
    public function getCapacityOverview(string $startDate, string $endDate): array
    {
        $members = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($members as $member) {
            $weeklyCapacity  = $member->weekly_capacity ?? 40;
            $weeks           = max(1, now()->parse($startDate)->diffInWeeks(now()->parse($endDate)));
            $totalCapacity   = $weeklyCapacity * $weeks;

            // Subtract leave hours
            $leaveHours = $this->leaveEntryRepository->countLeaveHours(
                $member->id, $startDate, $endDate
            );
            $adjustedCapacity = max(0, $totalCapacity - $leaveHours);

            // Sum allocated hours from active assignments
            $allocatedHours = $this->assignmentRepository->sumAllocatedHours($member->id);

            $available   = max(0, $adjustedCapacity - $allocatedHours);
            $utilisation = $adjustedCapacity > 0
                ? round(($allocatedHours / $adjustedCapacity) * 100, 1)
                : 0;

            $result[] = [
                'member_id'            => $member->id,
                'member_name'          => $member->name,
                'role'                 => $member->role,
                'weekly_capacity'      => $weeklyCapacity,
                'total_capacity'       => $totalCapacity,
                'leave_hours'          => $leaveHours,
                'adjusted_capacity'    => $adjustedCapacity,
                'allocated_hours'      => $allocatedHours,
                'available_hours'      => $available,
                'utilisation_percentage' => $utilisation,
                'is_overloaded'        => $allocatedHours > $adjustedCapacity,
            ];
        }

        return $result;
    }
}
