<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class GitRepositoryRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('repositories')
            ->leftJoin('git_providers', 'repositories.git_provider_id', '=', 'git_providers.id')
            ->select(
                'repositories.*',
                'git_providers.name as provider_name',
                'git_providers.provider_type'
            )
            ->whereNull('repositories.deleted_at');

        if (!empty($filters['git_provider_id'])) {
            $query->where('repositories.git_provider_id', $filters['git_provider_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('repositories.is_active', $filters['is_active']);
        }

        return $query->orderBy('repositories.name')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('repositories')
            ->leftJoin('git_providers', 'repositories.git_provider_id', '=', 'git_providers.id')
            ->select(
                'repositories.*',
                'git_providers.name as provider_name',
                'git_providers.provider_type'
            )
            ->where('repositories.id', $id)
            ->whereNull('repositories.deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('repositories')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('repositories')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('repositories')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }

    public function findByWebhookSecret(string $repoName): ?object
    {
        return DB::table('repositories')
            ->where('name', $repoName)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();
    }
}
