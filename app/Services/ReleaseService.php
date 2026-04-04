<?php

namespace App\Services;

use App\Repositories\ReleaseRepository;
use Illuminate\Support\Facades\DB;

class ReleaseService
{
    public function __construct(
        private readonly ReleaseRepository $releaseRepository,
    ) {}

    public function create(array $data, array $featureIds = []): int
    {
        // Auto-generate notes from feature titles if not provided
        if (empty($data['notes']) && !empty($featureIds)) {
            $features = DB::table('features')
                ->whereIn('id', $featureIds)
                ->pluck('title')
                ->toArray();
            $data['notes'] = implode("\n", array_map(fn ($t) => "- {$t}", $features));
        }

        $releaseId = $this->releaseRepository->create($data);

        // Attach features and transition to Released
        foreach ($featureIds as $featureId) {
            $this->releaseRepository->addFeature($releaseId, $featureId);

            DB::table('features')
                ->where('id', $featureId)
                ->update([
                    'status'     => 'released',
                    'updated_at' => now(),
                ]);
        }

        return $releaseId;
    }
}
