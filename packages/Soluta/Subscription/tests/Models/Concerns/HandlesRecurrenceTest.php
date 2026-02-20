<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Soluta\Subscription\Enums\PeriodicityType;
use Soluta\Subscription\Models\Plan;
use Tests\TestCase;

class HandlesRecurrenceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Plan::class;

    public function test_model_calculate_yearly_expiration()
    {
        Carbon::setTestNow(now());

        $years = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Year,
            'periodicity' => $years,
        ]);

        $this->assertEquals(now()->addYears($years), $plan->calculateNextRecurrenceEnd());
    }

    public function test_model_calculate_monthly_expiration()
    {
        Carbon::setTestNow(now());

        $months = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Month,
            'periodicity' => $months,
        ]);

        $this->assertEquals(now()->addMonths($months), $plan->calculateNextRecurrenceEnd());
    }

    public function test_model_calculate_weekly_expiration()
    {
        Carbon::setTestNow(now());

        $weeks = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Week,
            'periodicity' => $weeks,
        ]);

        $this->assertEquals(now()->addWeeks($weeks), $plan->calculateNextRecurrenceEnd());
    }

    public function test_model_calculate_daily_expiration()
    {
        Carbon::setTestNow(now());

        $days = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Day,
            'periodicity' => $days,
        ]);

        $this->assertEquals(now()->addDays($days), $plan->calculateNextRecurrenceEnd());
    }

    public function test_model_calculate_expiration_with_a_different_start()
    {
        Carbon::setTestNow(now());

        $weeks = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Week,
            'periodicity' => $weeks,
        ]);

        $start = now()->subDay();

        $this->assertEquals($start->copy()->addWeeks($weeks), $plan->calculateNextRecurrenceEnd($start));
    }

    public function test_model_calculate_expiration_with_a_different_start_as_string()
    {
        Carbon::setTestNow(today());

        $weeks = $this->faker->randomDigitNotNull();
        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Week,
            'periodicity' => $weeks,
        ]);

        $start = today()->subDay();

        $this->assertEquals(
            $start->copy()->addWeeks($weeks),
            $plan->calculateNextRecurrenceEnd($start->toDateTimeString()),
        );
    }

    public function test_model_calculate_expiration_with_renewal_after_one_month()
    {
        Carbon::setTestNow('2021-02-18');

        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Month,
            'periodicity' => 1,
        ]);

        $start = '2021-02-20';

        $this->assertEquals('2021-03-20', $plan->calculateNextRecurrenceEnd($start)->toDateString());
    }

    public function test_model_calculate_expiration_with_two_renewals_in_one_month()
    {
        Carbon::setTestNow('2021-02-19');

        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Month,
            'periodicity' => 1,
        ]);

        $start = '2021-03-20';

        $this->assertEquals('2021-04-20', $plan->calculateNextRecurrenceEnd($start)->toDateString());
    }

    public function test_model_calculate_expiration_with_three_renewals_in_one_month()
    {
        Carbon::setTestNow('2021-02-20');

        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Month,
            'periodicity' => 1,
        ]);

        $start = '2021-04-20';

        $this->assertEquals('2021-05-20', $plan->calculateNextRecurrenceEnd($start)->toDateString());
    }

    public function test_model_calculate_expiration_with_renewal_after_expiration()
    {
        Carbon::setTestNow('2021-02-21');

        $plan = self::MODEL::factory()->create([
            'periodicity_type' => PeriodicityType::Month,
            'periodicity' => 1,
        ]);

        $start = '2021-02-20';

        $this->assertEquals('2021-03-20', $plan->calculateNextRecurrenceEnd($start)->toDateString());
    }
}
