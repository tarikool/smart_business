<?php

namespace App\Providers;

use App\Events\TransactionCompleted;
use App\Listeners\CustomerSummerySubscriber;
use App\Listeners\UpdateRecentPrice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listeners = [
        TransactionCompleted::class => [
            UpdateRecentPrice::class,
        ],
    ];

    protected $subscribers = [
        CustomerSummerySubscriber::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        foreach ($this->listeners as $event => $listeners) {
            foreach (Arr::wrap($listeners) as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach (Arr::wrap($this->subscribers) as $subscriber) {
            Event::subscribe($subscriber);
        }
    }
}
