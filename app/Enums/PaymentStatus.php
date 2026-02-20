<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum PaymentStatus: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: payments
     * Column: status (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

}
