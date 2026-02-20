<?php

namespace Soluta\Subscription\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Soluta\Subscription\Models\Feature;
use Soluta\Subscription\Models\FeatureTicket;

class FeatureTicketCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public $subscriber,
        public Feature $feature,
        public FeatureTicket $featureTicket,
    ) {
        //
    }
}
