<?php

namespace App\Services;

use App\Repositories\GitEventRepository;
use Illuminate\Support\Facades\DB;

class GitEventService
{
    public function __construct(
        private readonly GitEventRepository $gitEventRepository,
    ) {}

    /**
     * Process a Git webhook event and handle auto status transitions.
     */
    public function processEvent(int $eventId): void
    {
        $event = DB::table('git_events')->where('id', $eventId)->first();
        if (!$event || $event->processed_at) {
            return;
        }

        $featureId = $event->feature_id;
        if ($featureId) {
            $this->handleStatusTransition($event->event_type, $featureId);
        }

        $this->gitEventRepository->markProcessed($eventId);
    }

    /**
     * Auto-transition feature status based on git event type.
     * branch_created → In Progress
     * pr_opened → In Review
     * pr_approved → Ready for QA
     * pr_merged (develop) → In QA
     * pr_merged (main) → Ready for Release
     * tag_created → Released
     */
    private function handleStatusTransition(string $eventType, int $featureId): void
    {
        $statusMap = [
            'branch_created'       => ['from' => ['backlog'], 'to' => 'in_progress'],
            'pull_request_opened'  => ['from' => ['in_progress'], 'to' => 'in_review'],
            'pull_request_approved'=> ['from' => ['in_review'], 'to' => 'ready_for_qa'],
            'pull_request_merged'  => ['from' => ['in_review', 'ready_for_qa'], 'to' => 'in_qa'],
            'tag_created'          => ['from' => ['ready_for_release', 'in_qa'], 'to' => 'released'],
        ];

        if (!isset($statusMap[$eventType])) {
            return;
        }

        $mapping = $statusMap[$eventType];
        $feature = DB::table('features')->where('id', $featureId)->first();

        if ($feature && in_array($feature->status, $mapping['from'])) {
            DB::table('features')
                ->where('id', $featureId)
                ->update([
                    'status'     => $mapping['to'],
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Extract feature ID from branch name (e.g., feature/F-123-description → 123).
     */
    public function extractFeatureIdFromBranch(string $branchName): ?int
    {
        if (preg_match('/F-(\d+)/i', $branchName, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Match git user to team member by email.
     */
    public function resolveTeamMember(string $email): ?int
    {
        $member = DB::table('team_members')
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        return $member?->id;
    }
}
