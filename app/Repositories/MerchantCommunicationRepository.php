<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class MerchantCommunicationRepository
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = DB::table('merchant_communications')
            ->leftJoin('merchants', 'merchant_communications.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'merchant_communications.team_member_id', '=', 'team_members.id')
            ->leftJoin('features', 'merchant_communications.feature_id', '=', 'features.id')
            ->select(
                'merchant_communications.*',
                'merchants.name as merchant_name',
                'team_members.name as team_member_name',
                'features.title as feature_title'
            )
            ->whereNull('merchant_communications.deleted_at');

        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_communications.merchant_id', $filters['merchant_id']);
        }
        if (!empty($filters['team_member_id'])) {
            $query->where('merchant_communications.team_member_id', $filters['team_member_id']);
        }
        if (!empty($filters['channel'])) {
            $query->where('merchant_communications.channel', $filters['channel']);
        }
        if (isset($filters['commitment_made'])) {
            $query->where('merchant_communications.commitment_made', $filters['commitment_made']);
        }

        return $query->orderByDesc('merchant_communications.created_at')->paginate($perPage);
    }

    public function findById(int $id): ?object
    {
        return DB::table('merchant_communications')
            ->leftJoin('merchants', 'merchant_communications.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'merchant_communications.team_member_id', '=', 'team_members.id')
            ->leftJoin('features', 'merchant_communications.feature_id', '=', 'features.id')
            ->select(
                'merchant_communications.*',
                'merchants.name as merchant_name',
                'team_members.name as team_member_name',
                'features.title as feature_title'
            )
            ->where('merchant_communications.id', $id)
            ->whereNull('merchant_communications.deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('merchant_communications')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = now();

        return DB::table('merchant_communications')
            ->where('id', $id)
            ->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return DB::table('merchant_communications')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]) > 0;
    }

    public function findWithSlippedFeatures(): array
    {
        return DB::table('merchant_communications')
            ->join('features', 'merchant_communications.feature_id', '=', 'features.id')
            ->join('merchants', 'merchant_communications.merchant_id', '=', 'merchants.id')
            ->where('merchant_communications.commitment_made', true)
            ->whereNotNull('merchant_communications.commitment_date')
            ->where('merchant_communications.commitment_date', '<', now()->toDateString())
            ->whereNotIn('features.status', ['released'])
            ->whereNull('merchant_communications.deleted_at')
            ->select(
                'merchant_communications.*',
                'merchants.name as merchant_name',
                'features.title as feature_title',
                'features.status as feature_status'
            )
            ->get()
            ->toArray();
    }
}
