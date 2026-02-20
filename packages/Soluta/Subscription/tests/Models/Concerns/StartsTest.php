<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class StartsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_model_returns_started_when_started_at_is_on_the_past()
    {
        $model = self::MODEL::factory()->make([
            'started_at' => now()->subDay(),
        ]);

        $this->assertTrue($model->started());
        $this->assertFalse($model->notStarted());
    }

    public function test_model_returns_not_started_when_started_at_is_on_the_future()
    {
        $model = self::MODEL::factory()->make([
            'started_at' => now()->addDay(),
        ]);

        $this->assertFalse($model->started());
        $this->assertTrue($model->notStarted());
    }

    public function test_model_returns_not_started_when_started_at_is_null()
    {
        $model = self::MODEL::factory()->make();
        $model->started_at = null;

        $this->assertFalse($model->started());
        $this->assertTrue($model->notStarted());
    }
}
