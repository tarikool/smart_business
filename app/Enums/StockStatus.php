<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum StockStatus: int
{
    use EnumTrait;

    case LOW = 20;
    case MEDIUM = 60;

    public static function threshold()
    {
        return [
            'low' => self::LOW,
            'medium' => self::MEDIUM,
        ];
    }
}
