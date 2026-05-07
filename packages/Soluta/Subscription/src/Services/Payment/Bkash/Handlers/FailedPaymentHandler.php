<?php

namespace Soluta\Subscription\Services\Payment\Bkash\Handlers;

use App\Enums\PaymentStatus;
use Illuminate\Http\Request;

class FailedPaymentHandler extends PaymentStatusHandler
{
    public function handle(Request $request, $invoiceId): mixed
    {
        $payment = $this->findPayment($invoiceId);

        if (! $payment->isCompleted()) {
            $payment->update([
                'gateway_status' => $request->status ?: 'failed',
                'status' => PaymentStatus::FAILED,
                'metadata' => $request->all(),
            ]);
        }

        throw new \Exception('Payment Failed!');
    }
}
