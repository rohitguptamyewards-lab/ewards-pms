<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class FeatureRepository
{
    /**
     * Get paginated list of features with linked request count.
     *
     * @param array $filters Supported: status, module_id, priority, assigned_to
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select(
                'features.*',
                'modules.name as module_name',
                'team_members.name as assignee_name',
                DB::raw('(SELECT COUNT(*) FROM requests WHERE requests.linked_feature_id = features.id AND requests.deleted_at IS NULL) as request_count')
            )
            ->whereNull('features.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('features.status', $filters['status']);
        }

        if (!empty($filters['module_id'])) {
            $query->where('features.module_id', $filters['module_id']);
        }

        if (!empty($filters['priority'])) {
            $query->where('features.priority', $filters['priority']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('features.assigned_to', $filters['assigned_to']);
        }

        return $query->orderByDesc('features.created_at')->paginate($perPage);
    }

    /**
     * Find a single feature by ID with linked requests.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        $feature = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select(
                'features.*',
                'modules.name as module_name',
                'team_members.name as assignee_name'
            )
            ->where('features.id', $id)
            ->whereNull('features.deleted_at')
            ->first();

        if (!$feature) {
            return null;
        }

        $feature->linked_requests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->select('requests.id', 'requests.title', 'requests.status', 'requests.demand_count', 'merchants.name as merchant_name')
            ->where('requests.linked_feature_id', $id)
            ->whereNull('requests.deleted_at')
            ->get()
            ->toArray();

        return $feature;
    }

    /**
     * Insert a new feature and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('features')->insertGetId($data);
    }

    /**
     * Update a feature by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('features')
            ->where('id', $id)
            ->update($data) > 0;
    }
}
