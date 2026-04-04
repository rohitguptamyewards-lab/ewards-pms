<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DeadlineRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('deadlines')
            ->whereNull('deleted_at');

        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['deadlineable_type'])) {
            $query->where('deadlineable_type', $filters['deadlineable_type']);
        }
        if (!empty($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }
        if (!empty($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }

        return $query->orderBy('due_date')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('deadlines')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    public function findByEntity(string $type, int $id): array
    {
        return DB::table('deadlines')
            ->where('deadlineable_type', $type)
            ->where('deadlineable_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('due_date')
            ->get()
            ->toArray();
    }

    public function findUpcoming(int $days = 7): array
    {
        return DB::table('deadlines')
            ->whereNull('deleted_at')
            ->whereIn('state', ['on_track', 'at_risk'])
            ->where('due_date', '<=', now()->addDays($days)->toDateString())
            ->where('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->get()
            ->toArray();
    }

    public function findOverdue(): array
    {
        return DB::table('deadlines')
            ->whereNull('deleted_at')
            ->whereIn('state', ['on_track', 'at_risk'])
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get()
            ->toArray();
    }

    public function findCascadeChildren(int $parentId): array
    {
        return DB::table('deadlines')
            ->where('cascade_from_id', $parentId)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('deadlines')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('deadlines')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('deadlines')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }
}
