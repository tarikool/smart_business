<?php

namespace Soluta\Subscription\Services\Payment\Bkash\Handlers;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Soluta\Subscription\Models\Payment;

class SuccessPaymentHandler extends PaymentStatusHandler
{
    public function handle(Request $request, $invoiceId): mixed
    {
        $response = $this->gateway->executePayment($request->paymentID);

        DB::beginTransaction();
        try {
            $payment = $this->findPayment($invoiceId);
            $this->validatePayment($payment, $response);
            $subscription = $this->subscriptionService->subscribe($payment->user, $payment->plan);
            $payment->update([
                'gateway_status' => $response['transactionStatus'],
                'status' => PaymentStatus::SUCCEEDED,
                'subscription_id' => $subscription->id,
            ]);
            DB::commit();

            return $subscription;
        } catch (\Exception $exception) {
            DB::rollBack();

            if (! $exception instanceof ModelNotFoundException) {
                $payment->update([
                    'gateway_status' => $response->json('transactionStatus'),
                    'status' => PaymentStatus::FAILED,
                    'metadata' => $response->json(),
                ]);
            }

            throw $exception;
        }

    }

    private function validatePayment(Payment $payment, $response): void
    {
        $isSuccess = $response->json('statusCode') === '0000'
            && $response->json('transactionStatus') === 'Completed';

        if (! $isSuccess) {
            $errorMsg = $response['statusMessage'] ?? $response['message'] ?? 'Payment Failed';
            throw new \Exception($errorMsg, 400);
        }

        abort_if($payment->isCompleted(), 400, 'Payment already processed');
        abort_if($payment->amount != $response->json('amount'), 400, 'Amount mismatch');
    }
}
