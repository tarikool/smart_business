<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Events\TransactionUpdated;
use App\Services\Report\CustomerSummeryService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;

class CustomerSummerySubscriber implements ShouldBeUnique, ShouldQueue
{
    public $tries = 2;

    public $maxExceptions = 3;

    public $uniqueFor = 10;

    public $queue = 'transaction';

    public function __construct(public CustomerSummeryService $service) {}

    /**
     * @param  TransactionCompleted  $event
     *                                       Handle the event.
     */
    public function handleTransactionCreate(object $event): void
    {
        Cache::lock("customer-summery:$event->action:{$event->transaction->id}", 5)->get(function () use ($event) {
            $this->service->updateSummeryOnCreate($event->transaction);
        });
    }

    /**
     * @param  TransactionUpdated  $event
     */
    public function handleTransactionUpdate(object $event): void
    {
        Cache::lock("customer-summery:$event->action:{$event->transaction->id}", 5)->get(function () use ($event) {
            $this->service->updateSummeryOnUpdate($event->transaction);
        });
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            TransactionCompleted::class => 'handleTransactionCreate',
            TransactionUpdated::class => 'handleTransactionUpdate',
        ];
    }

    /**
     * @param  TransactionCompleted|TransactionUpdated  $event
     */
    public function middleware($event): array
    {
        return [(new WithoutOverlapping("customer-summery:$event->action:{$event->transaction->id}"))->dontRelease()];
    }
}
