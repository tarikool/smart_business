<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum GatewayType: string
{
    use EnumTrait;

    case STRIPE = 'stripe';
    case BKASH = 'bkash';

    // Used in seeder
    public function countryCode()
    {
        return match ($this) {
            self::STRIPE => 'BD',
            default => null,
        };
    }

    public static function gateways($isoCode)
    {
        return match ($isoCode) {
            'BD' => [
                self::STRIPE,
                self::BKASH,
            ],

            default => [
                self::STRIPE,
            ]
        };
    }
}
