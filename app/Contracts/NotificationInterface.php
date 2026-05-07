<?php

namespace App\Contracts;

use App\Enums\OtpPurpose;

interface NotificationInterface
{
    public function send($identifier, OtpPurpose $purpose, $otp = null, $data = []);

    public function canSend();
}
