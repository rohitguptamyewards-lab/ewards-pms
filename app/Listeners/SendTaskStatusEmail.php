<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Services\EmailNotificationService;

class SendTaskStatusEmail
{
    public function __construct(
        private readonly EmailNotificationService $emailService,
    ) {}

    public function handle(TaskStatusChanged $event): void
    {
        $this->emailService->onTaskStatusChanged(
            $event->projectId,
            $event->taskId,
            $event->oldStatus,
            $event->newStatus,
        );
    }
}
