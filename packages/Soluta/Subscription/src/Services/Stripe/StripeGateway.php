<?php

namespace Soluta\Subscription\Services\Stripe;

use Soluta\Subscription\Contracts\PaymentGateway;
use Soluta\Subscription\Services\Stripe\Action\HandleWebhook;
use Soluta\Subscription\Services\Stripe\Action\InitiatePayment;

class StripeGateway implements PaymentGateway
{
    public $secret;

    public $endpointSecret;

    public function __construct()
    {
        $this->secret = config('services.stripe.secret');
        $this->endpointSecret = config('services.stripe.webhook_secret');
    }

    public function initiatePayment($planId, $user): array
    {
        return InitiatePayment::make($this->secret)->execute($planId, $user);
    }

    public function handleWebhook($request)
    {
        HandleWebhook::make($this->secret, $this->endpointSecret)->handle($request);
    }
}
