<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class CommentRepository
{
    /**
     * Get comments for a polymorphic entity, with user name, ordered by created_at.
     *
     * @param string $type Fully-qualified morph class (e.g. App\Models\Task)
     * @param int    $id   The entity ID
     * @return array
     */
    public function findByEntity(string $type, int $id): array
    {
        return DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select(
                'comments.*',
                'team_members.name as user_name'
            )
            ->where('comments.commentable_type', $type)
            ->where('comments.commentable_id', $id)
            ->whereNull('comments.deleted_at')
            ->orderBy('comments.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Insert a new comment and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('comments')->insertGetId($data);
    }
}
