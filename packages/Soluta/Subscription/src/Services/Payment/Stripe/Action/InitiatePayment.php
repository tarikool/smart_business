<?php

namespace Soluta\Subscription\Services\Payment\Stripe\Action;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentGatewayEnum;
use App\Models\User;
use App\Traits\Makeable;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Services\Payment\BaseInitiatePayment;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidArgumentException;
use Stripe\StripeClient;

class InitiatePayment extends BaseInitiatePayment
{
    use Makeable;

    public $stripe;

    public function __construct(public $secret)
    {
        $this->stripe = new StripeClient($this->secret);
    }

    protected function getAmount(Plan $plan): float
    {
        return round($plan->price * 100);
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
        $amount = $this->getAmount($plan);
        $payment = $this->savePaymentLog($user, $plan, $amount * 0.01);
        abort_unless($amount > 0, 422, 'Amount must be greater than 0.');

        $customer = $this->getOrCreateCustomer($user);
        $ephemeralKey = $this->stripe->ephemeralKeys->create(
            ['customer' => $customer->id],
            ['stripe_version' => config('services.stripe.version')]
        );

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => CurrencyEnum::USD->value,
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
            'amount' => $payment->amount,
        ];
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

    protected function getGatewayIdentifier(): PaymentGatewayEnum
    {
        return PaymentGatewayEnum::STRIPE;
    }

    protected function getDefaultCurrency(): CurrencyEnum
    {
        return CurrencyEnum::USD;
    }
}
