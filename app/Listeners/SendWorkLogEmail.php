<?php

namespace App\Listeners;

use App\Events\WorkLogCreated;
use App\Services\EmailNotificationService;

class SendWorkLogEmail
{
    public function __construct(
        private readonly EmailNotificationService $emailService,
    ) {}

    public function handle(WorkLogCreated $event): void
    {
        $this->emailService->onWorkLogCreated(
            $event->projectId,
            $event->userId,
            $event->hoursSpent,
        );
    }
}
