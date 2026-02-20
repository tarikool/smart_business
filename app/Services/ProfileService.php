<?php

namespace App\Services;

use App\Enums\OtpPurpose;

class ProfileService
{
    public function sendOtpToUpdateEmail($request, $user)
    {
        if ($user->email == $request->email) {
            throw new \Exception('Enter a new email address.', 422);
        }

        return (new AuthService)->generateOtp($request->email, OtpPurpose::EMAIL_UPDATE);
    }
}
