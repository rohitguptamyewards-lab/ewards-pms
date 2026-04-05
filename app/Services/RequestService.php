<?php

namespace App\Services;

use App\Repositories\FeatureRepository;
use App\Repositories\RequestRepository;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RequestService
{
    public function __construct(
        private readonly RequestRepository $requestRepository,
        private readonly FeatureRepository $featureRepository,
        private readonly EmailNotificationService $emailService,
    ) {}

    /**
     * Tokenize the title and search for duplicates via the repository.
     *
     * Flags results with > 50% word overlap as likely duplicates.
     *
     * @param string $title
     * @return array Each element includes the match data plus a `is_likely_duplicate` flag.
     */
    public function findDuplicates(string $title): array
    {
        $candidates = $this->requestRepository->searchDuplicates($title);

        if (empty($candidates)) {
            return [];
        }

        $inputWords = $this->tokenize($title);

        $results = [];
        foreach ($candidates as $candidate) {
            $candidateWords = $this->tokenize($candidate->title);
            $overlap = $this->calculateOverlap($inputWords, $candidateWords);

            $results[] = [
                'id'                  => $candidate->id,
                'title'               => $candidate->title,
                'demand_count'        => $candidate->demand_count,
                'overlap_percentage'  => $overlap,
                'is_likely_duplicate' => $overlap > 50.0,
            ];
        }

        // Sort by overlap descending
        usort($results, fn (array $a, array $b) => $b['overlap_percentage'] <=> $a['overlap_percentage']);

        return $results;
    }

    /**
     * Merge source request into target request.
     *
     * @param int $sourceId
     * @param int $targetId
     * @return bool
     */
    public function merge(int $sourceId, int $targetId): bool
    {
        return $this->requestRepository->merge($sourceId, $targetId);
    }

    /**
     * Triage a request: accept, defer, or reject.
     *
     * @param int         $requestId
     * @param string      $action   One of: accept, defer, reject
     * @param string|null $reason   Optional reason (used for defer/reject)
     * @return void
     *
     * @throws InvalidArgumentException If action is invalid or request not found.
     */
    public function triage(int $requestId, string $action, ?string $reason = null): void
    {
        $request = $this->requestRepository->findById($requestId);

        if (!$request) {
            throw new InvalidArgumentException("Request #{$requestId} not found.");
        }

        $userName = auth()->user()->name ?? 'System';
        $oldStatus = $request->status ?? 'received';

        switch ($action) {
            case 'accept':
                // Create a Feature from the request data
                $featureId = $this->featureRepository->create([
                    'title'        => $request->title,
                    'description'  => $request->description,
                    'type'         => $request->type,
                    'origin_type'  => 'request',
                    'status'       => 'backlog',
                ]);

                // Link request to the new feature and update status to 'linked'
                $this->requestRepository->update($requestId, [
                    'status'            => 'linked',
                    'linked_feature_id' => $featureId,
                ]);

                $this->logRequestComment($requestId, "Request accepted and linked to a new feature by {$userName}." . ($reason ? "\nReason: {$reason}" : ''));
                break;

            case 'defer':
                $this->requestRepository->update($requestId, [
                    'status' => 'deferred',
                ]);
                $this->logRequestComment($requestId, "Request deferred by {$userName}." . ($reason ? "\nReason: {$reason}" : ''));
                break;

            case 'reject':
                $this->requestRepository->update($requestId, [
                    'status' => 'rejected',
                ]);
                $this->logRequestComment($requestId, "Request rejected by {$userName}." . ($reason ? "\nReason: {$reason}" : ''));
                break;

            case 'clarify':
                $this->requestRepository->update($requestId, [
                    'status' => 'clarification_needed',
                ]);
                $this->logRequestComment($requestId, "Clarification requested by {$userName}." . ($reason ? "\n\n{$reason}" : ''));
                // Send email to the requester asking for clarification
                try {
                    $this->emailService->onRequestClarificationNeeded($requestId, $reason);
                } catch (\Throwable $e) {}
                return; // Skip the general triage email below — clarification has its own

            default:
                throw new InvalidArgumentException("Invalid triage action: {$action}. Must be accept, defer, reject, or clarify.");
        }

        // Send triage result email to the requester
        try {
            $this->emailService->onRequestTriaged($requestId, $action, $reason);
        } catch (\Throwable $e) {}
    }

    /**
     * Log a system comment on a request for audit trail.
     */
    private function logRequestComment(int $requestId, string $body): void
    {
        DB::table('comments')->insert([
            'commentable_type' => 'request',
            'commentable_id'   => $requestId,
            'user_id'          => auth()->id(),
            'body'             => $body,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Tokenize a string into lowercase words, stripping common stop words.
     *
     * @param string $text
     * @return array
     */
    private function tokenize(string $text): array
    {
        $words = preg_split('/[\s\-_\/]+/', mb_strtolower(trim($text)));

        $stopWords = ['a', 'an', 'the', 'is', 'in', 'at', 'of', 'to', 'for', 'on', 'and', 'or', 'it', 'be', 'as', 'do'];

        return array_values(array_filter($words, function (string $word) use ($stopWords) {
            return mb_strlen($word) > 1 && !in_array($word, $stopWords, true);
        }));
    }

    /**
     * Calculate word overlap percentage between two tokenized arrays.
     *
     * @param array $wordsA
     * @param array $wordsB
     * @return float 0-100
     */
    private function calculateOverlap(array $wordsA, array $wordsB): float
    {
        if (empty($wordsA) || empty($wordsB)) {
            return 0.0;
        }

        $intersection = array_intersect($wordsA, $wordsB);
        $maxCount = max(count($wordsA), count($wordsB));

        return round((count($intersection) / $maxCount) * 100, 2);
    }
}
