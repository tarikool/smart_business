<?php

namespace Soluta\Subscription\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Soluta\Subscription\Models\Subscription;

class SubscriptionSuppressed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Subscription $subscription,
    ) {
        //
    }
}
