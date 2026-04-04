<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\GitEventRepository;
use App\Services\GitEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GitWebhookController extends Controller
{
    public function __construct(
        private readonly GitEventRepository $gitEventRepository,
        private readonly GitEventService $gitEventService,
    ) {}

    /**
     * Handle incoming git webhook.
     * Verifies HMAC-SHA256 signature and processes event.
     */
    public function handle(Request $request, string $repoName): JsonResponse
    {
        $repo = DB::table('repositories')
            ->where('name', $repoName)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (!$repo) {
            return response()->json(['error' => 'Repository not found'], 404);
        }

        // Verify HMAC signature
        if (!$this->verifySignature($request, $repo)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        $eventType = $this->determineEventType($request);

        if (!$eventType) {
            return response()->json(['message' => 'Event type not supported'], 200);
        }

        // Extract feature ID from branch name
        $branchName = $this->extractBranchName($payload);
        $featureId = $branchName ? $this->gitEventService->extractFeatureIdFromBranch($branchName) : null;

        // Resolve team member from commit/PR author email
        $email = $this->extractAuthorEmail($payload);
        $teamMemberId = $email ? $this->gitEventService->resolveTeamMember($email) : null;

        // Store the event
        $eventId = $this->gitEventRepository->create([
            'repository_id'  => $repo->id,
            'feature_id'     => $featureId,
            'team_member_id' => $teamMemberId,
            'event_type'     => $eventType,
            'payload'        => json_encode($payload),
        ]);

        // Process auto status transitions
        $this->gitEventService->processEvent($eventId);

        // Create/update git branch record if applicable
        if ($branchName && $eventType === 'branch_created') {
            DB::table('git_branches')->insertOrIgnore([
                'repository_id' => $repo->id,
                'feature_id'    => $featureId,
                'branch_name'   => $branchName,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    private function verifySignature(Request $request, object $repo): bool
    {
        if (!$repo->webhook_secret) {
            return true; // No secret configured, skip verification
        }

        $signature = $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Gitlab-Token');

        if (!$signature) {
            return false;
        }

        try {
            $secret = Crypt::decryptString($repo->webhook_secret);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt webhook secret', ['repo_id' => $repo->id]);
            return false;
        }

        // GitHub format: sha256=<hash>
        if (str_starts_with($signature, 'sha256=')) {
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            return hash_equals($expected, $signature);
        }

        // GitLab format: plain token
        return hash_equals($secret, $signature);
    }

    private function determineEventType(Request $request): ?string
    {
        $githubEvent = $request->header('X-GitHub-Event');
        $gitlabEvent = $request->header('X-Gitlab-Event');

        if ($githubEvent === 'push') {
            return 'push';
        }
        if ($githubEvent === 'create' && ($request->input('ref_type') === 'branch')) {
            return 'branch_created';
        }
        if ($githubEvent === 'create' && ($request->input('ref_type') === 'tag')) {
            return 'tag_created';
        }
        if ($githubEvent === 'pull_request') {
            $action = $request->input('action');
            return match ($action) {
                'opened'          => 'pull_request_opened',
                'closed'          => $request->input('pull_request.merged') ? 'pull_request_merged' : null,
                'review_approved' => 'pull_request_approved',
                default           => null,
            };
        }
        if ($githubEvent === 'pull_request_review' && $request->input('review.state') === 'approved') {
            return 'pull_request_approved';
        }

        // GitLab events
        if ($gitlabEvent === 'Push Hook') {
            return 'push';
        }
        if ($gitlabEvent === 'Tag Push Hook') {
            return 'tag_created';
        }
        if ($gitlabEvent === 'Merge Request Hook') {
            $action = $request->input('object_attributes.action');
            return match ($action) {
                'open'   => 'pull_request_opened',
                'merge'  => 'pull_request_merged',
                'approved'=> 'pull_request_approved',
                default  => null,
            };
        }

        return null;
    }

    private function extractBranchName(array $payload): ?string
    {
        return $payload['ref'] ?? $payload['pull_request']['head']['ref']
            ?? $payload['object_attributes']['source_branch'] ?? null;
    }

    private function extractAuthorEmail(array $payload): ?string
    {
        return $payload['pusher']['email'] ?? $payload['sender']['email']
            ?? $payload['user']['email'] ?? null;
    }
}
