<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class ModuleDebtScoreRepository
{
    /**
     * Get all debt scores for a module, ordered by week_date desc.
     *
     * @param int $moduleId
     * @return array
     */
    public function findByModule(int $moduleId): array
    {
        return DB::table('module_debt_scores')
            ->where('module_id', $moduleId)
            ->orderByDesc('week_date')
            ->get()
            ->toArray();
    }

    /**
     * Get the most recent debt score for a module.
     *
     * @param int $moduleId
     * @return stdClass|null
     */
    public function findLatest(int $moduleId): ?stdClass
    {
        return DB::table('module_debt_scores')
            ->where('module_id', $moduleId)
            ->orderByDesc('week_date')
            ->first() ?: null;
    }

    /**
     * Get the latest debt score per module, joined with modules.name.
     *
     * @return array
     */
    public function findLatestAll(): array
    {
        // Use a subquery to get the max week_date per module, then join back
        $latestDates = DB::table('module_debt_scores')
            ->select('module_id', DB::raw('MAX(week_date) as max_week_date'))
            ->groupBy('module_id');

        return DB::table('module_debt_scores')
            ->joinSub($latestDates, 'latest', function ($join) {
                $join->on('module_debt_scores.module_id', '=', 'latest.module_id')
                     ->on('module_debt_scores.week_date', '=', 'latest.max_week_date');
            })
            ->join('modules', 'module_debt_scores.module_id', '=', 'modules.id')
            ->select(
                'module_debt_scores.*',
                'modules.name as module_name'
            )
            ->orderBy('modules.name')
            ->get()
            ->toArray();
    }

    /**
     * Insert or update a debt score based on the unique [module_id, week_date] pair.
     *
     * @param array $data
     * @return void
     */
    public function upsert(array $data): void
    {
        $existing = DB::table('module_debt_scores')
            ->where('module_id', $data['module_id'])
            ->where('week_date', $data['week_date'])
            ->first();

        if ($existing) {
            $data['updated_at'] = now();
            DB::table('module_debt_scores')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('module_debt_scores')->insert($data);
        }
    }

    /**
     * Get all module debt scores for a given week date.
     *
     * @param string $weekDate
     * @return array
     */
    public function findByWeek(string $weekDate): array
    {
        return DB::table('module_debt_scores')
            ->join('modules', 'module_debt_scores.module_id', '=', 'modules.id')
            ->select(
                'module_debt_scores.*',
                'modules.name as module_name'
            )
            ->where('module_debt_scores.week_date', $weekDate)
            ->get()
            ->toArray();
    }
}
