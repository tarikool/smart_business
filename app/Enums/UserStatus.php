<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum UserStatus: string
{
    use EnumTrait;

    /**
     * This enum is used as a column type in the database.
     *
     * Database Table: surveys
     * Column: survey_by (ENUM type)
     *
     * Ensure migrations are updated while changing these values
     */
    case ACTIVE = '1';
    case INACTIVE = '0';
    case BLOCKED = '-1';
}
