<?php

namespace Soluta\Subscription\Services\Payment\Bkash\Handlers;

use Illuminate\Http\Request;
use Soluta\Subscription\Models\Payment;
use Soluta\Subscription\Services\Payment\Bkash\BkashGatewayService;
use Soluta\Subscription\Services\SubscriptionService;

abstract class PaymentStatusHandler
{
    public function __construct(
        protected BkashGatewayService $gateway,
        protected SubscriptionService $subscriptionService
    ) {}

    abstract public function handle(Request $request, $invoiceId): mixed;

    protected function findPayment(string $invoiceId): Payment
    {
        return Payment::where('id', $invoiceId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
