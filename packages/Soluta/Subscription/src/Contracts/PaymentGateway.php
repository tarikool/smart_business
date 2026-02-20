<?php

namespace Soluta\Subscription\Contracts;

interface PaymentGateway
{
    public function initiatePayment($planId, $user): array;

    public function handleWebhook($request);
}
