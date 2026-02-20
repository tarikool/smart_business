<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class StartingScopeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_not_started_models_are_not_returned_by_default()
    {
        $startedModelsCount = $this->faker()->randomDigitNotNull();
        $startedModels = self::MODEL::factory()->count($startedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->subDay(),
        ]);

        $notStartedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($notStartedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->addDay(),
        ]);

        $returnedSubscriptions = self::MODEL::all();

        $this->assertEqualsCanonicalizing(
            $startedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_not_started_models_are_not_returned_when_calling_without_not_started()
    {
        $startedModelsCount = $this->faker()->randomDigitNotNull();
        $startedModels = self::MODEL::factory()->count($startedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->subDay(),
        ]);

        $notStartedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($notStartedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->addDay(),
        ]);

        $returnedSubscriptions = self::MODEL::withoutNotStarted()->get();

        $this->assertEqualsCanonicalizing(
            $startedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_started_models_are_returned_when_calling_method_with_not_started()
    {
        $startedModelsCount = $this->faker()->randomDigitNotNull();
        $startedModels = self::MODEL::factory()->count($startedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->subDay(),
        ]);

        $notStartedModelsCount = $this->faker()->randomDigitNotNull();
        $notStartedModels = self::MODEL::factory()->count($notStartedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->addDay(),
        ]);

        $expectedSubscriptions = $startedModels->concat($notStartedModels);

        $returnedSubscriptions = self::MODEL::withNotStarted()->get();

        $this->assertEqualsCanonicalizing(
            $expectedSubscriptions->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_not_started_models_are_returned_when_calling_method_with_not_started_and_passing_a_false()
    {
        $startedModelsCount = $this->faker()->randomDigitNotNull();
        $startedModels = self::MODEL::factory()->count($startedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->subDay(),
        ]);

        $notStartedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($notStartedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->addDay(),
        ]);

        $returnedSubscriptions = self::MODEL::withNotStarted(false)->get();

        $this->assertEqualsCanonicalizing(
            $startedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }

    public function test_only_started_models_are_returned_when_calling_method_only_not_started()
    {
        $startedModelsCount = $this->faker()->randomDigitNotNull();
        self::MODEL::factory()->count($startedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->subDay(),
        ]);

        $notStartedModelsCount = $this->faker()->randomDigitNotNull();
        $notStartedModels = self::MODEL::factory()->count($notStartedModelsCount)->create([
            'expired_at' => now()->addDay(),
            'started_at' => now()->addDay(),
        ]);

        $returnedSubscriptions = self::MODEL::onlyNotStarted()->get();

        $this->assertEqualsCanonicalizing(
            $notStartedModels->pluck('id')->toArray(),
            $returnedSubscriptions->pluck('id')->toArray(),
        );
    }
}
