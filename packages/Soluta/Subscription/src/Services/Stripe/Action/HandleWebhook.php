<?php

namespace Soluta\Subscription\Services\Stripe\Action;

use App\Enums\PaymentStatus;
use App\Traits\Makeable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Soluta\Subscription\Models\Payment;
use Soluta\Subscription\Models\StripeProcessedEvent;
use Soluta\Subscription\Services\SubscriptionService;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class HandleWebhook
{
    use Makeable;

    public function __construct(public $secret, public $endpointSecret)
    {
        Stripe::setApiKey($this->secret);
    }

    /**
     * @param  Request  $request
     * @return void
     */
    public function handle($request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('STRIPE-SIGNATURE');
        $event = Webhook::constructEvent($payload, $sigHeader, $this->endpointSecret);

        DB::transaction(function () use ($event) {
            StripeProcessedEvent::create([
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            match ($event->type) {
                'payment_intent.succeeded' => $this->handleSuccess($event->type, $event->data->object),
                'payment_intent.payment_failed' => $this->handleFailed($event->type, $event->data->object),
                default => null
            };
        });

    }

    /**
     * @param  PaymentIntent  $paymentIntent
     * @return void
     */
    public function handleSuccess($status, $paymentIntent)
    {
        $payment = Payment::find($paymentIntent->metadata->payment_id);

        abort_if(! $payment, 404, 'Payment not found');
        abort_if($payment->isCompleted(), 400, 'Payment already processed');
        abort_if($payment->gateway_txn_id !== $paymentIntent->id, 400, 'Payment ID mismatch');

        $expectedAmount = round($payment->amount * 100);
        abort_if($paymentIntent->amount != $expectedAmount, 400, 'Amount mismatch');
        $subscription = SubscriptionService::make()->subscribe($payment->user, $payment->plan);
        $payment->update([
            'gateway_status' => $status,
            'status' => PaymentStatus::SUCCEEDED,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * @param  string  $status
     * @param  PaymentIntent  $paymentIntent
     * @return void
     */
    public function handleFailed($status, $paymentIntent)
    {
        $payment = Payment::find($paymentIntent->metadata->payment_id);

        if (! $payment->isCompleted()) {
            $payment->update([
                'gateway_status' => $status,
                'status' => PaymentStatus::FAILED,
            ]);
        }

    }
}
