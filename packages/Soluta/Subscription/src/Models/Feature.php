<?php

namespace Soluta\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Soluta\Subscription\Database\Factories\FeatureFactory;
use Soluta\Subscription\Models\Concerns\HandlesRecurrence;

#[UseFactory(FeatureFactory::class)]
class Feature extends Model
{
    use HandlesRecurrence, HasFactory;

    protected $guarded = ['id'];

    public function plans()
    {
        return $this->belongsToMany(Plan::class)->using(FeaturePlan::class);
    }

    public function tickets()
    {
        return $this->hasMany(config('soulbscription.models.feature_ticket'));
    }
}
