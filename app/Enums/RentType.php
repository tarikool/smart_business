<?php

namespace App\Enums;

use App\Traits\EnumTrait;
use Illuminate\Support\Arr;

enum RentType: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: rental_items
     * Column: rent_type
     *
     * Ensure migrations are updated while changing these values
     */
    case HOUR = 'hour';
    case DAY = 'day';
    case MONTH = 'month';
    case DECIMAL = 'decimal';
    case ACRE = 'acre';

    public static function getLabels()
    {
        return Arr::map(self::values(), function ($value) {
            return [
                'label' => ucfirst($value),
                'value' => $value,
            ];
        });
    }
}
