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

/**
 * Public landing page — project overview, feature list, app changelog, and
 * APK download CTA.
 *
 * The changelog is data, not markup: entries live in config/changelog.php
 * (newest first) and the view loops over them, so publishing a release means
 * adding one array block there and nothing else. It tracks public Android
 * app releases only — not backend milestones. Empty until the first APK
 * ships, at which point the view swaps its empty state for the timeline.
 */
Route::get('/', function () {
    return view('welcome', [
        'releases' => config('changelog.releases', []),
        'upcomingVersion' => config('changelog.upcoming_version', 'v1.0.0'),
    ]);
})->name('home');

/**
 * Mobile app download.
 *
 * No Android build exists yet, so this serves a styled "Coming Soon" page
 * instead of a dead link or a 404. The landing page's download button points
 * here, so the URL stays stable once a real build lands.
 */
Route::get('/download/apk', function () {
    // TODO: once the first APK build exists, serve the file from here instead
    // of the placeholder view, e.g.:
    //
    //   $path = storage_path('app/public/releases/gamefowl-latest.apk');
    //
    //   if (! file_exists($path)) {
    //       return view('download-apk');   // fall back to Coming Soon
    //   }
    //
    //   return response()->download($path, 'gamefowl.apk', [
    //       'Content-Type' => 'application/vnd.android.package-archive',
    //   ]);
    return view('download-apk');
})->name('apk.download');
