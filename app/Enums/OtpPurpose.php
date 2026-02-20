<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum OtpPurpose: string
{
    use EnumTrait;

    case SIGNUP = 'signup';
    case FORGOT_PASSWORD = 'forgot_password';
    case EMAIL_UPDATE = 'email_update';
}
