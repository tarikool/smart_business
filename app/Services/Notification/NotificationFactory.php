<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Services\Notification\Email\EmailService;
use App\Services\Notification\SMS\SMSFactory;
use App\Services\Notification\SMS\SMSService;
use Illuminate\Foundation\Application;

class NotificationFactory
{
    /**
     * @return EmailService|SMSFactory|Application|mixed|object|null
     */
    public static function make(NotificationChannel $channel)
    {
        return match ($channel) {
            NotificationChannel::SMS => app(SMSService::class),
            NotificationChannel::EMAIL => app(EmailService::class),
        };
    }
}
