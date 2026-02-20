<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum PaymentMethod: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: transactions
     * Column: payment_method (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    case CASH = 'cash';
    case DIGITAL = 'digital';

}
