<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum TxnLogType: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Table: users
     * Column: user_type (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    case ADD = 'add';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case REMOVE = 'remove';
    case RETURN = 'return';

}
