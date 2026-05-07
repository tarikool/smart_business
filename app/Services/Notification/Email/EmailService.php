<?php

namespace App\Services\Notification\Email;

use App\Contracts\NotificationInterface;
use App\Enums\OtpPurpose;
use App\Exceptions\ClientException;
use App\Mail\EmailUpdateVerification;
use App\Mail\ForgotPassword;
use App\Mail\SignUpVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class EmailService implements NotificationInterface
{
    private $key = 'send-email:';

    private $limit = 50;

    private $decay = 86400; // 30 days

    public function send($identifier, OtpPurpose $purpose, $otp = null, $data = [])
    {
        $this->key .= $identifier;
        $this->canSend();

        $mailer = $this->getMailer($purpose, $otp, $data);
        $response = Mail::to($identifier)->queue($mailer);
        RateLimiter::hit($this->key, $this->decay);

        return $response;
    }

    public function canSend()
    {
        if (RateLimiter::tooManyAttempts($this->key, $this->limit)) {
            $seconds = RateLimiter::availableIn($this->key);
            $start = now();
            $wait = now()->addSeconds($seconds)->diffForHumans($start, true);

            throw new ClientException("You've reached the email limit. Please try again in {$wait}.", 429);
        }
    }

    public function getMailer($purpose, $otp = null, $data = [])
    {
        return match ($purpose) {
            OtpPurpose::SIGNUP => new SignUpVerification($otp, $data),
            OtpPurpose::FORGOT_PASSWORD => new ForgotPassword($otp, $data),
            OtpPurpose::EMAIL_UPDATE => new EmailUpdateVerification($otp, $data),

            default => throw new ClientException('This notification type is not supported.')
        };
    }
}
