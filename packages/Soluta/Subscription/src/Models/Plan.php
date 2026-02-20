<?php

namespace Soluta\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Soluta\Subscription\Database\Factories\PlanFactory;
use Soluta\Subscription\Models\Concerns\HandlesRecurrence;

#[UseFactory(PlanFactory::class)]
class Plan extends Model
{
    use HandlesRecurrence;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['id'];

    public function features()
    {
        return $this->belongsToMany(config('soulbscription.models.feature'))
            ->using(config('soulbscription.models.feature_plan'))
            ->withPivot(['charges']);
    }

    public function subscriptions()
    {
        return $this->hasMany(config('soulbscription.models.subscription'));
    }

    public function calculateGraceDaysEnd(Carbon $recurrenceEnd)
    {
        return $recurrenceEnd->copy()->addDays($this->grace_days);
    }

    public function getHasGraceDaysAttribute()
    {
        return ! empty($this->grace_days);
    }
}
