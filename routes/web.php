<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Password reset web routes (Backend Milestone 10).
 *
 * These are NOT part of the /api/v1 JSON API — they serve a plain Blade
 * form that the emailed reset link points to. The flow:
 *   GET  /reset-password?token=...&email=...  -> shows form with hidden token/email
 *   POST /reset-password                      -> processes form, calls Password::reset()
 *
 * CSRF protection is enabled via the default 'web' middleware group.
 */

Route::get('/reset-password', function () {
    return view('auth.reset-password', [
        'token' => request()->query('token', ''),
        'email' => request()->query('email', ''),
    ]);
})->name('password.reset');

Route::post('/reset-password', function () {
    $attributes = request()->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $status = Password::reset(
        $attributes,
        function ($user, $password) {
            $user->forceFill([
                'password' => $password,
            ])->save();

            // Revoke ALL Sanctum tokens for this user — a password reset
            // should invalidate every prior session (matches M9's security
            // posture for the authenticated change-password flow).
            $user->tokens()->delete();
        }
    );

    if ($status !== Password::PASSWORD_RESET) {
        // Return the form view directly with the error, preserving token/email
        return back()->withInput(request()->only('token', 'email'))
            ->withErrors(['email' => __($status)]);
    }

    return view('auth.reset-password-success');
})->name('password.update');

Route::get('/', function () {
    return view('welcome');
});
