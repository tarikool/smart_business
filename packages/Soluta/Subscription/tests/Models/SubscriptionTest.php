<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Soluta\Subscription\Events\SubscriptionCanceled;
use Soluta\Subscription\Events\SubscriptionRenewed;
use Soluta\Subscription\Events\SubscriptionStarted;
use Soluta\Subscription\Events\SubscriptionSuppressed;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Models\Subscription;
use Tests\Mocks\Models\User;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_model_renews()
    {
        Carbon::setTestNow(now());

        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->addDays(1),
            ]);

        $expectedExpiredAt = $plan->calculateNextRecurrenceEnd($subscription->expired_at)->toDateTimeString();

        Event::fake();

        $subscription->renew();

        Event::assertDispatched(SubscriptionRenewed::class);

        $this->assertDatabaseHas('subscriptions', [
            'plan_id' => $plan->id,
            'subscriber_id' => $subscriber->id,
            'subscriber_type' => User::class,
            'expired_at' => $expectedExpiredAt,
        ]);
    }

    public function test_model_renews_based_on_current_date_if_overdue()
    {
        Carbon::setTestNow(now());

        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        $expectedExpiredAt = $plan->calculateNextRecurrenceEnd()->toDateTimeString();

        Event::fake();

        $subscription->renew();

        Event::assertDispatched(SubscriptionRenewed::class);

        $this->assertDatabaseHas('subscriptions', [
            'plan_id' => $plan->id,
            'subscriber_id' => $subscriber->id,
            'subscriber_type' => User::class,
            'expired_at' => $expectedExpiredAt,
        ]);
    }

    public function test_model_can_cancel()
    {
        Carbon::setTestNow(now());

        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->notStarted()
            ->create();

        Event::fake();

        $subscription->cancel();

        Event::assertDispatched(SubscriptionCanceled::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'canceled_at' => now(),
        ]);
    }

    public function test_model_can_start()
    {
        Carbon::setTestNow(now());

        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->notStarted()
            ->create();

        Event::fake();

        $subscription->start();

        Event::assertDispatched(SubscriptionStarted::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'started_at' => today(),
        ]);
    }

    public function test_model_can_suppress()
    {
        Carbon::setTestNow(now());

        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create();

        Event::fake();

        $subscription->suppress();

        Event::assertDispatched(SubscriptionSuppressed::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'suppressed_at' => now(),
        ]);
    }

    public function test_model_can_mark_as_switched()
    {
        $plan = Plan::factory()->create();
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create();

        $subscription->markAsSwitched()
            ->save();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'was_switched' => true,
        ]);
    }

    public function test_model_registers_renewal()
    {
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($subscriber, 'subscriber')
            ->create();

        $subscription->renew();

        $this->assertDatabaseCount('subscription_renewals', 1);
        $this->assertDatabaseHas('subscription_renewals', [
            'subscription_id' => $subscription->id,
            'renewal' => true,
        ]);
    }

    public function test_model_registers_overdue()
    {
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        $subscription->renew();

        $this->assertDatabaseCount('subscription_renewals', 1);
        $this->assertDatabaseHas('subscription_renewals', [
            'subscription_id' => $subscription->id,
            'overdue' => true,
        ]);
    }

    public function test_model_does_not_register_overdue_if_there_is_no_expiration()
    {
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => null,
            ]);

        $subscription->renew();

        $this->assertDatabaseCount('subscription_renewals', 1);
        $this->assertDatabaseHas('subscription_renewals', [
            'subscription_id' => $subscription->id,
            'overdue' => false,
        ]);
    }

    public function test_model_renews_even_if_plan_has_no_periodicity()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create([
            'periodicity' => null,
            'periodicity_type' => null,
            'grace_days' => 0,
        ]);

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        Event::fake();

        $subscription->renew();

        Event::assertDispatched(SubscriptionRenewed::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'expired_at' => null,
        ]);
    }

    public function test_model_renews_even_if_plan_has_no_periodicity_but_has_grace_days()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create([
            'periodicity' => null,
            'periodicity_type' => null,
            'grace_days' => 1,
        ]);

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        Event::fake();

        $subscription->renew();

        Event::assertDispatched(SubscriptionRenewed::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'expired_at' => null,
            'grace_days_ended_at' => null,
        ]);
    }

    public function test_model_renews_with_defined_expiration_even_if_plan_has_no_periodicity_but_has_grace_days()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create([
            'periodicity' => null,
            'periodicity_type' => null,
            'grace_days' => 1,
        ]);

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        $expiration = now();

        Event::fake();

        $subscription->renew(now());

        Event::assertDispatched(SubscriptionRenewed::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'expired_at' => $expiration->toDateTimeString(),
            'grace_days_ended_at' => $expiration->addDays(1)->toDateTimeString(),
        ]);
    }

    public function test_model_considers_grace_days_on_overdue()
    {
        $subscriber = User::factory()->create();
        $subscription = Subscription::factory()
            ->for($subscriber, 'subscriber')
            ->create([
                'grace_days_ended_at' => now()->addDay(),
                'expired_at' => now()->subDay(),
            ]);

        $subscription->renew();

        $this->assertDatabaseCount('subscription_renewals', 1);
        $this->assertDatabaseHas('subscription_renewals', [
            'subscription_id' => $subscription->id,
            'overdue' => false,
        ]);
    }

    public function test_model_returns_not_started_subscriptions_in_not_active_scope()
    {
        Subscription::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->create();

        $notStartedSubscription = Subscription::factory()
            ->count($notStartedSubscriptionCount = $this->faker()->randomDigitNotNull())
            ->notStarted()
            ->notExpired()
            ->notSuppressed()
            ->create();

        $returnedSubscriptions = Subscription::notActive()->get();

        $this->assertCount($notStartedSubscriptionCount, $returnedSubscriptions);
        $notStartedSubscription->each(
            fn ($subscription) => $this->assertContains($subscription->id, $returnedSubscriptions->pluck('id'))
        );
    }

    public function test_model_returns_expired_subscriptions_in_not_active_scope()
    {
        Subscription::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->create();

        $expiredSubscription = Subscription::factory()
            ->count($expiredSubscriptionCount = $this->faker()->randomDigitNotNull())
            ->started()
            ->expired()
            ->notSuppressed()
            ->create();

        $returnedSubscriptions = Subscription::notActive()->get();

        $this->assertCount($expiredSubscriptionCount, $returnedSubscriptions);
        $expiredSubscription->each(
            fn ($subscription) => $this->assertContains($subscription->id, $returnedSubscriptions->pluck('id'))
        );
    }

    public function test_model_returns_suppressed_subscriptions_in_not_active_scope()
    {
        Subscription::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->create();

        $suppressedSubscription = Subscription::factory()
            ->count($suppressedSubscriptionCount = $this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->suppressed()
            ->create();

        $returnedSubscriptions = Subscription::notActive()->get();

        $this->assertCount($suppressedSubscriptionCount, $returnedSubscriptions);
        $suppressedSubscription->each(
            fn ($subscription) => $this->assertContains($subscription->id, $returnedSubscriptions->pluck('id'))
        );
    }

    public function test_model_returns_only_canceled_subscriptions_with_the_scope()
    {
        Subscription::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->notCanceled()
            ->create();

        $canceledSubscription = Subscription::factory()
            ->count($canceledSubscriptionCount = $this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->canceled()
            ->create();

        $returnedSubscriptions = Subscription::canceled()->get();

        $this->assertCount($canceledSubscriptionCount, $returnedSubscriptions);
        $canceledSubscription->each(
            fn ($subscription) => $this->assertContains($subscription->id, $returnedSubscriptions->pluck('id'))
        );
    }

    public function test_model_returns_only_not_canceled_subscriptions_with_the_scope()
    {
        Subscription::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->canceled()
            ->create();

        $notCanceledSubscription = Subscription::factory()
            ->count($notCanceledSubscriptionCount = $this->faker()->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->notCanceled()
            ->create();

        $returnedSubscriptions = Subscription::notCanceled()->get();

        $this->assertCount($notCanceledSubscriptionCount, $returnedSubscriptions);
        $notCanceledSubscription->each(
            fn ($subscription) => $this->assertContains($subscription->id, $returnedSubscriptions->pluck('id'))
        );
    }

    public function test_model_updates_grace_days_ended_at_when_renewing()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create([
            'grace_days' => $graceDays = $this->faker()->randomDigitNotNull(),
        ]);

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $subscription->renew();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'grace_days_ended_at' => $subscription->expired_at->addDays($graceDays),
        ]);
    }

    public function test_model_leaves_grace_days_empty_when_renewing_if_plan_does_not_have_it()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create([
            'grace_days' => 0,
        ]);

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'grace_days_ended_at' => null,
            ]);

        $subscription->renew();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'grace_days_ended_at' => null,
        ]);
    }

    public function test_model_uses_provided_expiration_at_renewing()
    {
        $subscriber = User::factory()->create();
        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()
            ->for($plan)
            ->for($subscriber, 'subscriber')
            ->create([
                'expired_at' => now()->subDay(),
            ]);

        $expectedExpiredAt = now()->addDays($days = $this->faker()->randomDigitNotNull())->toDateTimeString();

        $subscription->renew(now()->addDays($days));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'expired_at' => $expectedExpiredAt,
        ]);
    }
}
