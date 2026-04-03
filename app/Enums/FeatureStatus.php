<?php

namespace App\Enums;

enum FeatureStatus: string
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case ReadyForQA = 'ready_for_qa';
    case InQA = 'in_qa';
    case ReadyForRelease = 'ready_for_release';
    case Released = 'released';
    case Deferred = 'deferred';
    case Rejected = 'rejected';
}
