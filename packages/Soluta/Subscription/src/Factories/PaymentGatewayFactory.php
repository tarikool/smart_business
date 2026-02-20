<?php

namespace Soluta\Subscription\Factories;

use App\Enums\GatewayType;
use Soluta\Subscription\Services\Stripe\StripeGateway;

class PaymentGatewayFactory
{
    public static function create(GatewayType $provider)
    {
        return match ($provider) {
            GatewayType::STRIPE => new StripeGateway,

            default => throw new \Exception('Unsupported gateway'),
        };
    }
}
