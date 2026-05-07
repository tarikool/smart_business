<?php

namespace Soluta\Subscription\Services\Payment\Bkash;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Soluta\Subscription\Contracts\PaymentGateway;
use Soluta\Subscription\Services\Payment\Bkash\Action\HandleWebhook;
use Soluta\Subscription\Services\Payment\Bkash\Action\InitiatePayment;
use Soluta\Subscription\Services\SubscriptionService;

class BkashGatewayService implements PaymentGateway
{
    public string $baseUrl;

    public array $headers = [];

    public function __construct(public BkashTokenService $tokenService)
    {
        $this->baseUrl = config('services.bkash.base_url');
        $this->setHeaders();
    }

    public function initiatePayment($planId, $user): array
    {
        $token = $this->tokenService->getToken();

        return InitiatePayment::make($token)->execute($planId, $user);
    }

    public function handleWebhook($request)
    {
        HandleWebhook::make($this, app(SubscriptionService::class))->handle($request);
    }

    public function executePayment(string $paymentId): Response
    {
        return Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->post('/tokenized/checkout/execute', ['paymentID' => $paymentId]);

    }

    public function getPaymentStatus(string $paymentId): Response
    {
        return Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->post('/tokenized/checkout/payment/status', ['paymentID' => $paymentId]);
    }

    private function setHeaders(): void
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->tokenService->getToken(),
            'X-App-Key' => config('services.bkash.app_key'),
        ];
    }
}
