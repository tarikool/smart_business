<?php

namespace Soluta\Subscription\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Soluta\Subscription\Database\Factories\SubscriptionFactory;
use Soluta\Subscription\Events\SubscriptionCanceled;
use Soluta\Subscription\Events\SubscriptionRenewed;
use Soluta\Subscription\Events\SubscriptionScheduled;
use Soluta\Subscription\Events\SubscriptionStarted;
use Soluta\Subscription\Events\SubscriptionSuppressed;
use Soluta\Subscription\Models\Concerns\Expires;
use Soluta\Subscription\Models\Concerns\Starts;
use Soluta\Subscription\Models\Concerns\Suppresses;
use Soluta\Subscription\Models\Scopes\StartingScope;
use Soluta\Subscription\Models\Scopes\SuppressingScope;

#[UseFactory(SubscriptionFactory::class)]
class Subscription extends Model
{
    use Expires, HasFactory,Starts, Suppresses;

    protected $dates = [
        'canceled_at',
    ];

    protected $fillable = [
        'canceled_at',
        'expired_at',
        'grace_days_ended_at',
        'started_at',
        'suppressed_at',
        'was_switched',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function renewals()
    {
        return $this->hasMany(config('soulbscription.models.subscription_renewal'));
    }

    public function subscriber()
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function scopeNotActive(Builder $query)
    {
        return $query->withoutGlobalScopes([
            Expires::class,
            StartingScope::class,
            SuppressingScope::class,
        ])
            ->where(function (Builder $query) {
                $query->where(fn (Builder $query) => $query->onlyExpired())
                    ->orWhere(fn (Builder $query) => $query->onlyNotStarted())
                    ->orWhere(fn (Builder $query) => $query->onlySuppressed());
            });
    }

    public function scopeCanceled(Builder $query)
    {
        return $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled(Builder $query)
    {
        return $query->whereNull('canceled_at');
    }

    public function markAsSwitched(): self
    {
        return $this->fill([
            'was_switched' => true,
        ]);
    }

    public function start(?Carbon $startDate = null): self
    {
        $startDate = $startDate ?: today();

        $this->fill(['started_at' => $startDate])
            ->save();

        if ($startDate->isToday()) {
            event(new SubscriptionStarted($this));
        } elseif ($startDate->isFuture()) {
            event(new SubscriptionScheduled($this));
        }

        return $this;
    }

    public function renew(?Carbon $expirationDate = null): self
    {
        $this->renewals()->create([
            'renewal' => true,
            'overdue' => $this->isOverdue,
        ]);

        $expirationDate = $this->getRenewedExpiration($expirationDate);
        $graceDaysEndedAt = null;

        if ($this->plan->grace_days && $expirationDate) {
            $graceDaysEndedAt = $expirationDate->copy()->addDays($this->plan->grace_days);
        }

        $this->update([
            'expired_at' => $expirationDate,
            'grace_days_ended_at' => $graceDaysEndedAt,
        ]);

        event(new SubscriptionRenewed($this));

        return $this;
    }

    public function cancel(?Carbon $cancelDate = null): self
    {
        $cancelDate = $cancelDate ?: now();

        $this->fill(['canceled_at' => $cancelDate])
            ->save();

        event(new SubscriptionCanceled($this));

        return $this;
    }

    public function suppress(?Carbon $suppressation = null)
    {
        $suppressationDate = $suppressation ?: now();

        $this->fill(['suppressed_at' => $suppressationDate])
            ->save();

        event(new SubscriptionSuppressed($this));

        return $this;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->grace_days_ended_at) {
            return $this->expired_at->isPast()
                and $this->grace_days_ended_at->isPast();
        }

        if (! $this->expired_at) {
            return false;
        }

        return $this->expired_at->isPast();
    }

    private function getRenewedExpiration(?Carbon $expirationDate = null)
    {
        if (! empty($expirationDate)) {
            return $expirationDate;
        }

        if (empty($this->plan->periodicity)) {
            return null;
        }

        if ($this->isOverdue) {
            return $this->plan->calculateNextRecurrenceEnd();
        }

        return $this->plan->calculateNextRecurrenceEnd($this->expired_at);
    }
}
