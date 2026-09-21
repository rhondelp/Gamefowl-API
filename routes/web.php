<?php

use App\Http\Middleware\EnsureDocsUnlocked;
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

/**
 * Expert-system documentation — a long-form, plain-language explainer of the
 * knowledge base, the scoring formula, and the assessment flow, written for
 * the capstone adviser and panel.
 *
 * INTENTIONALLY LIGHTWEIGHT GATE: one static password shared by everyone
 * (DOCS_PASSWORD in .env, read through config/documentation.php), with no
 * per-user accounts and no hashing. Its only job is to keep the page off
 * casual public view while staying effortless to share with an adviser or
 * panel member. It is NOT meant to protect sensitive data, and the page holds
 * none: it explains the seeded knowledge base and the scoring formula.
 *
 *   GET  /documentation       -> password form (skipped once unlocked)
 *   POST /documentation       -> checks the password, flags the session
 *   GET  /documentation/view  -> the documentation itself (EnsureDocsUnlocked)
 *
 * The unlock flag lives in the server-side session (the browser only holds the
 * encrypted session cookie), so it can't be forged client-side, and it lasts
 * until the session expires (SESSION_LIFETIME) instead of asking again on
 * every visit.
 */
Route::get('/documentation', function () {
    if (session('docs_unlocked') === true) {
        return redirect()->route('docs.show');
    }

    return view('documentation.gate', [
        'isConfigured' => filled(config('documentation.password')),
    ]);
})->name('docs.gate');

Route::post('/documentation', function () {
    $attributes = request()->validate([
        'password' => 'required|string|max:255',
    ]);

    $expected = (string) config('documentation.password');

    // Fails closed: while DOCS_PASSWORD is unset, nothing unlocks the page.
    // hash_equals() is a constant-time comparison; there is no real threat
    // model here, it simply costs nothing.
    if ($expected === '' || ! hash_equals($expected, $attributes['password'])) {
        return redirect()->route('docs.gate')
            ->withErrors(['password' => 'That password is incorrect. Please try again.']);
    }

    // Fresh session ID whenever a session gains access (standard session-
    // fixation hygiene), then remember the unlock for the rest of it.
    request()->session()->regenerate();
    session(['docs_unlocked' => true]);

    return redirect()->route('docs.show');
})->middleware('throttle:10,1')->name('docs.unlock');

Route::get('/documentation/view', function () {
    return view('documentation.show', [
        'knowledgeBase' => config('documentation.knowledge_base'),
    ]);
})->middleware(EnsureDocsUnlocked::class)->name('docs.show');
