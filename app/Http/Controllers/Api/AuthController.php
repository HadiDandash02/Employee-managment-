<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API login with Laravel Sanctum: the app sends the username/email and password once and gets a token,
 * then sends "Authorization: Bearer <token>" with every request. There is no session or cookie.
 */
class AuthController extends Controller
{
    /**
     * POST /api/login → {"token": "1|abc…", "user": {...}}
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        // The same field accepts a username or an email address, like the web login.
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'The username/email or password is incorrect.']);
        }

        if (! $user->is_active) {
            abort(403, 'Your account is suspended. Please contact HR.');
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'username', 'role']),
        ]);
    }

    /**
     * POST /api/logout: deletes only the token used for this request (other devices stay logged in).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
