<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class AiToolRepository
{
    /**
     * Get paginated AI tools with optional filters.
     *
     * @param array $filters Supported: is_active, provider
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('ai_tools')
            ->whereNull('ai_tools.deleted_at');

        if (isset($filters['is_active'])) {
            $query->where('ai_tools.is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['provider'])) {
            $query->where('ai_tools.provider', $filters['provider']);
        }

        return $query->orderByDesc('ai_tools.created_at')->paginate($perPage);
    }

    /**
     * Find a single AI tool by ID (not soft-deleted).
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        return DB::table('ai_tools')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first() ?: null;
    }

    /**
     * Get all active AI tools (is_active = true, no deleted_at).
     *
     * @return array
     */
    public function findActive(): array
    {
        return DB::table('ai_tools')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Insert a new AI tool and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('ai_tools')->insertGetId($data);
    }

    /**
     * Update an AI tool by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('ai_tools')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data) > 0;
    }

    /**
     * Soft-delete an AI tool by setting deleted_at.
     *
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        return DB::table('ai_tools')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }
}
