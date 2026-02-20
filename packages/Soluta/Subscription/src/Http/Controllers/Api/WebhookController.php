<?php

namespace Soluta\Subscription\Http\Controllers\Api;

use App\Enums\GatewayType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Soluta\Subscription\Factories\PaymentGatewayFactory;

class WebhookController extends Controller
{
    public function stripeWebhookHandle(Request $request)
    {
        $gateway = PaymentGatewayFactory::create(GatewayType::STRIPE);

        $gateway->handleWebhook($request);

        return $this->successResponse();
    }
}
