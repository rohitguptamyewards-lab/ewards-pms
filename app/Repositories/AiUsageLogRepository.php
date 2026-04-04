<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class AiUsageLogRepository
{
    /**
     * Get paginated AI usage logs with optional filters.
     *
     * @param array $filters Supported: team_member_id, ai_tool_id, capability, outcome
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('ai_usage_logs')
            ->leftJoin('team_members', 'ai_usage_logs.team_member_id', '=', 'team_members.id')
            ->leftJoin('ai_tools', 'ai_usage_logs.ai_tool_id', '=', 'ai_tools.id')
            ->leftJoin('work_logs', 'ai_usage_logs.work_log_id', '=', 'work_logs.id')
            ->leftJoin('prompt_templates', 'ai_usage_logs.prompt_template_id', '=', 'prompt_templates.id')
            ->select(
                'ai_usage_logs.*',
                'team_members.name as member_name',
                'ai_tools.name as tool_name',
                'ai_tools.provider as tool_provider',
                'prompt_templates.title as prompt_template_title'
            );

        if (!empty($filters['team_member_id'])) {
            $query->where('ai_usage_logs.team_member_id', $filters['team_member_id']);
        }

        if (!empty($filters['ai_tool_id'])) {
            $query->where('ai_usage_logs.ai_tool_id', $filters['ai_tool_id']);
        }

        if (!empty($filters['capability'])) {
            $query->where('ai_usage_logs.capability', $filters['capability']);
        }

        if (!empty($filters['outcome'])) {
            $query->where('ai_usage_logs.outcome', $filters['outcome']);
        }

        return $query->orderByDesc('ai_usage_logs.created_at')->paginate($perPage);
    }

    /**
     * Get all usage logs for a team member, with optional date range filters.
     *
     * @param int   $memberId
     * @param array $filters Supported: from, to (dates applied to work_logs.log_date via join)
     * @return array
     */
    public function findByMember(int $memberId, array $filters = []): array
    {
        $query = DB::table('ai_usage_logs')
            ->leftJoin('ai_tools', 'ai_usage_logs.ai_tool_id', '=', 'ai_tools.id')
            ->leftJoin('work_logs', 'ai_usage_logs.work_log_id', '=', 'work_logs.id')
            ->select(
                'ai_usage_logs.*',
                'ai_tools.name as tool_name',
                'ai_tools.provider as tool_provider',
                'work_logs.log_date'
            )
            ->where('ai_usage_logs.team_member_id', $memberId);

        if (!empty($filters['from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('ai_usage_logs.work_log_id')
                  ->orWhere('work_logs.log_date', '>=', $filters['from']);
            });
        }

        if (!empty($filters['to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('ai_usage_logs.work_log_id')
                  ->orWhere('work_logs.log_date', '<=', $filters['to']);
            });
        }

        return $query->orderByDesc('ai_usage_logs.created_at')->get()->toArray();
    }

    /**
     * Get all usage logs for a given AI tool.
     *
     * @param int $toolId
     * @return array
     */
    public function findByTool(int $toolId): array
    {
        return DB::table('ai_usage_logs')
            ->leftJoin('team_members', 'ai_usage_logs.team_member_id', '=', 'team_members.id')
            ->select(
                'ai_usage_logs.*',
                'team_members.name as member_name'
            )
            ->where('ai_usage_logs.ai_tool_id', $toolId)
            ->orderByDesc('ai_usage_logs.created_at')
            ->get()
            ->toArray();
    }

    /**
     * Insert a new AI usage log and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('ai_usage_logs')->insertGetId($data);
    }

    /**
     * Returns counts of each outcome per tool + capability combination.
     * Used as an effectiveness matrix for reporting.
     *
     * @return array  Each item: ai_tool_id, tool_name, capability, outcome, count
     */
    public function getEffectivenessMatrix(): array
    {
        return DB::table('ai_usage_logs')
            ->join('ai_tools', 'ai_usage_logs.ai_tool_id', '=', 'ai_tools.id')
            ->select(
                'ai_usage_logs.ai_tool_id',
                'ai_tools.name as tool_name',
                'ai_usage_logs.capability',
                'ai_usage_logs.outcome',
                DB::raw('COUNT(*) as count')
            )
            ->whereNull('ai_tools.deleted_at')
            ->groupBy(
                'ai_usage_logs.ai_tool_id',
                'ai_tools.name',
                'ai_usage_logs.capability',
                'ai_usage_logs.outcome'
            )
            ->orderBy('ai_usage_logs.ai_tool_id')
            ->orderBy('ai_usage_logs.capability')
            ->orderBy('ai_usage_logs.outcome')
            ->get()
            ->toArray();
    }

    /**
     * Returns log notes where the outcome was helpful or partially helpful.
     * Ordered by created_at desc, limited to 200 records.
     * Used as a knowledge base of useful AI interactions.
     *
     * @return array
     */
    public function getKnowledgeBaseItems(): array
    {
        return DB::table('ai_usage_logs')
            ->leftJoin('team_members', 'ai_usage_logs.team_member_id', '=', 'team_members.id')
            ->leftJoin('ai_tools', 'ai_usage_logs.ai_tool_id', '=', 'ai_tools.id')
            ->select(
                'ai_usage_logs.id',
                'ai_usage_logs.capability',
                'ai_usage_logs.contribution',
                'ai_usage_logs.outcome',
                'ai_usage_logs.time_saved',
                'ai_usage_logs.note',
                'ai_usage_logs.created_at',
                'team_members.name as member_name',
                'ai_tools.name as tool_name'
            )
            ->whereIn('ai_usage_logs.outcome', ['helpful', 'partially'])
            ->whereNotNull('ai_usage_logs.note')
            ->orderByDesc('ai_usage_logs.created_at')
            ->limit(200)
            ->get()
            ->toArray();
    }
}
