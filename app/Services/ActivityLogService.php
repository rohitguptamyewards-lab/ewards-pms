<?php

namespace App\Services;

use App\Repositories\ActivityLogRepository;
use Illuminate\Support\Facades\DB;

class ActivityLogService
{
    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function create(array $data): int
    {
        // If blocked status, require blocker_reason
        if (($data['status'] ?? '') === 'blocked' && empty($data['blocker_reason'])) {
            throw new \InvalidArgumentException('Blocker reason is required when status is blocked.');
        }

        // Strip AI fields when ai_used is false
        if (empty($data['ai_used']) || !$data['ai_used']) {
            unset($data['ai_tool_id'], $data['ai_capability'], $data['ai_contribution'], $data['ai_outcome'], $data['ai_time_saved'], $data['ai_note']);
            $data['ai_used'] = false;
        }

        $id = $this->activityLogRepository->create($data);

        // Update logging streak
        $this->updateStreak($data['team_member_id']);

        return $id;
    }

    public function prefillFromYesterday(int $teamMemberId): array
    {
        $yesterdayLogs = $this->activityLogRepository->findYesterdayLogs($teamMemberId);

        return array_map(function ($log) {
            $prefilled = (array) $log;
            unset($prefilled['id'], $prefilled['created_at'], $prefilled['updated_at'], $prefilled['deleted_at']);
            $prefilled['is_same_as_yesterday'] = true;
            $prefilled['log_date'] = now()->toDateString();
            return $prefilled;
        }, $yesterdayLogs);
    }

    private function updateStreak(int $teamMemberId): void
    {
        $member = DB::table('team_members')->where('id', $teamMemberId)->first();
        if (!$member) {
            return;
        }

        $today = now()->toDateString();
        $lastLogDate = $member->last_log_date ?? null;

        if ($lastLogDate === $today) {
            return; // Already logged today
        }

        $yesterday = now()->subDay()->toDateString();
        $currentStreak = $member->logging_streak_count ?? 0;

        if ($lastLogDate === $yesterday) {
            $newStreak = $currentStreak + 1;
        } else {
            $newStreak = 1; // Reset streak
        }

        DB::table('team_members')
            ->where('id', $teamMemberId)
            ->update([
                'logging_streak_count' => $newStreak,
                'last_log_date'        => $today,
            ]);
    }
}
