<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DecisionRepository
{
    public function findAll(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('decisions')
            ->leftJoin('team_members', 'decisions.decision_maker_id', '=', 'team_members.id')
            ->leftJoin('decisions as superseder', 'decisions.superseded_by', '=', 'superseder.id')
            ->select(
                'decisions.*',
                'team_members.name as decision_maker_name',
                'superseder.title as superseded_by_title'
            )
            ->whereNull('decisions.deleted_at');

        if (! empty($filters['status'])) {
            $query->where('decisions.status', $filters['status']);
        }

        if (! empty($filters['linked_to_type'])) {
            $query->where('decisions.linked_to_type', $filters['linked_to_type']);
        }

        return $query->orderBy('decisions.decision_date', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('decisions')
            ->leftJoin('team_members', 'decisions.decision_maker_id', '=', 'team_members.id')
            ->leftJoin('decisions as superseder', 'decisions.superseded_by', '=', 'superseder.id')
            ->select(
                'decisions.*',
                'team_members.name as decision_maker_name',
                'superseder.title as superseded_by_title'
            )
            ->where('decisions.id', $id)
            ->whereNull('decisions.deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('decisions')->insertGetId($data);
    }

    /**
     * BR-032: Decisions are append-only — only status and superseded_by can be updated.
     */
    public function supersede(int $oldId, int $newId): void
    {
        DB::table('decisions')
            ->where('id', $oldId)
            ->update([
                'status' => 'superseded',
                'superseded_by' => $newId,
                'updated_at' => now(),
            ]);
    }

    public function updateStatus(int $id, string $status): int
    {
        return DB::table('decisions')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}
