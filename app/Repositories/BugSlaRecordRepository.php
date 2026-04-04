<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class BugSlaRecordRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('bug_sla_records')
            ->leftJoin('features', 'bug_sla_records.feature_id', '=', 'features.id')
            ->select(
                'bug_sla_records.*',
                'features.title as feature_title',
                'features.status as feature_status'
            )
            ->whereNull('bug_sla_records.deleted_at');

        if (!empty($filters['severity'])) {
            $query->where('bug_sla_records.severity', $filters['severity']);
        }
        if (!empty($filters['feature_id'])) {
            $query->where('bug_sla_records.feature_id', $filters['feature_id']);
        }
        if (!empty($filters['origin'])) {
            $query->where('bug_sla_records.origin', $filters['origin']);
        }
        if (isset($filters['breached'])) {
            if ($filters['breached']) {
                $query->whereNotNull('bug_sla_records.breached_at');
            } else {
                $query->whereNull('bug_sla_records.breached_at');
            }
        }

        return $query->orderByDesc('bug_sla_records.created_at')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('bug_sla_records')
            ->leftJoin('features', 'bug_sla_records.feature_id', '=', 'features.id')
            ->select(
                'bug_sla_records.*',
                'features.title as feature_title'
            )
            ->where('bug_sla_records.id', $id)
            ->whereNull('bug_sla_records.deleted_at')
            ->first();
    }

    public function findByFeature(int $featureId): array
    {
        return DB::table('bug_sla_records')
            ->where('feature_id', $featureId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function findBreached(): array
    {
        return DB::table('bug_sla_records')
            ->leftJoin('features', 'bug_sla_records.feature_id', '=', 'features.id')
            ->select('bug_sla_records.*', 'features.title as feature_title')
            ->whereNotNull('bug_sla_records.breached_at')
            ->whereNull('bug_sla_records.deleted_at')
            ->orderByDesc('bug_sla_records.breached_at')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('bug_sla_records')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('bug_sla_records')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('bug_sla_records')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }
}
