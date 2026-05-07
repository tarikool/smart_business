<?php

namespace Soluta\Subscription\Services\Payment;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentStatus;
use App\Models\User;
use Soluta\Subscription\Models\Payment;
use Soluta\Subscription\Models\PaymentGateway;
use Soluta\Subscription\Models\Plan;

abstract class BaseInitiatePayment
{
    abstract protected function getGatewayIdentifier(): PaymentGatewayEnum;

    abstract protected function getDefaultCurrency(): CurrencyEnum;

    abstract protected function getAmount(Plan $plan): float;

    abstract public function execute($planId, $user);

    /**
     * @param  User  $user
     * @param  Plan  $plan
     * @return Payment
     */
    public function savePaymentLog($user, $plan, $amount)
    {
        $gateway = PaymentGateway::where('identifier', PaymentGatewayEnum::STRIPE)->first();

        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $this->getDefaultCurrency(),
            'plan_id' => $plan->id,
            'gateway_id' => $gateway?->id,
            'status' => PaymentStatus::PENDING,
        ]);
    }
}
