<?php

namespace Tests\Feature\Models\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Concerns\ExpiresAndHasGraceDays;
use Soluta\Subscription\Models\Scopes\ExpiringWithGraceDaysScope;
use Soluta\Subscription\Models\Subscription;
use Tests\TestCase;

class ExpiresAndHasGraceDaysTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public const MODEL = Subscription::class;

    public function test_trait_applies_scope()
    {
        $model = self::MODEL::factory()->create();

        $this->assertArrayHasKey(ExpiresAndHasGraceDays::class, class_uses_recursive($model));
        $this->assertArrayHasKey(ExpiringWithGraceDaysScope::class, $model->getGlobalScopes());
    }

    public function test_model_returns_expired_status()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $expiredModelWithFutureGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $expiredModelWithPastGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertTrue($expiredModel->expired());
        $this->assertFalse($expiredModelWithFutureGraceDays->expired());
        $this->assertTrue($expiredModelWithPastGraceDays->expired());
        $this->assertFalse($notExpiredModel->expired());
    }

    public function test_model_returns_not_expired_status()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $modelWithNullExpiredAt = self::MODEL::factory()
            ->expired()
            ->create([
                'expired_at' => null,
            ]);

        $expiredModelWithFutureGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $expiredModelWithPastGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertFalse($expiredModel->notExpired());
        $this->assertTrue($expiredModelWithFutureGraceDays->notExpired());
        $this->assertFalse($expiredModelWithPastGraceDays->notExpired());
        $this->assertTrue($notExpiredModel->notExpired());
        $this->assertTrue($modelWithNullExpiredAt->notExpired());
    }

    public function test_model_returns_if_it_has_expired()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $modelWithNullExpiredAt = self::MODEL::factory()
            ->expired()
            ->create([
                'expired_at' => null,
            ]);

        $expiredModelWithFutureGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $expiredModelWithPastGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertTrue($expiredModel->hasExpired());
        $this->assertFalse($expiredModelWithFutureGraceDays->hasExpired());
        $this->assertTrue($expiredModelWithPastGraceDays->hasExpired());
        $this->assertFalse($notExpiredModel->hasExpired());
        $this->assertFalse($modelWithNullExpiredAt->hasExpired());
    }

    public function test_model_returns_if_it_has_not_expired()
    {
        $expiredModel = self::MODEL::factory()
            ->expired()
            ->create();

        $modelWithNullExpiredAt = self::MODEL::factory()
            ->expired()
            ->create([
                'expired_at' => null,
            ]);

        $expiredModelWithFutureGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->addDay(),
            ]);

        $expiredModelWithPastGraceDays = self::MODEL::factory()
            ->expired()
            ->create([
                'grace_days_ended_at' => now()->subDay(),
            ]);

        $notExpiredModel = self::MODEL::factory()
            ->notExpired()
            ->create();

        $this->assertFalse($expiredModel->hasNotExpired());
        $this->assertTrue($expiredModelWithFutureGraceDays->hasNotExpired());
        $this->assertFalse($expiredModelWithPastGraceDays->hasNotExpired());
        $this->assertTrue($notExpiredModel->hasNotExpired());
        $this->assertTrue($modelWithNullExpiredAt->hasNotExpired());
    }
}
