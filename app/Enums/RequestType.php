<?php

namespace App\Enums;

enum RequestType: string
{
    case Bug = 'bug';
    case NewFeature = 'new_feature';
    case Improvement = 'improvement';
}
