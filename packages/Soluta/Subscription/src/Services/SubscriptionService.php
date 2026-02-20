<?php

namespace Soluta\Subscription\Services;

use App\Models\User;
use App\Traits\Makeable;
use Soluta\Subscription\Models\Plan;
use Soluta\Subscription\Models\Subscription;

class SubscriptionService
{
    use Makeable;

    /**
     * @param  User  $user
     * @param  Plan  $plan
     * @return Subscription
     */
    public function subscribe($user, $plan)
    {
        $subscription = $user->subscribeTo($plan);

        return $subscription;
    }
}
