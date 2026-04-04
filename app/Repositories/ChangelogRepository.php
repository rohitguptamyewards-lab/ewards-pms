<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class ChangelogRepository
{
    /**
     * Get paginated changelogs with optional filters.
     * Joins team_members twice for drafter and approver names.
     *
     * @param array $filters Supported: status
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('changelogs')
            ->leftJoin('team_members as drafter', 'changelogs.drafted_by', '=', 'drafter.id')
            ->leftJoin('team_members as approver', 'changelogs.approved_by', '=', 'approver.id')
            ->select(
                'changelogs.*',
                'drafter.name as drafter_name',
                'approver.name as approver_name'
            )
            ->whereNull('changelogs.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('changelogs.status', $filters['status']);
        }

        return $query->orderByDesc('changelogs.created_at')->paginate($perPage);
    }

    /**
     * Find a single changelog by ID (not soft-deleted).
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        return DB::table('changelogs')
            ->leftJoin('team_members as drafter', 'changelogs.drafted_by', '=', 'drafter.id')
            ->leftJoin('team_members as approver', 'changelogs.approved_by', '=', 'approver.id')
            ->select(
                'changelogs.*',
                'drafter.name as drafter_name',
                'approver.name as approver_name'
            )
            ->where('changelogs.id', $id)
            ->whereNull('changelogs.deleted_at')
            ->first() ?: null;
    }

    /**
     * Insert a new changelog and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('changelogs')->insertGetId($data);
    }

    /**
     * Update a changelog by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('changelogs')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data) > 0;
    }

    /**
     * Soft-delete a changelog by setting deleted_at.
     *
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        return DB::table('changelogs')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get all published changelogs, ordered by published_at desc.
     *
     * @return array
     */
    public function findPublished(): array
    {
        return DB::table('changelogs')
            ->leftJoin('team_members as drafter', 'changelogs.drafted_by', '=', 'drafter.id')
            ->leftJoin('team_members as approver', 'changelogs.approved_by', '=', 'approver.id')
            ->select(
                'changelogs.*',
                'drafter.name as drafter_name',
                'approver.name as approver_name'
            )
            ->where('changelogs.status', 'published')
            ->whereNull('changelogs.deleted_at')
            ->orderByDesc('changelogs.published_at')
            ->get()
            ->toArray();
    }
}
