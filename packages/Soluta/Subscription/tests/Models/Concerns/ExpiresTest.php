<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\FeatureConsumption;
use Tests\TestCase;

class ExpiresTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = FeatureConsumption::class;

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

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $unexpiredModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
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

        $expectedSubscriptions = $expiredModels->concat($unexpiredModels);

        $returnedSubscriptions = self::MODEL::withExpired()->get();

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

        $returnedSubscriptions = self::MODEL::onlyExpired()->get();

        $this->assertEqualsCanonicalizing(
            $expiredModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_model_returns_expired_status()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertTrue($expiredModel->expired());
        $this->assertFalse($notExpiredModel->expired());
    }

    public function test_model_returns_not_expired_status()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertFalse($expiredModel->notExpired());
        $this->assertTrue($notExpiredModel->notExpired());
    }

    public function test_models_without_expiration_date_are_returned_by_default()
    {
        $expiredModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($expiredModelsCount)->create([
            'expired_at' => now()->subDay(),
        ]);

        $unexpiredModelsCount = $this->faker()->randomDigitNotNull();
        $unexpiredModels = self::MODEL::factory()->count($unexpiredModelsCount)->create([
            'expired_at' => null,
        ]);

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $unexpiredModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }
}
