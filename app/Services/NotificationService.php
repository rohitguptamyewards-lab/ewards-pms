<?php

namespace App\Services;

use App\Repositories\NotificationRepository;

class NotificationService
{
    // Working hours: 9 AM to 6 PM IST
    private const WORK_START_HOUR = 9;
    private const WORK_END_HOUR   = 18;

    // Smart batching times
    private const MORNING_BATCH = '09:00:00';
    private const EVENING_BATCH = '17:30:00';

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {}

    /**
     * Send a notification, respecting working hours and DND.
     * Critical notifications bypass DND.
     */
    public function send(int $userId, string $type, array $data, bool $isCritical = false): int
    {
        $scheduledFor = $isCritical ? now() : $this->getNextDeliveryTime();

        return $this->notificationRepository->create([
            'notifiable_type' => 'team_member',
            'notifiable_id'   => $userId,
            'type'            => $type,
            'data'            => json_encode($data),
            'channel'         => 'in_app',
            'is_critical'     => $isCritical,
            'scheduled_for'   => $scheduledFor,
        ]);
    }

    /**
     * Get the next available delivery time within working hours.
     */
    private function getNextDeliveryTime(): string
    {
        $now = now();
        $hour = (int) $now->format('H');

        // If within working hours, deliver now
        if ($hour >= self::WORK_START_HOUR && $hour < self::WORK_END_HOUR) {
            return $now->toDateTimeString();
        }

        // If after hours, schedule for morning batch next day
        if ($hour >= self::WORK_END_HOUR) {
            return $now->addDay()->format('Y-m-d') . ' ' . self::MORNING_BATCH;
        }

        // If before hours, schedule for morning batch today
        return $now->format('Y-m-d') . ' ' . self::MORNING_BATCH;
    }

    /**
     * Batch notifications — group unread notifications at 9 AM and 5:30 PM.
     */
    public function getScheduledForSending(): array
    {
        return $this->notificationRepository->findScheduledForSending();
    }
}
