<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class GitProviderRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('git_providers')
            ->whereNull('deleted_at');

        if (!empty($filters['provider_type'])) {
            $query->where('provider_type', $filters['provider_type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('git_providers')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    public function findAll(): array
    {
        return DB::table('git_providers')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->select('id', 'name', 'provider_type')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('git_providers')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('git_providers')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('git_providers')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }
}
