<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * File: app/Http/Controllers/Auth/AuthController.php
 *
 * Purpose:
 *   The four authentication endpoints of the API:
 *     POST /api/v1/auth/register  — create an account, get a token
 *     POST /api/v1/auth/login     — exchange credentials for a token
 *     POST /api/v1/auth/logout    — revoke the current token
 *     GET  /api/v1/auth/me        — who am I?
 *
 * How it fits into the project:
 *   Routes are wired in routes/api.php. Input rules live in
 *   RegisterRequest / LoginRequest (this class never hand-rolls validation).
 *   The UserResource controls exactly which user fields are exposed —
 *   password hashes can never appear in a response. Tokens are plain Sanctum
 *   bearer tokens; the mobile app stores one per device and sends it as an
 *   Authorization header.
 */
class AuthController extends Controller
{
    /**
     * Create a new owner account and immediately log the caller in.
     *
     * Why role is hardcoded: registration is public, so trusting a `role`
     * value from the payload would let anyone self-promote to admin.
     * RegisterRequest only validates name/email/password — anything else
     * sent by the client is ignored (covered by tests).
     *
     * Returns 201 with { user, token }. The password is hashed automatically
     * by the 'hashed' cast on the User model.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            // Server-side assignment only — never from the payload.
            'role' => 'owner',
        ]);

        // Issues a Sanctum personal access token; plainTextToken is the only
        // moment the full secret is ever visible (it's stored hashed).
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Exchange email + password for a fresh Sanctum token.
     *
     * Security details:
     * - Unknown email and wrong password produce the IDENTICAL error so an
     *   attacker cannot discover which emails exist (no account enumeration).
     * - The route itself is wrapped in throttle:6,1 (6 attempts per minute)
     *   to blunt brute-force guessing.
     * - Hash::check compares against the stored hash; no plaintext is ever
     *   stored or logged.
     *
     * Returns 200 with { user, token } on success; 422 with an errors.email
     * envelope on failure.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Look the account up first, then verify the password separately.
        // Doing both steps with the same failure message keeps responses
        // indistinguishable between "no such user" and "wrong password".
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Log out by revoking ONLY the token used for this request.
     *
     * Why not all tokens: the mobile app may be signed in on several
     * devices; revoking every token would log them all out. Revoking just
     * currentAccessToken() matches what users expect "logout" to do.
     *
     * Requires auth:sanctum — the middleware has already resolved the user
     * before this method runs.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user, shaped by UserResource.
     *
     * Used by the mobile app at startup to confirm a stored token is still
     * valid and to hydrate the profile screen. Returns 401 through our
     * error renderer when the token is missing/expired.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user.',
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }
}
