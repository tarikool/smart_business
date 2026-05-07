<?php

namespace Soluta\Subscription\Services\Payment\Bkash\Action;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentGatewayEnum;
use App\Models\User;
use App\Traits\Makeable;
use Illuminate\Support\Facades\Http;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Services\Payment\BaseInitiatePayment;

class InitiatePayment extends BaseInitiatePayment
{
    use Makeable;

    public $headers = [];

    public $body = [];

    public $baseUrl = [];

    public function __construct(public $token)
    {
        $this->baseUrl = config('services.bkash.base_url');
        $this->setHeaders();
    }

    protected function getGatewayIdentifier(): PaymentGatewayEnum
    {
        return PaymentGatewayEnum::BKASH;
    }

    protected function getDefaultCurrency(): CurrencyEnum
    {
        return CurrencyEnum::BDT;
    }

    /**
     * @param  User  $user
     * @return array
     */
    public function execute($planId, $user)
    {
        $plan = Plan::find($planId);
        $amount = $this->getAmount($plan, $user);
        $paymentLog = $this->savePaymentLog($user, $plan, $amount);
        $this->setBody($paymentLog);
        $bkashPayment = $this->createBkashPayment();
        $paymentLog->update([
            'gateway_txn_id' => $bkashPayment['paymentID'],
            'gateway_status' => $bkashPayment['transactionStatus'],
        ]);

        return [
            'paymentID' => $bkashPayment['paymentID'],
            'bkashURL' => $bkashPayment['bkashURL'],
            'amount' => $bkashPayment['amount'],
        ];
    }

    public function createBkashPayment()
    {
        $response = Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->post('/tokenized/checkout/create', $this->body);

        $isSuccess = $response->json('statusCode') === '0000' and $response->json('paymentID');

        if (! $isSuccess) {
            $errorMsg = $response['statusMessage'] ?? $response['message'] ?? 'bKash Grant Token Failed';
            throw new \Exception($errorMsg, 400);
        }

        return $response->json();
    }

    protected function getAmount(Plan $plan): float
    {
        return $plan->getLocalPrice();
    }

    public function setBody($paymentLog)
    {
        $this->body = [
            'mode' => '0011',
            'payerReference' => "user_{$paymentLog->user_id}_plan_{$paymentLog->plan_id}",
            'callbackURL' => route('webhooks.bkash'),
            'amount' => $paymentLog->amount,
            'currency' => CurrencyEnum::BDT->value,
            'intent' => 'sale',
            'merchantInvoiceNumber' => $paymentLog->id,
        ];
    }

    public function setHeaders()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->token,
            'X-App-Key' => config('services.bkash.app_key'),
        ];
    }
}
