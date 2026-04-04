<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class CostRateRepository
{
    /**
     * Get all cost rates for a team member, ordered by effective_from desc.
     *
     * @param int $memberId
     * @return array
     */
    public function findByMember(int $memberId): array
    {
        return DB::table('cost_rates')
            ->join('team_members', 'cost_rates.team_member_id', '=', 'team_members.id')
            ->select(
                'cost_rates.*',
                'team_members.name as member_name'
            )
            ->where('cost_rates.team_member_id', $memberId)
            ->orderByDesc('cost_rates.effective_from')
            ->get()
            ->toArray();
    }

    /**
     * Find the cost rate active at a given date for a team member.
     * Active means: effective_from <= date AND (effective_to IS NULL OR effective_to >= date).
     *
     * @param int    $memberId
     * @param string $date
     * @return stdClass|null
     */
    public function findActive(int $memberId, string $date): ?stdClass
    {
        return DB::table('cost_rates')
            ->join('team_members', 'cost_rates.team_member_id', '=', 'team_members.id')
            ->select(
                'cost_rates.*',
                'team_members.name as member_name'
            )
            ->where('cost_rates.team_member_id', $memberId)
            ->where('cost_rates.effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('cost_rates.effective_to')
                  ->orWhere('cost_rates.effective_to', '>=', $date);
            })
            ->orderByDesc('cost_rates.effective_from')
            ->first() ?: null;
    }

    /**
     * Get paginated cost rates with optional filters. Joins team_members for name.
     *
     * @param array $filters Supported: team_member_id
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('cost_rates')
            ->join('team_members', 'cost_rates.team_member_id', '=', 'team_members.id')
            ->select(
                'cost_rates.*',
                'team_members.name as member_name'
            );

        if (!empty($filters['team_member_id'])) {
            $query->where('cost_rates.team_member_id', $filters['team_member_id']);
        }

        return $query->orderByDesc('cost_rates.effective_from')->paginate($perPage);
    }

    /**
     * Create a new cost rate. Before inserting, closes any previous active rate
     * for the same team_member_id by setting its effective_to to one day before
     * the new rate's effective_from.
     *
     * @param array $data
     * @return int  The new cost_rate ID
     */
    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $newEffectiveFrom = $data['effective_from'];

            // Close the previous active rate if one exists for this member
            $previousRate = DB::table('cost_rates')
                ->where('team_member_id', $data['team_member_id'])
                ->where('effective_from', '<', $newEffectiveFrom)
                ->where(function ($q) use ($newEffectiveFrom) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $newEffectiveFrom);
                })
                ->orderByDesc('effective_from')
                ->first();

            if ($previousRate) {
                $closeDate = date('Y-m-d', strtotime($newEffectiveFrom . ' -1 day'));
                DB::table('cost_rates')
                    ->where('id', $previousRate->id)
                    ->update(['effective_to' => $closeDate]);
            }

            $data['created_at'] = now();
            $data['updated_at'] = now();

            return DB::table('cost_rates')->insertGetId($data);
        });
    }

    /**
     * Find a single cost rate by ID, joined with team_members for name.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        return DB::table('cost_rates')
            ->join('team_members', 'cost_rates.team_member_id', '=', 'team_members.id')
            ->select(
                'cost_rates.*',
                'team_members.name as member_name'
            )
            ->where('cost_rates.id', $id)
            ->first() ?: null;
    }
}
