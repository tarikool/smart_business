<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgoPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignupRequest;
use App\Http\Resources\User\AuthResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService) {}

    public function getMe()
    {
        return $this->successResponse(
            new UserResource(auth()->user())
        );
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->getUserByContactNo($request->contact_no);

        if (! Hash::check($request->password, $user->password)) {
            throw new \Exception('Invalid password!', 401);
        }

        $token = $this->authService->createToken($user);

        return $this->successResponse(
            new AuthResource($user->refresh(), $token),
            'Logged in successfully.'
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(msg: 'Logged out.');
    }

    public function signup(SignupRequest $request)
    {
        $signupIdentifier = $request->validated('signup_identifier');

        $otp = $this->authService->generateOtp($signupIdentifier, OtpPurpose::SIGNUP);

        return $this->successResponse($otp, msg: "An otp is sent to your $request->type.");
    }

    public function verifySignup(SignupRequest $request)
    {
        $signupIdentifier = $request->validated('signup_identifier');

        $this->authService->verifyOtp($signupIdentifier, OtpPurpose::SIGNUP, $request->otp);

        $user = User::create($request->validated());
        $token = $this->authService->createToken($user);

        return $this->successResponse(
            new AuthResource($user->refresh(), $token),
            'OTP verified successfully'
        );
    }

    public function socialSignIn(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google',
            'user_token' => 'required|string',
            'iso_code' => 'required|size:2|exists:countries,iso_code',
        ]);

        $user = $this->authService->socialSignIn($request);
        $token = $this->authService->createToken($user);

        return $this->successResponse(
            new AuthResource($user->refresh(), $token),
            'Sign in successfully.'
        );

    }

    public function forgotPassword(ForgoPasswordRequest $request)
    {
        $user = $this->authService->getUserByContactNo($request->contact_no);

        $otp = $this->authService->generateOtp($request->contact_no, OtpPurpose::FORGOT_PASSWORD);

        return $this->successResponse($otp, msg: "An otp is sent to your $request->contact_no.");
    }

    public function verifyForgotPassword(ForgoPasswordRequest $request)
    {
        $this->authService->verifyOtp($request->contact_no, OtpPurpose::FORGOT_PASSWORD, $request->otp);

        $user = User::whereAny(['email', 'phone_number'], $request->contact_no)->first();
        $user->password = $request->password;
        $user->save();

        return $this->successResponse(msg: 'Password reset successfully.');
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
            'identifier' => 'required|string|max:50',
        ]);

        $otp = $this->authService->generateOtp($request->identifier, OtpPurpose::from($request->purpose));

        return $this->successResponse($otp, msg: "An otp is sent to your $request->identifier.");
    }

    public function otpPurposes()
    {
        return $this->successResponse(OtpPurpose::values());
    }
}
