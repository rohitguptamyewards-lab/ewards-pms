<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class DocumentRepository
{
    /**
     * Get documents for a polymorphic entity.
     *
     * @param string $type Fully-qualified morph class (e.g. App\Models\Feature)
     * @param int    $id   The entity ID
     * @return array
     */
    public function findByEntity(string $type, int $id): array
    {
        return DB::table('documents')
            ->leftJoin('team_members', 'documents.uploaded_by', '=', 'team_members.id')
            ->select(
                'documents.*',
                'team_members.name as uploader_name'
            )
            ->where('documents.documentable_type', $type)
            ->where('documents.documentable_id', $id)
            ->orderByDesc('documents.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Insert a new document record and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('documents')->insertGetId($data);
    }

    /**
     * Find a single document by ID.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        $row = DB::table('documents')
            ->leftJoin('team_members', 'documents.uploaded_by', '=', 'team_members.id')
            ->select(
                'documents.*',
                'team_members.name as uploader_name'
            )
            ->where('documents.id', $id)
            ->first();

        return $row ?: null;
    }

    /**
     * Delete a document by ID (hard delete since Document model has no SoftDeletes).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return DB::table('documents')
            ->where('id', $id)
            ->delete() > 0;
    }
}
