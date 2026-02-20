<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class SuppressingScopeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_suppressed_models_are_not_returned_by_default()
    {
        $suppressedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()
            ->count($suppressedModelsCount)
            ->suppressed()
            ->notExpired()
            ->started()
            ->create();

        $notSuppressedModelsCount = $this->faker()->randomDigitNotNull();
        $notSuppressedModels = self::MODEL::factory()
            ->count($notSuppressedModelsCount)
            ->notSuppressed()
            ->notExpired()
            ->started()
            ->create();

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $notSuppressedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_suppressed_models_are_not_returned_when_calling_without_not_suppressed()
    {
        $suppressedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()
            ->count($suppressedModelsCount)
            ->suppressed()
            ->notExpired()
            ->started()
            ->create();

        $notSuppressedModelsCount = $this->faker()->randomDigitNotNull();
        $notSuppressedModels = self::MODEL::factory()
            ->count($notSuppressedModelsCount)
            ->notSuppressed()
            ->notExpired()
            ->started()
            ->create();

        $returnedSubscriptions = self::MODEL::withoutSuppressed()->get();

        $this->assertEqualsCanonicalizing(
            $notSuppressedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_suppressed_models_are_returned_when_calling_method_with_not_suppressed()
    {
        $suppressedModelsCount = $this->faker()->randomDigitNotNull();
        $suppressedModels = self::MODEL::factory()
            ->count($suppressedModelsCount)
            ->suppressed()
            ->notExpired()
            ->started()
            ->create();

        $notSuppressedModelsCount = $this->faker()->randomDigitNotNull();
        $notSuppressedModels = self::MODEL::factory()
            ->count($notSuppressedModelsCount)
            ->notSuppressed()
            ->notExpired()
            ->started()
            ->create();

        $expectedSubscriptions = $suppressedModels->concat($notSuppressedModels);

        $returnedSubscriptions = self::MODEL::withSuppressed()->get();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_suppressed_models_are_returned_when_calling_method_with_not_suppressed_and_passing_a_false()
    {
        $suppressedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()
            ->count($suppressedModelsCount)
            ->suppressed()
            ->notExpired()
            ->started()
            ->create();

        $notSuppressedModelsCount = $this->faker()->randomDigitNotNull();
        $notSuppressedModels = self::MODEL::factory()
            ->count($notSuppressedModelsCount)
            ->notSuppressed()
            ->notExpired()
            ->started()
            ->create();

        $returnedSubscriptions = self::MODEL::withSuppressed(false)->get();

        $this->assertEqualsCanonicalizing(
            $notSuppressedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_only_suppressed_models_are_returned_when_calling_method_only_not_suppressed()
    {
        $suppressedModelsCount = $this->faker()->randomDigitNotNull();
        $suppressedModels = self::MODEL::factory()
            ->count($suppressedModelsCount)
            ->suppressed()
            ->notExpired()
            ->started()
            ->create();

        $notSuppressedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()
            ->count($notSuppressedModelsCount)
            ->notSuppressed()
            ->notExpired()
            ->started()
            ->create();

        $returnedSubscriptions = self::MODEL::onlySuppressed()->get();

        $this->assertEqualsCanonicalizing(
            $suppressedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }
}
