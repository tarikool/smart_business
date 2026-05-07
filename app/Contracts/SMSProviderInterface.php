<?php

namespace App\Contracts;

interface SMSProviderInterface
{
    public function send(string $to, string $body);
}
