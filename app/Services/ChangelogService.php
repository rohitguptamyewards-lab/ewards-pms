<?php

namespace App\Services;

use App\Repositories\ChangelogRepository;
use Illuminate\Support\Facades\DB;

class ChangelogService
{
    public function __construct(
        private readonly ChangelogRepository $changelogRepository,
    ) {}

    /**
     * Create a changelog. Auto-drafts from release feature titles if body is empty.
     */
    public function create(array $data): int
    {
        if (empty($data['body']) && !empty($data['release_id'])) {
            $features = DB::table('release_features')
                ->join('features', 'release_features.feature_id', '=', 'features.id')
                ->where('release_features.release_id', $data['release_id'])
                ->pluck('features.title')
                ->toArray();

            $data['body'] = implode("\n", array_map(fn ($t) => "- {$t}", $features));
            unset($data['release_id']);
        } else {
            unset($data['release_id']);
        }

        $data['status'] = 'draft';
        $data['body'] = $data['body'] ?? '';

        return $this->changelogRepository->create($data);
    }

    /**
     * Approve a changelog (product head approves tech lead's draft).
     */
    public function approve(int $id, int $approvedBy): int
    {
        return $this->changelogRepository->update($id, [
            'status'      => 'approved',
            'approved_by' => $approvedBy,
        ]);
    }

    /**
     * Publish an approved changelog.
     */
    public function publish(int $id): int
    {
        return $this->changelogRepository->update($id, [
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }
}
