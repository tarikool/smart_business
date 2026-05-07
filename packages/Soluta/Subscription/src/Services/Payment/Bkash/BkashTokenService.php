<?php

namespace Soluta\Subscription\Services\Payment\Bkash;

use Illuminate\Support\Facades\Http;

class BkashTokenService
{
    public $baseUrl;

    public $headers = [];

    public $body = [];

    public $idToken;

    public $refreshToken;

    public function __construct(public $config)
    {
        $this->setBaseUrl();
        $this->setHeaders();
        $this->setBody();

        $this->idToken = cache('bkash.idToken');
        $this->refreshToken = cache('bkash.refreshToken');
    }

    /**
     * @throws \Exception
     */
    public function getToken(): string
    {
        if (! $this->idToken) {
            $response = $this->refreshToken ? $this->refreshToken() : $this->grantToken();
            $this->setIdToken($response->json('id_token'))
                ->setRefreshToken($response->json('refresh_token'), now()->addDays(28));
            $isSuccess = $response->json('statusCode') === '0000' and $response->json('id_token');
            if (! $isSuccess) {
                $errorMsg = $response['statusMessage'] ?? $response['message'] ?? 'bKash Grant Token Failed';
                throw new \Exception($errorMsg, 400);
            }

            return $this->idToken;
        }

        return $this->idToken;
    }

    public function grantToken()
    {
        return Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->post('/tokenized/checkout/token/grant', $this->body);
    }

    public function refreshToken()
    {
        $body = [...$this->body, 'refresh_token' => $this->refreshToken];

        return Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->post('/tokenized/checkout/token/refresh', $body);
    }

    public function setIdToken($value, $expiration = 3500)
    {
        cache()->put('bkash.idToken', $value, $expiration);
        $this->idToken = $value;

        return $this;
    }

    public function setRefreshToken($value, $expiration = 3600)
    {
        cache()->put('bkash.refreshToken', $value, $expiration);
        $this->refreshToken = $value;

        return $this;
    }

    public function setHeaders()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ];
    }

    public function setBody()
    {
        $this->body = [
            'app_key' => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
        ];
    }

    public function setBaseUrl()
    {
        $this->baseUrl = $this->config['base_url'];
    }
}
