<?php

/*
|--------------------------------------------------------------------------
| Trusted Proxies
|--------------------------------------------------------------------------
|
| Laravel's built-in TrustProxies middleware (already in the global stack)
| falls back to this value when bootstrap/app.php doesn't set proxies.
|
| Only needed when ANOTHER proxy sits in front of the web server (a load
| balancer, Cloudflare, or nginx proxy_pass-ing to a second server).
| Trusting it makes Laravel honour X-Forwarded-Proto/-For, so url() and
| route() produce https:// links and request()->ip() is the real client.
|
| NOT needed when nginx terminates TLS itself and talks to PHP-FPM via
| fastcgi_pass: nginx's stock fastcgi_params already passes HTTPS=on, so
| Laravel sees https directly. In that setup leave this UNSET — '*' would
| then trust headers sent by the client itself, letting anyone spoof
| X-Forwarded-For and dodge IP-based rate limits.
|
| TRUSTED_PROXIES accepts a comma-separated list of IPs/CIDRs (e.g. the
| load balancer's address). Leave it unset locally.
|
*/

return [

    'proxies' => env('TRUSTED_PROXIES'),

];
