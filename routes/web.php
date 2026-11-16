<?php

use App\Http\Middleware\EnsureDocsUnlocked;
use App\Http\Middleware\SetNoIndexHeader;
use Illuminate\Support\Carbon;
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
})->middleware(SetNoIndexHeader::class)->name('password.reset');

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
})->middleware(SetNoIndexHeader::class)->name('password.update');

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
    $apkPath = storage_path('app/public/releases/'.basename(config('apk.file')));

    return view('welcome', [
        'releases' => config('changelog.releases', []),
        'upcomingVersion' => config('changelog.upcoming_version', 'v1.0.0'),
        'apk' => config('apk'),
        'apkSizeMb' => file_exists($apkPath) ? round(filesize($apkPath) / 1048576, 1) : null,
    ]);
})->name('home');

/**
 * Mobile app download.
 *
 * /download/apk is the page the landing page links to: it shows a download
 * button when the APK exists and falls back to "Coming Soon" when it doesn't.
 * /download/apk/file streams the actual file. Which build is served (and its
 * version, date and SHA-256) comes from config/apk.php / APK_* in .env — see
 * that file for the release steps. Paths are resolved inside the closures,
 * not at file load, so a cached route table never pins an old build.
 * basename() keeps an env-supplied name from escaping the releases folder.
 */
Route::get('/download/apk', function () {
    $apkPath = storage_path('app/public/releases/'.basename(config('apk.file')));
    $available = file_exists($apkPath);

    return view('download-apk', [
        'available' => $available,
        'sizeMb' => $available ? round(filesize($apkPath) / 1048576, 1) : null,
        'apk' => config('apk'),
    ]);
})->name('apk.download');

Route::get('/download/apk/file', function () {
    $apkFile = basename(config('apk.file'));
    $apkPath = storage_path('app/public/releases/'.$apkFile);
    abort_unless(file_exists($apkPath), 404);

    return response()->download($apkPath, $apkFile, [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->middleware(SetNoIndexHeader::class)->name('apk.file');

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
 *   GET  /documentation            -> password form (skipped once unlocked)
 *   POST /documentation            -> checks the password, flags the session
 *   GET  /documentation/view       -> the plain-language overview (EnsureDocsUnlocked)
 *   GET  /documentation/technical  -> the code-level walkthrough (EnsureDocsUnlocked)
 *
 * The unlock flag lives in the server-side session (the browser only holds the
 * encrypted session cookie), so it can't be forged client-side, and it lasts
 * until the session expires (SESSION_LIFETIME) instead of asking again on
 * every visit. Both pages share the one flag: unlocking either unlocks both.
 */
Route::get('/documentation', function () {
    if (session('docs_unlocked') === true) {
        return redirect()->route('docs.show');
    }

    return view('documentation.gate', [
        'isConfigured' => filled(config('documentation.password')),
    ]);
})->middleware(SetNoIndexHeader::class)->name('docs.gate');

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

    // Back to the page that sent the visitor here (EnsureDocsUnlocked stores
    // it), so a shared link to the technical page lands there after the
    // password; the overview otherwise.
    return redirect()->intended(route('docs.show'));
})->middleware([SetNoIndexHeader::class, 'throttle:10,1'])->name('docs.unlock');

Route::get('/documentation/view', function () {
    return view('documentation.show', [
        'knowledgeBase' => config('documentation.knowledge_base'),
    ]);
})->middleware([SetNoIndexHeader::class, EnsureDocsUnlocked::class])->name('docs.show');

Route::get('/documentation/technical', function () {
    return view('documentation.technical');
})->middleware([SetNoIndexHeader::class, EnsureDocsUnlocked::class])->name('docs.technical');

/**
 * SEO: robots.txt and sitemap.xml, generated so every URL comes from APP_URL.
 *
 * robots.txt keeps crawlers off the API, the token-based reset page and the
 * APK binary. The download PAGE (/download/apk) stays crawlable, so only
 * /download/apk/file is disallowed. /documentation is intentionally NOT listed
 * (listing it would advertise it); SetNoIndexHeader + the noindex meta tag
 * keep it out of search results instead.
 *
 * The sitemap lists public, indexable pages only. When you add a public page,
 * add it to $pages below and pass a unique title/description to
 * partials/seo in its view.
 */
Route::get('/robots.txt', function () {
    $appUrl = rtrim((string) config('app.url'), '/');

    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /api/',
        'Disallow: /reset-password',
        'Disallow: /download/apk/file',
        '',
        'Sitemap: '.$appUrl.'/sitemap.xml',
    ];

    return response(implode("\n", $lines)."\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('robots');

Route::get('/sitemap.xml', function () {
    $appUrl = rtrim((string) config('app.url'), '/');

    // The landing page changes when its view or the changelog it renders does.
    $homeModified = max(
        filemtime(resource_path('views/welcome.blade.php')),
        filemtime(config_path('changelog.php')),
    );

    $pages = [
        [
            'loc' => $appUrl.route('home', [], false),
            'lastmod' => date('Y-m-d', $homeModified),
        ],
        [
            'loc' => $appUrl.route('apk.download', [], false),
            'lastmod' => Carbon::parse(config('apk.released_at'))->toDateString(),
        ],
    ];

    return response()->view('seo.sitemap', ['pages' => $pages], 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('sitemap');
