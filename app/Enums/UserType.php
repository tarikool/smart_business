<?php

namespace App\Enums;

use App\Traits\EnumTrait;
use Illuminate\Support\Arr;

enum UserType: string
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
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';
    case ENTERPRISE = 'enterprise';
    case MERCHANT = 'merchant';
    case NETWORK_MANAGER = 'network_manager';
    case STAFF = 'staff';

    public static function getPublicUserTypes()
    {
        return array_column(
            [
                self::MERCHANT,
                self::NETWORK_MANAGER,
            ], 'value');
    }

    public function accessRoles()
    {
        return match ($this) {
            self::SUPER_ADMIN => self::cases(),
        };
    }

    public static function getLabels()
    {
        return Arr::map(self::getPublicUserTypes(), function ($value) {
            return [
                'label' => __("common.user_types.$value"),
                'value' => $value,
            ];
        });
    }

    public static function getAllLabels()
    {
        return Arr::map(self::values(), function ($value) {
            return [
                'label' => __("common.user_types.$value"),
                'value' => $value,
            ];
        });
    }
}
