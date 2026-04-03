<?php

namespace App\Enums;

enum RequestUrgency: string
{
    case MerchantBlocked = 'merchant_blocked';
    case MerchantUnhappy = 'merchant_unhappy';
    case NiceToHave = 'nice_to_have';
}
