<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string']);

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->approval_status !== 'approved') {
            return response()->json([
                'message' => $user->approval_status === 'pending'
                    ? 'Your registration is waiting for approval.'
                    : 'Your registration was not approved.',
                'approval_status' => $user->approval_status,
            ], 403);
        }

        $token = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'privileges' => $user->privileges(),
                'property_id' => $user->property_id,
                'approval_status' => $user->approval_status,
            ],
        ])->cookie(
            'hotel_task_page_token',
            $token,
            1440,
            '/',
            null,
            (bool) (config('session.secure') ?? $request->isSecure()),
            true,
            false,
            'lax',
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'])
            ->withoutCookie('hotel_task_page_token');
    }
}
