<?php

namespace Soluta\Subscription\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FeaturePlan extends Pivot
{
    public function feature()
    {
        return $this->belongsTo(config('soulbscription.models.feature'));
    }

    public function plan()
    {
        return $this->belongsTo(config('soulbscription.models.plan'));
    }
}
