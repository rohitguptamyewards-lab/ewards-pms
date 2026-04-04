<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class NotificationRepository
{
    public function findByUser(int $userId, int $perPage = 20)
    {
        return DB::table('pms_notifications')
            ->where('notifiable_type', 'team_member')
            ->where('notifiable_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findUnread(int $userId): array
    {
        return DB::table('pms_notifications')
            ->where('notifiable_type', 'team_member')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function countUnread(int $userId): int
    {
        return DB::table('pms_notifications')
            ->where('notifiable_type', 'team_member')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('pms_notifications')->insertGetId($data);
    }

    public function markAsRead(int $id): bool
    {
        return DB::table('pms_notifications')
            ->where('id', $id)
            ->update(['read_at' => now()]) > 0;
    }

    public function markAllAsRead(int $userId): int
    {
        return DB::table('pms_notifications')
            ->where('notifiable_type', 'team_member')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function findScheduledForSending(): array
    {
        return DB::table('pms_notifications')
            ->whereNull('sent_at')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->get()
            ->toArray();
    }

    public function markAsSent(int $id): bool
    {
        return DB::table('pms_notifications')
            ->where('id', $id)
            ->update(['sent_at' => now()]) > 0;
    }
}
