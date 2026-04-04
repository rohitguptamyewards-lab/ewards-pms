<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class PromptTemplateRepository
{
    /**
     * Get paginated prompt templates with optional filters.
     * Joins ai_tools for tool name and team_members for creator name.
     *
     * @param array $filters Supported: ai_tool_id, capability
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('prompt_templates')
            ->leftJoin('ai_tools', 'prompt_templates.ai_tool_id', '=', 'ai_tools.id')
            ->leftJoin('team_members', 'prompt_templates.created_by', '=', 'team_members.id')
            ->select(
                'prompt_templates.*',
                'ai_tools.name as tool_name',
                'ai_tools.provider as tool_provider',
                'team_members.name as creator_name'
            )
            ->whereNull('prompt_templates.deleted_at');

        if (!empty($filters['ai_tool_id'])) {
            $query->where('prompt_templates.ai_tool_id', $filters['ai_tool_id']);
        }

        if (!empty($filters['capability'])) {
            $query->where('prompt_templates.capability', $filters['capability']);
        }

        return $query->orderByDesc('prompt_templates.created_at')->paginate($perPage);
    }

    /**
     * Find a single prompt template by ID (not soft-deleted).
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        return DB::table('prompt_templates')
            ->leftJoin('ai_tools', 'prompt_templates.ai_tool_id', '=', 'ai_tools.id')
            ->leftJoin('team_members', 'prompt_templates.created_by', '=', 'team_members.id')
            ->select(
                'prompt_templates.*',
                'ai_tools.name as tool_name',
                'ai_tools.provider as tool_provider',
                'team_members.name as creator_name'
            )
            ->where('prompt_templates.id', $id)
            ->whereNull('prompt_templates.deleted_at')
            ->first() ?: null;
    }

    /**
     * Search prompt templates with filters including JSON tag matching.
     *
     * @param array $filters Supported: capability, tool_id, tags (array — uses JSON_CONTAINS)
     * @return array
     */
    public function search(array $filters = []): array
    {
        $query = DB::table('prompt_templates')
            ->leftJoin('ai_tools', 'prompt_templates.ai_tool_id', '=', 'ai_tools.id')
            ->select(
                'prompt_templates.*',
                'ai_tools.name as tool_name'
            )
            ->whereNull('prompt_templates.deleted_at');

        if (!empty($filters['capability'])) {
            $query->where('prompt_templates.capability', $filters['capability']);
        }

        if (!empty($filters['tool_id'])) {
            $query->where('prompt_templates.ai_tool_id', $filters['tool_id']);
        }

        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereRaw('JSON_CONTAINS(prompt_templates.tags, ?)', [json_encode($tag)]);
            }
        }

        return $query->orderByDesc('prompt_templates.usage_count')->get()->toArray();
    }

    /**
     * Insert a new prompt template and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $data['usage_count'] = $data['usage_count'] ?? 0;
        $data['created_at']  = now();
        $data['updated_at']  = now();

        return DB::table('prompt_templates')->insertGetId($data);
    }

    /**
     * Update a prompt template by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $data['updated_at'] = now();

        return DB::table('prompt_templates')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($data) > 0;
    }

    /**
     * Soft-delete a prompt template by setting deleted_at.
     *
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        return DB::table('prompt_templates')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Increment the usage_count for a prompt template by 1.
     *
     * @param int $id
     * @return void
     */
    public function incrementUsage(int $id): void
    {
        DB::table('prompt_templates')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->increment('usage_count', 1, ['updated_at' => now()]);
    }

    /**
     * Get the most-used prompt templates, ordered by usage_count desc.
     *
     * @param int $limit
     * @return array
     */
    public function getMostUsed(int $limit = 10): array
    {
        return DB::table('prompt_templates')
            ->leftJoin('ai_tools', 'prompt_templates.ai_tool_id', '=', 'ai_tools.id')
            ->select(
                'prompt_templates.*',
                'ai_tools.name as tool_name'
            )
            ->whereNull('prompt_templates.deleted_at')
            ->orderByDesc('prompt_templates.usage_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
