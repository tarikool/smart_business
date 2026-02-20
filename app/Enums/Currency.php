<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum Currency: string
{
    use EnumTrait;

    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case BDT = 'BDT';
}
