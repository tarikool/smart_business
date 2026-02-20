<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Soluta\Subscription\Enums\PeriodicityType;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_model_cancalculate_grace_days_end()
    {
        Carbon::setTestNow(now());

        $days = $this->faker->randomDigitNotNull();
        $graceDays = $this->faker->randomDigitNotNull();
        $plan = Plan::factory()->create([
            'grace_days' => $graceDays,
            'periodicity_type' => PeriodicityType::Day,
            'periodicity' => $days,
        ]);

        $this->assertEquals(
            now()->addDays($days)->addDays($graceDays),
            $plan->calculateGraceDaysEnd($plan->calculateNextRecurrenceEnd()),
        );
    }

    public function test_model_can_retrieve_subscriptions()
    {
        $plan = Plan::factory()
            ->create();

        $subscriptions = Subscription::factory()
            ->for($plan)
            ->count($subscriptionsCount = $this->faker->randomDigitNotNull())
            ->started()
            ->notExpired()
            ->notSuppressed()
            ->create();

        $this->assertEquals($subscriptionsCount, $plan->subscriptions()->count());
        $subscriptions->each(function ($subscription) use ($plan) {
            $this->assertTrue($plan->subscriptions->contains($subscription));
        });
    }

    public function test_plan_can_be_created_without_periodicity()
    {
        $plan = Plan::factory()
            ->create([
                'periodicity' => null,
                'periodicity_type' => null,
            ]);

        $this->assertNull($plan->periodicity);
        $this->assertNull($plan->periodicity_type);
    }
}
