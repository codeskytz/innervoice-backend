<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_verified' => false,
        ]);

        $this->generateAndSendOtp($user, 'InnerVoice OTP Verification');

        return response()->json(['message' => 'Registered successfully. Please verify the OTP sent to your email.'], 201);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->is_verified) {
            return response()->json(['message' => 'User already verified.'], 200);
        }

        $otpValidation = $this->validateOtp($user, $data['otp']);
        if ($otpValidation !== null) {
            return $otpValidation;
        }

        $user->is_verified = true;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $token = Str::random(60);
        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json(['message' => 'Verified', 'token' => $token, 'user' => $user], 200);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->is_verified) {
            return response()->json(['message' => 'User already verified.'], 200);
        }

        $this->generateAndSendOtp($user, 'InnerVoice OTP Verification');

        return response()->json(['message' => 'OTP resent successfully.'], 200);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->is_verified) {
            return response()->json(['message' => 'Please verify your account via OTP before logging in.'], 403);
        }

        $token = Str::random(60);
        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json(['message' => 'If that email exists, a reset code has been sent.'], 200);
        }

        $this->generateAndSendOtp($user, 'InnerVoice Password Reset Code');

        return response()->json(['message' => 'If that email exists, a reset code has been sent.'], 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otpValidation = $this->validateOtp($user, $data['otp']);
        if ($otpValidation !== null) {
            return $otpValidation;
        }

        $user->password = $data['password'];
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->api_token = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Generate a 6-digit OTP, save it on the user, and send it via email.
     */
    private function generateAndSendOtp(User $user, string $subject): void
    {
        $otp = (string) random_int(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::raw("Your InnerVoice code is: {$otp}", function ($message) use ($user, $subject) {
            $message->to($user->email)->subject($subject);
        });
    }

    /**
     * Validate the OTP code against the user's stored OTP.
     *
     * Returns a JsonResponse on failure, or null if the OTP is valid.
     */
    private function validateOtp(User $user, string $otp): ?JsonResponse
    {
        if (! $user->otp_code || $user->otp_code !== $otp) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        if ($user->otp_expires_at && Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP expired.'], 422);
        }

        return null;
    }
}
