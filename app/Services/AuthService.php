<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Country;
use App\Models\User;
use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    public function createToken($user)
    {
        return $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addWeek()
        )->plainTextToken;
    }

    /**
     * @param  OtpPurpose  $purpose
     * @return true
     *
     * @throws \Exception
     */
    public function verifyOtp($identifier, $purpose, $otp)
    {
        $cacheKey = "otp:{$purpose->value}:{$identifier}";
        $cachedOtp = Cache::get($cacheKey);

        if (! $cachedOtp) {
            throw new \Exception('Otp Expired!');
        }

        if ($cachedOtp != $otp) {
            throw new \Exception('Invalid OTP!');
        }

        Cache::forget($cacheKey);

        return true;
    }

    /**
     * @param  OtpPurpose  $purpose
     * @return int
     */
    public function generateOtp($identifier, $purpose)
    {
        $key = "otp:{$purpose->value}:{$identifier}";
        $otp = random_int(100000, 999999);
        Cache::put($key, $otp, now()->addMinutes(10));

        return $otp;
    }

    /**
     * @return User
     *
     * @throws \Exception
     */
    public function getUserByContactNo($contactNo)
    {
        $user = User::whereAny(['email', 'phone_number'], $contactNo)->sole();

        return $user;
    }

    public function socialSignIn($request)
    {
        $payload = $this->verifySocialSignIn($request);

        return $this->extractUserFromSocialSignIn($request, $payload);
    }

    public function extractUserFromSocialSignIn($request, $payload)
    {
        DB::beginTransaction();
        try {
            $user = User::firstOrNew([
                'email' => $payload['email'],
            ], [
                'name' => $payload['name'] ?? 'Not found',
                'country_id' => Country::whereIsoCode($request->iso_code)->value('id'),
                'user_type' => UserType::MERCHANT,
                'status' => UserStatus::ACTIVE,
            ]);

            if (! $user->password) {
                $user->password = Str::password(8);
            }

            $user->save();

            if ($photo = $payload['picture'] ?? null) {
                $user->userProfile()->updateOrCreate([], ['user_photo' => $photo]);
            }

            $user->socialAccounts()->updateOrCreate(['provider' => $request->provider], [
                'social_id' => $payload['sub'],
            ]);

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function verifySocialSignIn($request)
    {
        $client = new Client(['client_id' => config('social.google.client_id')]);
        $payload = $client->verifyIdToken($request->user_token);

        if (! $payload) {
            throw new \Exception('Invalid user!', 404);
        }

        return $payload;
    }
}
