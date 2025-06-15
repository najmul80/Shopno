<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Notifications\SendOtpNotification;

class PasswordResetController extends Controller
{
     public function sendOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $otp = rand(100000, 999999);
        Cache::put($request->email, $otp, now()->addMinutes(10));

        $user->notify(new SendOtpNotification($otp));

        return response()->json(['message' => 'OTP sent to email!']);
    }

    public function resetPassword(Request $request)
    {
        $storedOtp = Cache::get($request->email);

        if (!$storedOtp || $storedOtp != $request->otp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget($request->email);

        return response()->json(['message' => 'Password reset successful!']);
    }
}
