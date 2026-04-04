<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class InitiativeRepository
{
    public function findAll(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('initiatives')
            ->leftJoin('team_members', 'initiatives.owner_id', '=', 'team_members.id')
            ->leftJoin('modules', 'initiatives.module_id', '=', 'modules.id')
            ->select(
                'initiatives.*',
                'team_members.name as owner_name',
                'modules.name as module_name',
                DB::raw('(SELECT COUNT(*) FROM features WHERE features.initiative_id = initiatives.id AND features.deleted_at IS NULL) as feature_count'),
                DB::raw('(SELECT COUNT(*) FROM features WHERE features.initiative_id = initiatives.id AND features.status = \'released\' AND features.deleted_at IS NULL) as completed_feature_count')
            )
            ->whereNull('initiatives.deleted_at');

        if (! empty($filters['status'])) {
            $query->where('initiatives.status', $filters['status']);
        }

        if (! empty($filters['origin_type'])) {
            $query->where('initiatives.origin_type', $filters['origin_type']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('initiatives.owner_id', $filters['owner_id']);
        }

        if (! empty($filters['module_id'])) {
            $query->where('initiatives.module_id', $filters['module_id']);
        }

        return $query->orderBy('initiatives.created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('initiatives')
            ->leftJoin('team_members', 'initiatives.owner_id', '=', 'team_members.id')
            ->leftJoin('modules', 'initiatives.module_id', '=', 'modules.id')
            ->select(
                'initiatives.*',
                'team_members.name as owner_name',
                'modules.name as module_name'
            )
            ->where('initiatives.id', $id)
            ->whereNull('initiatives.deleted_at')
            ->first();
    }

    public function getFeatures(int $initiativeId)
    {
        return DB::table('features')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select('features.*', 'team_members.name as assignee_name')
            ->where('features.initiative_id', $initiativeId)
            ->whereNull('features.deleted_at')
            ->orderBy('features.priority')
            ->get();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('initiatives')->insertGetId($data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = now();

        return DB::table('initiatives')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data);
    }

    public function countByStatus(): array
    {
        return DB::table('initiatives')
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
