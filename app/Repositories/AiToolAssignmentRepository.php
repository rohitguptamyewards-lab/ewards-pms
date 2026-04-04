<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class AiToolAssignmentRepository
{
    /**
     * Get all assignments for a given AI tool, with team_member name.
     *
     * @param int $toolId
     * @return array
     */
    public function findByTool(int $toolId): array
    {
        return DB::table('ai_tool_assignments')
            ->join('team_members', 'ai_tool_assignments.team_member_id', '=', 'team_members.id')
            ->select(
                'ai_tool_assignments.*',
                'team_members.name as member_name'
            )
            ->where('ai_tool_assignments.ai_tool_id', $toolId)
            ->orderByDesc('ai_tool_assignments.assigned_at')
            ->get()
            ->toArray();
    }

    /**
     * Get all active assignments (not revoked) for a team member.
     *
     * @param int $memberId
     * @return array
     */
    public function findByMember(int $memberId): array
    {
        return DB::table('ai_tool_assignments')
            ->join('ai_tools', 'ai_tool_assignments.ai_tool_id', '=', 'ai_tools.id')
            ->select(
                'ai_tool_assignments.*',
                'ai_tools.name as tool_name',
                'ai_tools.provider as tool_provider'
            )
            ->where('ai_tool_assignments.team_member_id', $memberId)
            ->whereNull('ai_tool_assignments.revoked_at')
            ->whereNull('ai_tools.deleted_at')
            ->orderByDesc('ai_tool_assignments.assigned_at')
            ->get()
            ->toArray();
    }

    /**
     * Assign an AI tool to a team member. Sets assigned_at to now().
     *
     * @param int $toolId
     * @param int $memberId
     * @return int  The new assignment ID
     */
    public function assign(int $toolId, int $memberId): int
    {
        return DB::table('ai_tool_assignments')->insertGetId([
            'ai_tool_id'     => $toolId,
            'team_member_id' => $memberId,
            'assigned_at'    => now(),
            'revoked_at'     => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Revoke an assignment by setting revoked_at to now().
     *
     * @param int $toolId
     * @param int $memberId
     * @return bool
     */
    public function revoke(int $toolId, int $memberId): bool
    {
        return DB::table('ai_tool_assignments')
            ->where('ai_tool_id', $toolId)
            ->where('team_member_id', $memberId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Check whether a tool is currently assigned to a member (not revoked).
     *
     * @param int $toolId
     * @param int $memberId
     * @return bool
     */
    public function isAssigned(int $toolId, int $memberId): bool
    {
        return DB::table('ai_tool_assignments')
            ->where('ai_tool_id', $toolId)
            ->where('team_member_id', $memberId)
            ->whereNull('revoked_at')
            ->exists();
    }
}
