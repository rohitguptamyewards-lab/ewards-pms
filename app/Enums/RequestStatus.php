<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Received = 'received';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Deferred = 'deferred';
    case Rejected = 'rejected';
    case Completed = 'completed';
}
