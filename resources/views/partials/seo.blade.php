{{--
    Shared <head> SEO tags for the public web pages.

    @include('partials.seo', [
        'title' => '…',            // required, unique per page, ≤ 60 chars
        'description' => '…',      // required, unique per page, ≤ 155 chars
        'canonical' => null,       // optional; defaults to APP_URL + current path (no query string)
        'noindex' => false,        // true → <meta name="robots" content="noindex, nofollow">
        'image' => null,           // optional absolute URL; defaults to the 1200×630 OG card
    ])

    Pages keep their own charset/viewport tags and <html lang="en">.
    URLs come from APP_URL, never a hardcoded host.
--}}
@php
    $seoPath = request()->path();
    $seoCanonical = $canonical ?? rtrim((string) config('app.url'), '/').($seoPath === '/' ? '/' : '/'.$seoPath);
    $seoImage = $image ?? asset('images/og-image.png');
    $seoUsesDefaultImage = ! isset($image);
@endphp
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
@if ($noindex ?? false)
    <meta name="robots" content="noindex, nofollow">
@endif
    <meta name="theme-color" content="#16a34a">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="GAMEFOWL">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
@if ($seoUsesDefaultImage)
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="GAMEFOWL: Early Bird Disease Monitoring">
@endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
