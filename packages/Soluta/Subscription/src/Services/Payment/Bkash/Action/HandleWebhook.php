<?php

namespace Soluta\Subscription\Services\Payment\Bkash\Action;

use App\Traits\Makeable;
use Illuminate\Http\Request;
use Soluta\Subscription\Services\Payment\Bkash\BkashGatewayService;
use Soluta\Subscription\Services\Payment\Bkash\Handlers\FailedPaymentHandler;
use Soluta\Subscription\Services\Payment\Bkash\Handlers\PaymentStatusHandler;
use Soluta\Subscription\Services\Payment\Bkash\Handlers\SuccessPaymentHandler;
use Soluta\Subscription\Services\SubscriptionService;

class HandleWebhook
{
    use Makeable;

    private array $handlers = [
        'success' => SuccessPaymentHandler::class,
        'failed' => FailedPaymentHandler::class,
        'cancel' => FailedPaymentHandler::class,
    ];

    public function __construct(
        private BkashGatewayService $gateway,
        private SubscriptionService $subscriptionService
    ) {}

    public function handle(Request $request)
    {
        $paymentId = $request->paymentID;
        abort_unless($paymentId, 404, 'Payment id not found');

        // First call bKash to get merchant invoice number
        $response = $this->gateway->getPaymentStatus($paymentId);
        $invoiceId = $response->json('merchantInvoice');

        abort_unless($invoiceId, 400, 'Merchant invoice not found in bKash response');

        $status = $request->status ?: 'failed';
        $handler = $this->getHandler($status);

        return $handler->handle($request, $invoiceId);

    }

    private function getHandler(string $status): PaymentStatusHandler
    {
        $handlerClass = $this->handlers[$status] ?? FailedPaymentHandler::class;

        return new $handlerClass($this->gateway, $this->subscriptionService);
    }
}
