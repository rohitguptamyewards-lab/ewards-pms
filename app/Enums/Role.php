<?php

namespace App\Enums;

enum Role: string
{
    case CTO = 'cto';
    case CEO = 'ceo';
    case Sales = 'sales';
    case Developer = 'developer';
    case Tester = 'tester';
    case Analyst = 'analyst';
}
