<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use OverflowException;
use ReflectionClass;
use Soluta\Subscription\Events\FeatureConsumed;
use Soluta\Subscription\Events\FeatureTicketCreated;
use Soluta\Subscription\Events\SubscriptionScheduled;
use Soluta\Subscription\Events\SubscriptionStarted;
use Soluta\Subscription\Events\SubscriptionSuppressed;
use Soluta\Subscription\Models\Feature;
use Soluta\Subscription\Models\FeatureConsumption;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Models\Subscription;
use Soluta\Subscription\Models\SubscriptionRenewal;
use Tests\Mocks\Models\User;
use Tests\TestCase;

class HasSubscriptionsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_model_can_subscribe_to_a_plan()
    {
        $plan = Plan::factory()->createOne();
        $subscriber = User::factory()->createOne();

        Event::fake();

        $subscription = $subscriber->subscribeTo($plan);

        Event::assertDispatched(SubscriptionStarted::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'plan_id' => $plan->id,
            'subscriber_id' => $subscriber->id,
            'started_at' => today(),
            'expired_at' => $plan->calculateNextRecurrenceEnd(),
            'grace_days_ended_at' => null,
        ]);
    }

    public function test_model_defines_grace_days_end()
    {
        $plan = Plan::factory()
            ->withGraceDays()
            ->createOne();

        $subscriber = User::factory()->createOne();
        $subscription = $subscriber->subscribeTo($plan);

        $this->assertDatabaseHas('subscriptions', [
            'grace_days_ended_at' => $plan->calculateGraceDaysEnd($subscription->expired_at),
        ]);
    }

    public function test_model_can_switch_to_a_plan()
    {
        Carbon::setTestNow(now());

        $oldPlan = Plan::factory()->createOne();
        $newPlan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $oldSubscription = $subscriber->subscribeTo($oldPlan);

        Event::fake();

        $newSubscription = $subscriber->switchTo($newPlan);

        Event::assertDispatched(SubscriptionStarted::class);
        Event::assertDispatched(SubscriptionSuppressed::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $newSubscription->id,
            'plan_id' => $newPlan->id,
            'subscriber_id' => $subscriber->id,
            'started_at' => today(),
            'expired_at' => $newPlan->calculateNextRecurrenceEnd(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $oldSubscription->id,
            'suppressed_at' => now(),
            'was_switched' => true,
        ]);
    }

    public function test_model_can_schedule_switch_to_a_plan()
    {
        Carbon::setTestNow(now());

        $oldPlan = Plan::factory()->createOne();
        $newPlan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $oldSubscription = $subscriber->subscribeTo($oldPlan);

        Event::fake();

        $newSubscription = $subscriber->switchTo($newPlan, immediately: false);

        Event::assertDispatched(SubscriptionScheduled::class);
        Event::assertNotDispatched(SubscriptionStarted::class);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $newSubscription->id,
            'plan_id' => $newPlan->id,
            'started_at' => $oldSubscription->expired_at,
            'expired_at' => $newPlan->calculateNextRecurrenceEnd($oldSubscription->expired_at),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $oldSubscription->id,
            'was_switched' => true,
        ]);
    }

    public function test_model_get_new_subscription_after_switching()
    {
        $oldPlan = Plan::factory()->createOne();
        $newPlan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($oldPlan, startDate: now()->subDay());

        $newSubscription = $subscriber->switchTo($newPlan);

        $this->assertTrue($newSubscription->is($subscriber->fresh()->subscription));
    }

    public function test_model_get_current_subscription_after_schedule_a_switch()
    {
        Carbon::setTestNow(now());

        $oldPlan = Plan::factory()->createOne();
        $newPlan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $oldSubscription = $subscriber->subscribeTo($oldPlan);

        $subscriber->switchTo($newPlan, immediately: false);

        $this->assertTrue($oldSubscription->is($subscriber->fresh()->subscription));
    }

    public function test_model_can_consume_a_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscription = $subscriber->subscribeTo($plan);

        Event::fake();

        $subscriber->consume($feature->name, $consumption);

        Event::assertDispatched(FeatureConsumed::class);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
            'expired_at' => $feature->calculateNextRecurrenceEnd($subscription->started_at),
        ]);
    }

    public function test_model_can_consume_a_not_consumable_feature_if_it_is_available()
    {
        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->notConsumable()->createOne();
        $feature->plans()->attach($plan);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => null,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_model_cant_consume_an_unavailable_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan, now()->subDay());

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('None of the active plans grants access to this feature.');

        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseMissing('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_model_cant_consume_a_feature_beyond_its_charges()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $charges + 1;

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('The feature has no enough charges to this consumption.');

        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseMissing('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_model_can_consume_some_amount_of_a_consumable_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $modelCanUse = $subscriber->canConsume($feature->name, $consumption);

        $this->assertTrue($modelCanUse);
    }

    public function test_model_cant_consume_some_amount_of_a_consumable_feature_from_an_expired_subscription()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan, now()->subDay());

        $modelCanUse = $subscriber->canConsume($feature->name, $consumption);

        $this->assertFalse($modelCanUse);
    }

    public function test_model_cant_consume_some_amount_of_a_consumable_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $charges + 1;

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $modelCanUse = $subscriber->canConsume($feature->name, $consumption);

        $this->assertFalse($modelCanUse);
    }

    public function test_model_can_consume_some_amount_of_a_consumable_feature_if_its_consumptions_are_expired()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        FeatureConsumption::factory()
            ->for($feature)
            ->for($subscriber, 'subscriber')
            ->createOne([
                'consumption' => $this->faker->numberBetween(5, 10),
                'expired_at' => now()->subDay(),
            ]);

        $modelCanUse = $subscriber->canConsume($feature->name, $consumption);

        $this->assertTrue($modelCanUse);
    }

    public function test_model_has_subscription_renewals()
    {
        $subscriber = User::factory()->createOne();
        $subscription = Subscription::factory()
            ->for($subscriber, 'subscriber')
            ->createOne();

        $renewalsCount = $this->faker->randomDigitNotNull();
        $renewals = SubscriptionRenewal::factory()
            ->times($renewalsCount)
            ->for($subscription)
            ->createOne();

        $this->assertEqualsCanonicalizing(
            $renewals->pluck('id'),
            $subscriber->renewals->pluck('id'),
        );
    }

    public function test_model_has_feature_tickets()
    {
        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        $ticket = $subscriber->featureTickets()->make(['expired_at' => now()->addDay()]);
        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $this->assertSame(
            $ticket->id,
            $subscriber->featureTickets->first()->id,
        );
    }

    public function test_model_feature_tickets_gets_only_not_expired()
    {
        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        $expiredTicket = $subscriber->featureTickets()->make([
            'expired_at' => now()->subDay(),
        ]);

        $expiredTicket->feature()->associate($feature);
        $expiredTicket->save();

        $activeTicket = $subscriber->featureTickets()->make([
            'expired_at' => now()->addDay(),
        ]);

        $activeTicket->feature()->associate($feature);
        $activeTicket->save();

        config()->set('soulbscription.feature_tickets', true);

        $this->assertContains(
            $activeTicket->id,
            $subscriber->featureTickets->pluck('id'),
        );

        $this->assertNotContains(
            $expiredTicket->id,
            $subscriber->featureTickets->pluck('id'),
        );
    }

    public function test_model_get_features_from_tickets()
    {
        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        $ticket = $subscriber->featureTickets()->make([
            'expired_at' => now()->addDay(),
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $this->assertContains(
            $feature->id,
            $subscriber->features->pluck('id')->toArray(),
        );
    }

    public function test_model_get_features_from_previously_loaded_tickets()
    {
        $feature = Feature::factory()->createOne();
        $subscriber = User::factory()->createOne();

        $reflection = new ReflectionClass($subscriber);
        $property = $reflection->getProperty('loadedTicketFeatures');
        $property->setAccessible(true);
        $property->setValue($subscriber, Collection::make([$feature]));

        config()->set('soulbscription.feature_tickets', true);

        $features = $subscriber->getFeaturesAttribute();

        $this->assertCount(1, $features);
        $this->assertTrue($features->contains($feature));
    }

    public function test_model_get_features_from_previously_loaded_subscription()
    {
        $feature = Feature::factory()->createOne();
        $subscriber = User::factory()->createOne();

        $reflection = new ReflectionClass($subscriber);
        $property = $reflection->getProperty('loadedSubscriptionFeatures');
        $property->setAccessible(true);
        $property->setValue($subscriber, Collection::make([$feature]));

        config()->set('soulbscription.feature_tickets', true);

        $features = $subscriber->getFeaturesAttribute();

        $this->assertCount(1, $features);
        $this->assertTrue($features->contains($feature));
    }

    public function test_model_get_features_from_non_expirable_tickets()
    {
        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        $ticket = $subscriber->featureTickets()->make([
            'expired_at' => null,
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $this->assertContains(
            $feature->id,
            $subscriber->features->pluck('id')->toArray(),
        );
    }

    public function test_model_can_consume_some_amount_of_a_consumable_feature_from_a_ticket()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $feature = Feature::factory()->consumable()->createOne();
        $subscriber = User::factory()->createOne();

        $ticket = $subscriber->featureTickets()->make([
            'charges' => $charges,
            'expired_at' => now()->addDay(),
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $modelCanUse = $subscriber->canConsume($feature->name, $consumption);

        $this->assertTrue($modelCanUse);
    }

    public function test_model_can_retrieve_total_charges_for_a_feature_considering_tickets()
    {
        $subscriptionFeatureCharges = $this->faker->numberBetween(5, 10);
        $ticketFeatureCharges = $this->faker->numberBetween(5, 10);

        $feature = Feature::factory()->consumable()->createOne();

        $plan = Plan::factory()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $subscriptionFeatureCharges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $ticket = $subscriber->featureTickets()->make([
            'charges' => $ticketFeatureCharges,
            'expired_at' => now()->addDay(),
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $totalCharges = $subscriber->getTotalCharges($feature->name);

        $this->assertEquals($totalCharges, $subscriptionFeatureCharges + $ticketFeatureCharges);
    }

    public function test_model_can_consume_a_not_consumable_feature_from_a_ticket()
    {
        $feature = Feature::factory()->notConsumable()->createOne();
        $subscriber = User::factory()->createOne();

        $ticket = $subscriber->featureTickets()->make([
            'expired_at' => now()->addDay(),
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);

        $modelCanUse = $subscriber->canConsume($feature->name);

        $this->assertTrue($modelCanUse);
    }

    public function test_model_can_retrieve_total_consumptions_for_a_feature()
    {
        $consumption = $this->faker->randomDigitNotNull();

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);
        $subscriber->featureConsumptions()
            ->make([
                'consumption' => $consumption,
                'expired_at' => now()->addDay(),
            ])
            ->feature()
            ->associate($feature)
            ->save();

        config()->set('soulbscription.feature_tickets', true);

        $receivedConsumption = $subscriber->getCurrentConsumption($feature->name);

        $this->assertEquals($consumption, $receivedConsumption);
    }

    public function test_model_can_retrieve_remaining_charges_for_a_feature()
    {
        $charges = $this->faker->numberBetween(6, 10);
        $consumption = $this->faker->numberBetween(1, 5);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->consumable()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);
        $subscriber->featureConsumptions()
            ->make([
                'consumption' => $consumption,
                'expired_at' => now()->addDay(),
            ])
            ->feature()
            ->associate($feature)
            ->save();

        config()->set('soulbscription.feature_tickets', true);

        $receivedRemainingCharges = $subscriber->getRemainingCharges($feature->name);

        $this->assertEquals($charges - $consumption, $receivedRemainingCharges);
    }

    public function test_model_cant_use_charges_from_expired_tickets()
    {
        $feature = Feature::factory()->consumable()->createOne();
        $subscriber = User::factory()->createOne();

        $plan = Plan::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriptionFeatureCharges = $this->faker->numberBetween(5, 10);
        $feature->plans()->attach($plan, [
            'charges' => $subscriptionFeatureCharges,
        ]);

        $activeTicketCharges = $this->faker->numberBetween(5, 10);
        $activeTicket = $subscriber->featureTickets()->make([
            'charges' => $activeTicketCharges,
            'expired_at' => now()->addDay(),
        ]);

        $activeTicket->feature()->associate($feature);
        $activeTicket->save();

        $expiredTicketCharges = $this->faker->numberBetween(5, 10);
        $expiredTicket = $subscriber->featureTickets()->make([
            'charges' => $expiredTicketCharges,
            'expired_at' => now()->subDay(),
        ]);

        $expiredTicket->feature()->associate($feature);
        $expiredTicket->save();

        config()->set('soulbscription.feature_tickets', true);

        $totalCharges = $subscriber->getTotalCharges($feature->name);

        $this->assertEquals($totalCharges, $subscriptionFeatureCharges + $activeTicketCharges);
    }

    public function test_it_ignores_tickets_when_it_is_disabled()
    {
        $feature = Feature::factory()->consumable()->createOne();
        $subscriber = User::factory()->createOne();

        $plan = Plan::factory()->createOne();
        $plan->features()->attach($feature);
        $subscriber->subscribeTo($plan);

        $ticket = $subscriber->featureTickets()->make([
            'expired_at' => now()->addDay(),
        ]);

        $ticket->feature()->associate($feature);
        $ticket->save();

        config()->set('soulbscription.feature_tickets', true);
        $featuresWithTickets = User::first()->features;

        config()->set('soulbscription.feature_tickets', false);
        $featuresWithoutTickets = User::first()->features;

        $this->assertCount(2, $featuresWithTickets);
        $this->assertCount(1, $featuresWithoutTickets);
    }

    public function test_it_can_create_a_ticket()
    {
        $charges = $this->faker->randomDigitNotNull();
        $expiration = $this->faker->dateTime();

        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        config()->set('soulbscription.feature_tickets', true);

        $subscriber->giveTicketFor($feature->name, $expiration, $charges);

        $this->assertDatabaseHas('feature_tickets', [
            'charges' => $charges,
            'expired_at' => $expiration,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_it_can_create_a_non_expirable_ticket()
    {
        $charges = $this->faker->randomDigitNotNull();

        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        config()->set('soulbscription.feature_tickets', true);

        $subscriber->giveTicketFor($feature->name, null, $charges);

        $this->assertDatabaseHas('feature_tickets', [
            'charges' => $charges,
            'expired_at' => null,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_it_fires_event_when_creating_a_ticket()
    {
        $charges = $this->faker->randomDigitNotNull();
        $expiration = $this->faker->dateTime();

        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        config()->set('soulbscription.feature_tickets', true);

        Event::fake();

        $subscriber->giveTicketFor($feature->name, $expiration, $charges);

        Event::assertDispatched(FeatureTicketCreated::class);
    }

    public function test_it_raises_an_exception_when_creating_a_ticket_for_a_non_existing_feature()
    {
        $charges = $this->faker->randomDigitNotNull();
        $expiration = $this->faker->dateTime();

        $unexistingFeatureName = $this->faker->word();

        $subscriber = User::factory()->createOne();

        $this->expectException(ModelNotFoundException::class);

        config()->set('soulbscription.feature_tickets', true);

        $subscriber->giveTicketFor($unexistingFeatureName, $expiration, $charges);
    }

    public function test_it_raises_an_exception_when_creating_a_ticket_despite_it_is_disabled()
    {
        $charges = $this->faker->randomDigitNotNull();
        $expiration = $this->faker->dateTime();

        $feature = Feature::factory()->consumable()->createOne();

        $subscriber = User::factory()->createOne();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The tickets are not enabled in the configs.');

        config()->set('soulbscription.feature_tickets', false);

        $subscriber->giveTicketFor($feature->name, $expiration, $charges);
    }

    public function test_it_create_a_not_expirable_consumption_for_quota_features()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->quota()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
            'expired_at' => null,
        ]);
    }

    public function test_it_does_not_create_new_consumptions_for_quoe_features()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges / 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->quota()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);
        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseCount('feature_consumptions', 1);
        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption * 2,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
            'expired_at' => null,
        ]);
    }

    public function test_it_can_set_quota_feature_consumption()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges / 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->quota()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);
        $subscriber->consume($feature->name, $consumption);
        $subscriber->setConsumedQuota($feature->name, $consumption);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
            'expired_at' => null,
        ]);
    }

    public function test_it_does_nothing_while_setting_quota_if_the_given_amount_is_the_same_as_the_balance()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->quota()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);
        $subscriber->setConsumedQuota($feature->name, $consumption);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
            'expired_at' => null,
        ]);
    }

    public function test_it_raises_an_exception_when_setting_consumed_quota_for_a_not_quota_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges / 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->notQuota()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The feature is not a quota feature.');

        $subscriber->setConsumedQuota($feature->name, $consumption);
    }

    public function test_it_checks_if_the_user_has_subscription_to_a_plan()
    {
        $plan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $hasSubscription = $subscriber->hasSubscriptionTo($plan);
        $isSubscribed = $subscriber->isSubscribedTo($plan);

        $this->assertTrue($hasSubscription);
        $this->assertTrue($isSubscribed);
    }

    public function test_it_checks_if_the_user_does_not_have_subscription_to_a_plan()
    {
        $plan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $hasSubscription = $subscriber->missingSubscriptionTo($plan);
        $isSubscribed = $subscriber->isNotSubscribedTo($plan);

        $this->assertFalse($hasSubscription);
        $this->assertFalse($isSubscribed);
    }

    public function test_it_returns_the_last_subscription_when_retrieving_expired()
    {
        $plan = Plan::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan, now()->subDay(), now()->subDay());
        $expectedSubscription = $subscriber->subscribeTo($plan, now()->subHour(), now()->subHour());

        $returnedSubscription = $subscriber->lastSubscription();

        $this->assertEquals($expectedSubscription->id, $returnedSubscription->id);
    }

    public function test_it_can_consume_a_feature_after_its_charges_if_this_feature_is_postpaid()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween(1, $charges * 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->postpaid()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_it_does_not_return_negative_charges_for_features()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween($charges + 1, $charges * 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->postpaid()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);

        $this->assertEquals(0, $subscriber->getRemainingCharges($feature->name));
    }

    public function test_it_returns_negative_balance_for_postpaid_features()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween($charges + 1, $charges * 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->postpaid()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $subscriber->consume($feature->name, $consumption);

        $this->assertLessThan(0, $subscriber->balance($feature->name));
    }

    public function test_it_returns_zero_for_unavailable_features()
    {
        $feature = Feature::factory()->createOne();
        $subscriber = User::factory()->createOne();

        $remainingCharges = $subscriber->getRemainingCharges($feature->name);

        $this->assertEquals(0, $remainingCharges);
    }

    public function test_it_returns_remaining_charges_only_for_the_given_user()
    {
        config(['soulbscription.feature_tickets' => true]);

        $charges = $this->faker->numberBetween(5, 10);

        $feature = Feature::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->giveTicketFor($feature->name, null, $charges);

        $otherSubscriber = User::factory()->createOne();
        $otherSubscriber->giveTicketFor($feature->name, null, $charges);

        $this->assertEquals($charges, $subscriber->getRemainingCharges($feature->name));
    }

    public function test_it_can_check_if_subscriber_has_feature()
    {
        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->createOne();
        $feature->plans()->attach($plan);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $this->assertTrue($subscriber->hasFeature($feature->name));
    }

    public function test_it_can_check_if_subscriber_does_not_have_feature()
    {
        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->createOne();

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $this->assertFalse($subscriber->hasFeature($feature->name));
    }

    public function test_it_can_always_consume_a_postpaid_feature()
    {
        $charges = $this->faker->numberBetween(5, 10);
        $consumption = $this->faker->numberBetween($charges + 1, $charges * 2);

        $plan = Plan::factory()->createOne();
        $feature = Feature::factory()->postpaid()->createOne();
        $feature->plans()->attach($plan, [
            'charges' => $charges,
        ]);

        $subscriber = User::factory()->createOne();
        $subscriber->subscribeTo($plan);

        $this->assertTrue($subscriber->canConsume($feature->name, $consumption));

        $subscriber->consume($feature->name, $consumption);

        $this->assertDatabaseHas('feature_consumptions', [
            'consumption' => $consumption,
            'feature_id' => $feature->id,
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function test_it_sets_an_empty_expiration_if_the_plan_has_no_periodicity()
    {
        $plan = Plan::factory()->createOne([
            'periodicity' => null,
        ]);

        $subscriber = User::factory()->createOne();
        $subscription = $subscriber->subscribeTo($plan);

        $this->assertNull($subscription->expired_at);
    }

    public function test_it_uses_received_expiration_even_if_the_plan_has_no_periodicity()
    {
        $plan = Plan::factory()->createOne([
            'periodicity' => null,
        ]);

        $subscriber = User::factory()->createOne();
        $subscription = $subscriber->subscribeTo($plan, now()->addDay());

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'expired_at' => now()->addDay(),
        ]);
    }

    public function test_it_returns_zero_for_current_consumption_when_subscriber_does_not_have_feature()
    {
        $feature = Feature::factory()->createOne();
        $subscriber = User::factory()->createOne();

        $currentConsumption = $subscriber->getCurrentConsumption($feature->name);

        $this->assertEquals(0, $currentConsumption);
    }

    public function test_it_returns_zero_for_total_charges_when_subscriber_does_not_have_feature()
    {
        $feature = Feature::factory()->createOne();
        $subscriber = User::factory()->createOne();

        $totalCharges = $subscriber->getTotalCharges($feature->name);

        $this->assertEquals(0, $totalCharges);
    }
}
