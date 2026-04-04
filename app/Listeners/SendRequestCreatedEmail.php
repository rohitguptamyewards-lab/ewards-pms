<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Services\EmailNotificationService;

class SendRequestCreatedEmail
{
    public function __construct(
        private readonly EmailNotificationService $emailService,
    ) {}

    public function handle(RequestCreated $event): void
    {
        $this->emailService->onRequestCreated(
            $event->requestId,
            $event->title,
            $event->urgency,
        );
    }
}
