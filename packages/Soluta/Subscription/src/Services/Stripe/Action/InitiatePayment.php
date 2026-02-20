<?php

namespace Soluta\Subscription\Services\Stripe\Action;

use App\Enums\Currency;
use App\Enums\GatewayType;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Traits\Makeable;
use Soluta\Subscription\Models\Payment;
use Soluta\Subscription\Models\PaymentGateway;
use Soluta\Subscription\Models\Plan;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidArgumentException;
use Stripe\StripeClient;

class InitiatePayment
{
    use Makeable;

    public $stripe;

    public function __construct(public $secret)
    {
        $this->stripe = new StripeClient($this->secret);
    }

    /**
     * @param  int  $plan
     * @param  User  $user
     * @return array
     *
     * @throws ApiErrorException
     * @throws \Throwable
     */
    public function execute($planId, $user)
    {
        $plan = Plan::find($planId);
        $customer = $this->getOrCreateCustomer($user);
        $payment = $this->saveLocalRecord($user, $plan);
        $amount = round($payment->amount * 100);

        $ephemeralKey = $this->stripe->ephemeralKeys->create(
            ['customer' => $customer->id],
            ['stripe_version' => config('services.stripe.version')]
        );

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => Currency::USD->value,
            'customer' => $customer->id,
            'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
            'setup_future_usage' => 'on_session',
            'metadata' => ['payment_id' => $payment->id, 'user_id' => $user->id, 'plan_id' => $plan->id],
        ]);

        $payment->update(['gateway_txn_id' => $paymentIntent->id]);

        return [
            'setupIntent' => $paymentIntent->client_secret,
            'ephemeralKey' => $ephemeralKey->secret,
            'customer' => $customer->id,
            'publishableKey' => config('services.stripe.key'),
            'id' => $paymentIntent->id,
        ];
    }

    public function saveLocalRecord($user, $plan)
    {
        $gateway = PaymentGateway::whereName(GatewayType::STRIPE)->first();

        return Payment::create([
            'user_id' => $user->id,
            'amount' => $plan->price,
            'currency' => Currency::USD,
            'plan_id' => $plan->id,
            'gateway_id' => $gateway?->id,
            'status' => PaymentStatus::PENDING,
        ]);
    }

    /**
     * @return Customer
     *
     * @throws ApiErrorException
     * @throws \Throwable
     */
    public function getOrCreateCustomer($user)
    {
        try {
            $customer = $this->stripe->customers->retrieve($user->stripe_customer_id);
            if ($customer->isDeleted()) {
                return $this->createCustomer($user);
            }

            return $customer;
        } catch (ApiErrorException|InvalidArgumentException $e) {
            return $this->createCustomer($user);
        } catch (\Throwable $e) {
            throw $e;
        }

    }

    /**
     * @param  User  $user
     * @return Customer
     *
     * @throws ApiErrorException
     */
    public function createCustomer($user)
    {
        $customerData = $user->email ? ['email' => $user->email] : ['phone' => $user->phone_number];
        $customerData['name'] = $user->name;
        $customer = $this->stripe->customers->create($customerData);
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }
}
