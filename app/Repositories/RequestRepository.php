<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class RequestRepository
{
    /**
     * Get paginated list of requests with optional filters.
     *
     * @param array $filters Supported: status, type, urgency, requested_by
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findAll(array $filters = [], int $perPage = 20)
    {
        $query = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select(
                'requests.*',
                'merchants.name as merchant_name',
                'team_members.name as requester_name'
            )
            ->whereNull('requests.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('requests.status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('requests.type', $filters['type']);
        }

        if (!empty($filters['urgency'])) {
            $query->where('requests.urgency', $filters['urgency']);
        }

        if (!empty($filters['requested_by'])) {
            $query->where('requests.requested_by', $filters['requested_by']);
        }

        return $query->orderByDesc('requests.created_at')->paginate($perPage);
    }

    /**
     * Find a single request by ID with related names.
     *
     * @param int $id
     * @return stdClass|null
     */
    public function findById(int $id): ?stdClass
    {
        $row = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->leftJoin('features', 'requests.linked_feature_id', '=', 'features.id')
            ->select(
                'requests.*',
                'merchants.name as merchant_name',
                'team_members.name as requester_name',
                'features.title as feature_title'
            )
            ->where('requests.id', $id)
            ->whereNull('requests.deleted_at')
            ->first();

        return $row ?: null;
    }

    /**
     * Insert a new request and return its ID.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('requests')->insertGetId($data);
    }

    /**
     * Update a request by ID.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('requests')
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Search for potential duplicate requests by title similarity.
     *
     * Uses the first significant word and SOUNDEX for fuzzy matching.
     *
     * @param string $title
     * @return array
     */
    public function searchDuplicates(string $title): array
    {
        $words = preg_split('/\s+/', trim($title));

        // Filter out very short/common words to find the first significant word
        $stopWords = ['a', 'an', 'the', 'is', 'in', 'at', 'of', 'to', 'for', 'on', 'and', 'or'];
        $significantWord = '';
        foreach ($words as $word) {
            if (mb_strlen($word) > 2 && !in_array(mb_strtolower($word), $stopWords, true)) {
                $significantWord = $word;
                break;
            }
        }

        if ($significantWord === '') {
            $significantWord = $words[0] ?? '';
        }

        if ($significantWord === '') {
            return [];
        }

        return DB::table('requests')
            ->select('id', 'title', 'demand_count')
            ->whereNull('deleted_at')
            ->where('title', 'LIKE', '%' . $significantWord . '%')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Merge source request into target: increment demand_count on target, mark source as completed.
     *
     * @param int $sourceId
     * @param int $targetId
     * @return bool
     */
    public function merge(int $sourceId, int $targetId): bool
    {
        return DB::transaction(function () use ($sourceId, $targetId) {
            $source = DB::table('requests')->where('id', $sourceId)->whereNull('deleted_at')->first();

            if (!$source) {
                return false;
            }

            $incrementBy = max((int) $source->demand_count, 1);

            DB::table('requests')
                ->where('id', $targetId)
                ->increment('demand_count', $incrementBy, ['updated_at' => now()]);

            DB::table('requests')
                ->where('id', $sourceId)
                ->update([
                    'status'     => 'fulfilled',
                    'updated_at' => now(),
                ]);

            return true;
        });
    }

    /**
     * Return request counts grouped by status.
     *
     * @return array
     */
    public function countByStatus(): array
    {
        return DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->toArray();
    }
}
