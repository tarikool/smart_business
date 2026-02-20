<?php

namespace Soluta\Subscription\Http\Controllers\Api;

use App\Enums\GatewayType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Soluta\Subscription\Factories\PaymentGatewayFactory;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'provider' => ['required', Rule::enum(GatewayType::class)->only(GatewayType::STRIPE)],
        ]);

        $provider = GatewayType::from($request->provider);
        $gateway = PaymentGatewayFactory::create($provider);
        $data = $gateway->initiatePayment($request->plan_id, $request->user());

        return $this->successResponse($data);
    }
}
