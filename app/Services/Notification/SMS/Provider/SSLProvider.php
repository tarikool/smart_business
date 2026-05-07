<?php

namespace App\Services\Notification\SMS\Provider;

use App\Contracts\SMSProviderInterface;
use App\Exceptions\ClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSLProvider implements SMSProviderInterface
{
    public $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('sms.ssl.base_url');
    }

    public function send(string $to, string $body)
    {
        $response = Http::baseUrl($this->baseUrl)
            ->asJson()
            ->acceptJson()
            ->post('/api/v3/send-sms', [
                'api_token' => config('sms.ssl.api_token'),
                'sid' => config('sms.ssl.sid'),
                'msisdn' => $to,
                'sms' => $body,
                'csms_id' => uniqid(),
            ]);

        $status = $response->json('status');

        if ($status == 'FAILED') {
            Log::error('sms:ssl', $response->json());
            throw new ClientException('Failed to send OTP. Please try again later.', 500);
        }

        return $response->json();
    }
}
