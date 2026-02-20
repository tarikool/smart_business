<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class SuppressesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_model_returns_suppressed_when_suppressed_at_is_on_the_past()
    {
        $model = self::MODEL::factory()->make([
            'suppressed_at' => now()->subDay(),
        ]);

        $this->assertTrue($model->suppressed());
        $this->assertFalse($model->notSuppressed());
    }

    public function test_model_returns_not_suppressed_when_suppressed_at_is_on_the_future()
    {
        $model = self::MODEL::factory()->make([
            'suppressed_at' => now()->addDay(),
        ]);

        $this->assertFalse($model->suppressed());
        $this->assertTrue($model->notSuppressed());
    }

    public function test_model_returns_not_suppressed_when_suppressed_at_is_null()
    {
        $model = self::MODEL::factory()->make();
        $model->suppressed_at = null;

        $this->assertFalse($model->suppressed());
        $this->assertTrue($model->notSuppressed());
    }
}
