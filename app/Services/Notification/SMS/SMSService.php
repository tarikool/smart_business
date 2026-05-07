<?php

namespace App\Services\Notification\SMS;

use App\Contracts\NotificationInterface;
use App\Enums\OtpPurpose;
use App\Exceptions\ClientException;
use Illuminate\Support\Facades\RateLimiter;
use Propaganistas\LaravelPhone\PhoneNumber;

class SMSService implements NotificationInterface
{
    private $key = 'send-message:';

    private $limit = 1;

    private $decay = 86400;  // 1 month

    public function send($identifier, OtpPurpose $purpose, $otp = null, $data = [])
    {
        $this->key .= $identifier;
        $this->canSend();

        $countryCode = $this->getCountry($identifier);
        $provider = SMSFactory::make($countryCode);
        $body = $this->getBody($purpose, $otp);
        $response = $provider->send($identifier, $body);
        RateLimiter::hit($this->key, $this->decay);

        return $response;
    }

    public function canSend()
    {
        if (RateLimiter::tooManyAttempts($this->key, $this->limit)) {
            $seconds = RateLimiter::availableIn($this->key);
            $start = now();
            $wait = now()->addSeconds($seconds)->diffForHumans($start, true);

            throw new ClientException("You've reached the SMS limit. Please try again in {$wait}.", 429);
        }
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getBody($purpose, $otp)
    {
        return match ($purpose) {
            OtpPurpose::SIGNUP => "Your Soluta signup verification code is: {$otp}. Valid for 10 minutes.",
            OtpPurpose::FORGOT_PASSWORD => "Your Soluta password reset code is: {$otp}. Valid for 10 minutes.",
            OtpPurpose::EMAIL_UPDATE => "Your Soluta email update verification code is: {$otp}. Valid for 10 minutes.",

            default => throw new ClientException('This notification type is not supported.'),
        };
    }

    public function getCountry($identifier)
    {
        $phone = new PhoneNumber($identifier);

        return $phone->getCountry();
    }
}
