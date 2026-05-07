<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Enums\OtpPurpose;
use App\Traits\Makeable;
use Illuminate\Support\Facades\Cache;

class NotificationManager
{
    use Makeable;

    protected $otp;

    protected $data = [];

    public function __construct(public $identifier, public OtpPurpose $purpose, ...$data)
    {
        $this->data = $data;
    }

    public function generateOtp()
    {
        $key = "otp:{$this->purpose->value}:{$this->identifier}";
        $this->otp = random_int(100000, 999999);
        Cache::put($key, $this->otp, now()->addMinutes(10));

        return $this;
    }

    public function send()
    {
        $channel = $this->getChannel($this->identifier);
        $notificationService = NotificationFactory::make($channel);

        return $notificationService->send(
            identifier: $this->identifier,
            purpose: $this->purpose,
            otp: $this->otp,
            data: $this->data
        );
    }

    /**
     * @return NotificationChannel
     *
     * @throws \Throwable
     */
    public function getChannel($identifier)
    {
        $channel = NotificationChannel::fromIdentifier($identifier);
        throw_unless($channel, 'Contact no is not valid');

        return $channel;
    }

    public function getNotificationService(NotificationChannel $channel)
    {
        $notificationService = NotificationFactory::make($channel);
        throw_unless($notificationService, 'No service found for channel');

        return $notificationService;
    }
}
