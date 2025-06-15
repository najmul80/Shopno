<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Log;

class TestUserController extends Controller
{
    public function show(User $user)
    {
        Log::info('--- TestUserController@show ---');
        Log::info('User from RMB - ID: ' . $user->id . ', Exists: ' . ($user->exists ? 'Yes' : 'No'));

        if (!$user->exists) {
            return response()->json(['error' => 'User not found in TestUserController'], 404);
        }
        // return response()->json(new UserResource($user->loadMissing(['store', 'roles'])));
        return response()->json($user->loadMissing(['store', 'roles'])); // সরাসরি মডেল রিটার্ন করে দেখুন
    }
}
