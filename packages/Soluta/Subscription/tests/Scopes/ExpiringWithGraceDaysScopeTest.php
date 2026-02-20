<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class ExpiringWithGraceDaysScopeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_expired_models_are_not_returned_by_default()
    {
        $expiredModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($expiredModelsCount)->create([
            'expired_at' => now()->subDay(),
        ]);

        $unexpiredModelsCount = $this->faker()->randomDigitNotNull();
        $unexpiredModels = self::MODEL::factory()->count($unexpiredModelsCount)->create([
            'expired_at' => now()->addDay(),
        ]);

        $modelsWithNullExpiredAtCount = $this->faker()->randomDigitNotNull();
        $modelsWithNullExpired = self::MODEL::factory()->count($modelsWithNullExpiredAtCount)->create([
            'expired_at' => null,
        ]);

        $expectedSubscriptions = $unexpiredModels->concat($modelsWithNullExpired);

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_expired_models_with_grace_days_are_returned_by_default()
    {
        $expiredModelsWithoutGraceDaysCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($expiredModelsWithoutGraceDaysCount)->create([
            'expired_at' => now()->subDay(),
            'grace_days_ended_at' => null,
        ]);

        $expiredModelsWithPastGraceDaysCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($expiredModelsWithPastGraceDaysCount)->create([
            'expired_at' => now()->subDay(),
            'grace_days_ended_at' => now()->subDay(),
        ]);

        $expiredModelsWithFutureGraceDaysCount = $this->faker()->randomDigitNotNull();
        $expiredModelsWithFutureGraceDays = self::MODEL::factory()
            ->count($expiredModelsWithFutureGraceDaysCount)->create([
                'expired_at' => now()->subDay(),
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $expiredModelsWithFutureGraceDays->pluck('id'),
            $returnedSubscriptions->pluck('id'),
        );
    }

    public function test_expired_models_are_returned_when_calling_method_with_expired()
    {
        $expiredModelsCount = $this->faker()->randomDigitNotNull();
        $expiredModels = self::MODEL::factory()->count($expiredModelsCount)->create([
            'expired_at' => now()->subDay(),
        ]);

        $unexpiredModelsCount = $this->faker()->randomDigitNotNull();
        $unexpiredModels = self::MODEL::factory()->count($unexpiredModelsCount)->create([
            'expired_at' => now()->addDay(),
        ]);

        $expiredModelsWithFutureGraceDays = self::MODEL::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->create([
                'expired_at' => now()->subDay(),
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $modelsWithNullExpiredAtCount = $this->faker()->randomDigitNotNull();
        $modelsWithNullExpired = self::MODEL::factory()->count($modelsWithNullExpiredAtCount)->create([
            'expired_at' => null,
        ]);

        $expectedSubscriptions = $expiredModels->concat($unexpiredModels)
            ->concat($expiredModelsWithFutureGraceDays)
            ->concat($modelsWithNullExpired);

        $returnedSubscriptions = self::MODEL::withExpired()->get();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_expired_models_are_not_returned_when_calling_method_with_expired_and_passing_false()
    {
        $expiredModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($expiredModelsCount)->create([
            'expired_at' => now()->subDay(),
        ]);

        $unexpiredModelsCount = $this->faker()->randomDigitNotNull();
        $unexpiredModels = self::MODEL::factory()->count($unexpiredModelsCount)->create([
            'expired_at' => now()->addDay(),
        ]);

        $expiredModelsWithFutureGraceDays = self::MODEL::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->create([
                'expired_at' => now()->subDay(),
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $modelsWithNullExpiredAtCount = $this->faker()->randomDigitNotNull();
        $modelsWithNullExpired = self::MODEL::factory()->count($modelsWithNullExpiredAtCount)->create([
            'expired_at' => null,
        ]);

        $expectedSubscriptions = $unexpiredModels->concat($expiredModelsWithFutureGraceDays)
            ->concat($modelsWithNullExpired);

        $returnedSubscriptions = self::MODEL::withExpired(false)->get();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_only_expired_models_are_returned_when_calling_method_only_expired()
    {
        $expiredModelsCount = $this->faker()->randomDigitNotNull();
        $expiredModels = self::MODEL::factory()->count($expiredModelsCount)->create([
            'expired_at' => now()->subDay(),
        ]);

        $unexpiredModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($unexpiredModelsCount)->create([
            'expired_at' => now()->addDay(),
        ]);

        $modelsWithNullExpiredAtCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($modelsWithNullExpiredAtCount)->create([
            'expired_at' => null,
        ]);

        $expiredModelsWithPastGraceDays = self::MODEL::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->create([
                'expired_at' => now()->subDay(),
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $expiredModelsWithNullGraceDays = self::MODEL::factory()
            ->count($this->faker()->randomDigitNotNull())
            ->create([
                'expired_at' => now()->subDay(),
                'grace_days_ended_at' => null,
            ]);

        $expectedSubscriptions = $expiredModels->concat($expiredModelsWithNullGraceDays)
            ->concat($expiredModelsWithPastGraceDays);

        $returnedSubscriptions = self::MODEL::onlyExpired()->get();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }
}
