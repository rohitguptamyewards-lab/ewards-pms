<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class WorkJournalRepository
{
    public function findAll(int $teamMemberId, array $filters = [], int $perPage = 15)
    {
        $query = DB::table('work_journals')
            ->where('team_member_id', $teamMemberId)
            ->whereNull('deleted_at');

        if (!empty($filters['mood'])) {
            $query->where('mood', $filters['mood']);
        }

        if (!empty($filters['from'])) {
            $query->where('entry_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('entry_date', '<=', $filters['to']);
        }

        return $query->orderByDesc('entry_date')->paginate($perPage);
    }

    public function findByDate(int $teamMemberId, string $date): ?stdClass
    {
        return DB::table('work_journals')
            ->where('team_member_id', $teamMemberId)
            ->where('entry_date', $date)
            ->whereNull('deleted_at')
            ->first();
    }

    public function findById(int $id): ?stdClass
    {
        return DB::table('work_journals')
            ->leftJoin('team_members', 'work_journals.team_member_id', '=', 'team_members.id')
            ->select('work_journals.*', 'team_members.name as member_name')
            ->where('work_journals.id', $id)
            ->whereNull('work_journals.deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();
        return DB::table('work_journals')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();
        return DB::table('work_journals')->where('id', $id)->update($data) > 0;
    }

    /**
     * Get mood distribution for a team member over a date range.
     */
    public function moodDistribution(int $teamMemberId, string $from, string $to): array
    {
        return DB::table('work_journals')
            ->select('mood', DB::raw('COUNT(*) as count'))
            ->where('team_member_id', $teamMemberId)
            ->whereBetween('entry_date', [$from, $to])
            ->whereNull('deleted_at')
            ->whereNotNull('mood')
            ->groupBy('mood')
            ->get()
            ->toArray();
    }

    /**
     * Get streak info — consecutive days with journal entries.
     */
    public function currentStreak(int $teamMemberId): int
    {
        $entries = DB::table('work_journals')
            ->where('team_member_id', $teamMemberId)
            ->whereNull('deleted_at')
            ->orderByDesc('entry_date')
            ->limit(90)
            ->pluck('entry_date')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        if (empty($entries)) return 0;

        $streak = 0;
        $check  = \Carbon\Carbon::today();

        // If today's entry doesn't exist, start from yesterday
        if ($entries[0] !== $check->format('Y-m-d')) {
            $check = $check->subDay();
        }

        foreach ($entries as $date) {
            if ($date === $check->format('Y-m-d')) {
                $streak++;
                $check = $check->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get entries for a team visible to managers (non-private only).
     */
    public function findTeamEntries(array $memberIds, string $date): array
    {
        return DB::table('work_journals')
            ->leftJoin('team_members', 'work_journals.team_member_id', '=', 'team_members.id')
            ->select('work_journals.*', 'team_members.name as member_name')
            ->whereIn('work_journals.team_member_id', $memberIds)
            ->where('work_journals.entry_date', $date)
            ->where('work_journals.is_private', false)
            ->whereNull('work_journals.deleted_at')
            ->orderBy('team_members.name')
            ->get()
            ->toArray();
    }
}
