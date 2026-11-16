<?php

/*
|--------------------------------------------------------------------------
| Android APK Download
|--------------------------------------------------------------------------
|
| Which build /download/apk serves, and the metadata shown next to it on
| the landing page and the download page.
|
| The file itself lives in storage/app/public/releases/ (gitignored — APKs
| are uploaded to the server by hand, never committed). Older builds stay
| in that folder as an archive; only the one named here is served.
|
| To ship a new build:
|   1. Upload it to storage/app/public/releases/ on the server.
|   2. Set APK_FILE, APK_VERSION, APK_RELEASED_AT and APK_SHA256 in .env
|      (or bump the defaults below), then `php artisan config:cache`.
|   3. Add a matching entry to config/changelog.php.
|
| The file size is NOT configured: it is read from the file on disk, so it
| can't drift. The SHA-256 is configured rather than computed per request
| because hashing a ~100 MB file on every page view is too expensive —
| compute it with `sha256sum` when uploading and paste it here.
|
*/

return [

    // File name inside storage/app/public/releases/.
    'file' => env('APK_FILE', 'gamefowl-v1.0.2.apk'),

    // Shown to users, 'v' prefixed — keep in step with config/changelog.php.
    'version' => env('APK_VERSION', 'v1.0.2'),

    // ISO-8601 (Y-m-d); formatted by the views.
    'released_at' => env('APK_RELEASED_AT', '2026-11-16'),

    // Lowercase hex SHA-256 of the file, so users can verify the download.
    'sha256' => env('APK_SHA256', 'ae89a2a0edfc741cacc7b2f57a23b19ea132994926768fb15c6366d622ff22ba'),

];
