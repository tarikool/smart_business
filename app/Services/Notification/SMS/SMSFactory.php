<?php

namespace App\Services\Notification\SMS;

use App\Exceptions\ClientException;
use App\Services\Notification\SMS\Provider\SSLProvider;

class SMSFactory
{
    public static function make(?string $countryCode)
    {
        return match ($countryCode) {
            'BD' => app(SSLProvider::class),
            default => throw new ClientException('SMS is not available in your region yet.'),
        };
    }
}
