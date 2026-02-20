<?php

namespace Soluta\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Soluta\Subscription\Enums\PeriodicityType;
use Soluta\Subscription\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'grace_days' => 0,
            'name' => $this->faker->words(asText: true),
            'price' => $this->faker->numberBetween(0, 100),
            'periodicity' => $this->faker->randomDigitNotNull(),
            'periodicity_type' => $this->faker->randomElement([
                PeriodicityType::Year,
                PeriodicityType::Month,
                PeriodicityType::Week,
                PeriodicityType::Day,
            ]),
        ];
    }

    public function withGraceDays()
    {
        return $this->state([
            'grace_days' => $this->faker->randomDigitNotNull(),
        ]);
    }
}
