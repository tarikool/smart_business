<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Services\Transaction\RecentPriceService;

class UpdateRecentPrice
{
    /**
     * Create the event listener.
     */
    public function __construct(public RecentPriceService $service)
    {
        //
    }

    /**
     * @param  TransactionCompleted  $event
     *                                       Handle the event.
     */
    public function handle($event): void
    {
        $this->service->updateRecentPrice($event->transaction);
    }
}
