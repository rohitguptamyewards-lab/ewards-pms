<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class LeaveEntryRepository
{
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('leave_entries')
            ->join('team_members', 'leave_entries.team_member_id', '=', 'team_members.id')
            ->select(
                'leave_entries.*',
                'team_members.name as member_name'
            )
            ->whereNull('leave_entries.deleted_at');

        if (!empty($filters['team_member_id'])) {
            $query->where('leave_entries.team_member_id', $filters['team_member_id']);
        }
        if (!empty($filters['leave_type'])) {
            $query->where('leave_entries.leave_type', $filters['leave_type']);
        }
        if (!empty($filters['month'])) {
            $query->whereRaw("TO_CHAR(leave_entries.leave_date, 'YYYY-MM') = ?", [$filters['month']]);
        }

        return $query->orderByDesc('leave_entries.leave_date')->paginate($perPage);
    }

    public function findByMember(int $memberId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = DB::table('leave_entries')
            ->where('team_member_id', $memberId)
            ->whereNull('deleted_at');

        if ($startDate) {
            $query->where('leave_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('leave_date', '<=', $endDate);
        }

        return $query->orderBy('leave_date')->get()->toArray();
    }

    public function findById(int $id): ?stdClass
    {
        return DB::table('leave_entries')
            ->join('team_members', 'leave_entries.team_member_id', '=', 'team_members.id')
            ->select(
                'leave_entries.*',
                'team_members.name as member_name'
            )
            ->where('leave_entries.id', $id)
            ->whereNull('leave_entries.deleted_at')
            ->first() ?: null;
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('leave_entries')->insertGetId($data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = now();

        return DB::table('leave_entries')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data);
    }

    public function delete(int $id): int
    {
        return DB::table('leave_entries')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function countLeaveHours(int $memberId, string $startDate, string $endDate): float
    {
        $leaves = DB::table('leave_entries')
            ->where('team_member_id', $memberId)
            ->whereBetween('leave_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->get();

        $hours = 0;
        foreach ($leaves as $leave) {
            $hours += $leave->half_day ? 4 : 8;
        }

        return $hours;
    }
}
