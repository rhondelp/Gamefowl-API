<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAMEFOWL | Expert System for Early Bird Disease Monitoring</title>
    <meta name="description"
        content="GAMEFOWL is a rule-based expert system that helps gamefowl owners screen their birds for early signs of disease through weighted symptom analysis.">

    <!-- Poppins Font (matches the password-reset pages) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Brand — health/green primary, shared with the mobile app */
            --primary: #16a34a;
            --primary-dark: #15803d;
            --primary-light: #22c55e;

            /* Semantic status colours (mirror the app's health-status labels) */
            --status-healthy: #16a34a;
            --status-attention: #d97706;
            --status-stale: #64748b;
            --status-critical: #dc2626;

            --text-primary: #17211b;
            --text-secondary: #66736b;
            --text-muted: #8a968f;
            --border: #e2e8e4;
            --background: #f4f8f5;
            --white: #ffffff;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.10), transparent 38%),
                radial-gradient(circle at bottom right, rgba(22, 163, 74, 0.08), transparent 38%),
                var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .wrap {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            padding: 56px 0;
        }

        /* ── Header / Hero ────────────────────────────────────────── */
        .hero {
            text-align: center;
            padding: 64px 0 56px;
        }

        .logo-mark {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
            box-shadow: 0 12px 28px rgba(22, 163, 74, 0.22);
        }

        .eyebrow {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.10);
            border: 1px solid rgba(22, 163, 74, 0.18);
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .brand-name {
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 2px;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .hero h1 {
            font-size: 19px;
            font-weight: 500;
            line-height: 1.5;
            color: var(--text-primary);
            max-width: 680px;
            margin: 0 auto 18px;
            letter-spacing: -0.2px;
        }

        .hero p.lead {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 640px;
            margin: 0 auto 30px;
            line-height: 1.85;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 15px 28px;
            border-radius: 14px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .btn svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.26);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(22, 163, 74, 0.32);
        }

        .btn-primary:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.28);
        }

        .btn-ghost {
            background: var(--white);
            border-color: var(--border);
            color: var(--text-primary);
        }

        .btn-ghost:hover {
            border-color: #cbd5ce;
            transform: translateY(-2px);
        }

        /* ── Section headings ─────────────────────────────────────── */
        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        h2 {
            font-size: 25px;
            font-weight: 600;
            letter-spacing: -0.5px;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .section-intro {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.85;
            max-width: 660px;
            margin-bottom: 28px;
        }

        .section-intro strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* ── Cards ────────────────────────────────────────────────── */
        .card {
            background: var(--white);
            border: 1px solid rgba(226, 232, 228, 0.9);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 4px 14px rgba(23, 33, 27, 0.04);
        }

        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 1fr;
        }

        .card h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 7px;
            letter-spacing: -0.2px;
        }

        .card p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .card-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(22, 163, 74, 0.10);
            color: var(--primary-dark);
            font-size: 19px;
            margin-bottom: 14px;
        }

        /* Callout — the "not ML/CV" clarification */
        .callout {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            background: var(--white);
            border: 1px solid rgba(226, 232, 228, 0.9);
            border-left: 4px solid var(--primary);
            border-radius: 16px;
            padding: 20px 22px;
            margin-top: 20px;
        }

        .callout .mark {
            font-size: 18px;
            line-height: 1.4;
        }

        .callout p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .callout strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* ── Tech stack badges ────────────────────────────────────── */
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--white);
            border: 1px solid var(--border);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
        }

        /* ── Changelog / version history ──────────────────────────── */
        .timeline {
            position: relative;
            margin-top: 26px;
            padding-left: 28px;
        }

        /* The rail. Fades out at the bottom so the oldest entry doesn't
           look like the list was cut off. */
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 14px;
            bottom: 6px;
            width: 2px;
            border-radius: 2px;
            background: linear-gradient(to bottom,
                    var(--primary-light),
                    rgba(22, 163, 74, 0.22) 65%,
                    rgba(22, 163, 74, 0));
        }

        .release {
            position: relative;
            margin-bottom: 14px;
            background: var(--white);
            border: 1px solid rgba(226, 232, 228, 0.9);
            border-radius: 18px;
            box-shadow: 0 4px 14px rgba(23, 33, 27, 0.04);
        }

        .release:last-child {
            margin-bottom: 0;
        }

        /* Node on the rail, aligned to the first line of the header. */
        .release::before {
            content: '';
            position: absolute;
            left: -28px;
            top: 22px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--white);
            border: 3px solid var(--border);
        }

        .release[open]::before,
        .release.is-latest::before {
            border-color: var(--primary);
        }

        .release.is-latest::before {
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.13);
        }

        .release-head {
            display: block;
            padding: 18px 20px;
            cursor: pointer;
            list-style: none;
            border-radius: 18px;
        }

        .release-head::-webkit-details-marker {
            display: none;
        }

        .release-head:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.28);
        }

        .release-top {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 7px;
        }

        .release-label {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        .release-date {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .pill-latest {
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.10);
            border: 1px solid rgba(22, 163, 74, 0.20);
            color: var(--primary-dark);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Chevron pushed to the far right of the header row. */
        .release-chevron {
            margin-left: auto;
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .release[open] .release-chevron {
            transform: rotate(180deg);
        }

        .release-summary {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.75;
        }

        /* Inset by the header's padding so the divider doesn't run the
           full width of the card. */
        .release-body {
            margin: 0 20px;
            padding: 0 0 20px;
            border-top: 1px solid var(--border);
        }

        .release-points {
            list-style: none;
            margin: 16px 0 0;
        }

        .release-points li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 9px;
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.75;
        }

        .release-points li:last-child {
            margin-bottom: 0;
        }

        .release-points li::before {
            content: '';
            position: absolute;
            left: 3px;
            top: 9px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary);
            opacity: 0.5;
        }

        .release-points code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11.5px;
            background: rgba(22, 163, 74, 0.08);
            color: var(--primary-dark);
            padding: 2px 6px;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        /* Empty state — shown until the first APK release lands. Dashed
           border signals "awaiting content" rather than a broken section. */
        .changelog-empty {
            text-align: center;
            background: var(--white);
            border: 1px dashed rgba(22, 163, 74, 0.32);
            border-radius: 20px;
            padding: 40px 24px;
            margin-top: 26px;
        }

        .changelog-empty .card-icon {
            margin: 0 auto 14px;
        }

        .changelog-empty h3 {
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -0.2px;
            margin-bottom: 8px;
        }

        .changelog-empty p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
            max-width: 440px;
            margin: 0 auto;
        }

        .version-tag {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 7px;
            background: rgba(22, 163, 74, 0.10);
            border: 1px solid rgba(22, 163, 74, 0.20);
            color: var(--primary-dark);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── Download section ─────────────────────────────────────── */
        .download {
            text-align: center;
            background: var(--white);
            border: 1px solid rgba(226, 232, 228, 0.9);
            border-radius: 26px;
            padding: 44px 26px;
            box-shadow: 0 20px 50px rgba(23, 33, 27, 0.07);
        }

        .download .card-icon {
            margin: 0 auto 16px;
            width: 54px;
            height: 54px;
            border-radius: 17px;
            font-size: 24px;
        }

        .download p {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 520px;
            margin: 0 auto 24px;
            line-height: 1.85;
        }

        .download .meta {
            margin-top: 16px;
            font-size: 12px;
            color: var(--text-muted);
        }

        /* ── Footer ───────────────────────────────────────────────── */
        footer {
            border-top: 1px solid var(--border);
            margin-top: 24px;
            padding: 38px 0 44px;
            text-align: center;
        }

        footer .foot-brand {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        footer .foot-sub {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .repo-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 20px 0 22px;
        }

        .repo-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: border-color 0.18s ease, transform 0.18s ease;
        }

        .repo-links a:hover {
            border-color: #cbd5ce;
            transform: translateY(-1px);
        }

        .repo-links svg {
            width: 15px;
            height: 15px;
        }

        .disclaimer {
            max-width: 620px;
            margin: 0 auto;
            font-size: 12px;
            line-height: 1.85;
            color: var(--text-secondary);
            background: rgba(217, 119, 6, 0.06);
            border: 1px solid rgba(217, 119, 6, 0.18);
            border-radius: 14px;
            padding: 14px 18px;
        }

        .disclaimer strong {
            color: var(--status-attention);
            font-weight: 600;
        }

        .copyright {
            margin-top: 22px;
            font-size: 11px;
            color: var(--text-muted);
        }

        /* ── Responsive (mobile-first: these are the up-scales) ───── */
        @media (min-width: 640px) {
            .grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .brand-name {
                font-size: 46px;
            }

            .hero h1 {
                font-size: 22px;
            }
        }

        @media (min-width: 900px) {
            section {
                padding: 68px 0;
            }

            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .brand-name {
                font-size: 54px;
            }

            .hero h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- ══ Hero ══════════════════════════════════════════════════ -->
    <header class="hero">
        <div class="wrap">
            <div class="logo-mark">G</div>

            <div class="eyebrow">Capstone Project</div>

            <div class="brand-name">GAMEFOWL</div>

            <h1>Expert System for Early Bird Disease Monitoring and Analysis in Gamefowl</h1>

            <p class="lead">
                A mobile-based expert system that helps gamefowl owners spot health problems early.
                Owners record the symptoms they observe in a bird, and the system scores those symptoms
                against an administrator-maintained knowledge base of poultry diseases — returning ranked
                <em>possible</em> conditions, the reasoning behind each score, and care recommendations,
                all kept in a per-bird health history.
            </p>

            <div class="cta-row">
                <a class="btn btn-primary" href="{{ route('apk.download') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download the App
                </a>

                <a class="btn btn-ghost" href="#about">Learn more</a>
            </div>
        </div>
    </header>

    <!-- ══ About ═════════════════════════════════════════════════ -->
    <section id="about">
        <div class="wrap">
            <div class="section-label">Overview</div>
            <h2>What the system does</h2>
            <p class="section-intro">
                GAMEFOWL is built for <strong>gamefowl owners and breeders</strong> — the people checking on their
                birds daily, often far from the nearest veterinarian. It turns everyday observation into a
                structured screening step: pick the symptoms you can actually see, get back an explainable
                shortlist of conditions worth acting on, and keep a record of every check over time.
            </p>

            <div class="grid grid-3">
                <div class="card">
                    <div class="card-icon">🔎</div>
                    <h3>Symptom-based assessment</h3>
                    <p>
                        Choose observed symptoms from a curated list grouped by category — respiratory,
                        physical, digestive, neurological, and behavioral.
                    </p>
                </div>

                <div class="card">
                    <div class="card-icon">📈</div>
                    <h3>Health history per bird</h3>
                    <p>
                        Every assessment and manual log entry (vet visits, weigh-ins) is kept in a single
                        chronological timeline with a derived current-status summary.
                    </p>
                </div>

                <div class="card">
                    <div class="card-icon">📚</div>
                    <h3>Admin knowledge base</h3>
                    <p>
                        Diseases, symptoms, weighted rules, and care recommendations are authored and
                        maintained by administrators — not hard-coded.
                    </p>
                </div>
            </div>

            <div class="callout">
                <span class="mark">🧠</span>
                <p>
                    <strong>This is a rule-based expert system — not machine learning or computer vision.</strong>
                    There is no trained model and no photo analysis. Each result comes from transparent arithmetic
                    over <em>(disease, symptom, weight)</em> rules written by domain administrators, so every score
                    can be explained after the fact: which symptoms matched, which were missing, and why one
                    condition ranked above another.
                </p>
            </div>
        </div>
    </section>

    <!-- ══ Features ══════════════════════════════════════════════ -->
    <section id="features">
        <div class="wrap">
            <div class="section-label">Capabilities</div>
            <h2>Key features</h2>
            <p class="section-intro">
                Everything below is implemented server-side and consumed by the mobile app as a thin client.
            </p>

            <div class="grid grid-3">
                <div class="card">
                    <div class="card-icon">🩺</div>
                    <h3>Symptom-Based Health Assessment</h3>
                    <p>Submit the symptoms you observe and receive ranked possible conditions in seconds.</p>
                </div>

                <div class="card">
                    <div class="card-icon">⚖️</div>
                    <h3>Weighted Diagnostic Engine</h3>
                    <p>
                        Each rule carries a weight of 1–5. Match scores are computed from those weights, so
                        highly indicative symptoms count for more than incidental ones.
                    </p>
                </div>

                <div class="card">
                    <div class="card-icon">🗓️</div>
                    <h3>Health History Timeline</h3>
                    <p>Assessments and manual records merged newest-first, with a status label for every bird.</p>
                </div>

                <div class="card">
                    <div class="card-icon">🐓</div>
                    <h3>Gamefowl Profile Management</h3>
                    <p>Register each bird with breed, age, sex, weight, and notes — every profile stays private to its owner.</p>
                </div>

                <div class="card">
                    <div class="card-icon">🛠️</div>
                    <h3>Admin Knowledge Base</h3>
                    <p>
                        Full management of diseases, symptoms, rules, and recommendations. Entries are deactivated
                        rather than deleted, so historical records stay intact.
                    </p>
                </div>

                <div class="card">
                    <div class="card-icon">🔐</div>
                    <h3>Secure Authentication</h3>
                    <p>Token-based sign-in with Laravel Sanctum, rate-limited logins, and role-separated access.</p>
                </div>

                <div class="card">
                    <div class="card-icon">✉️</div>
                    <h3>Password Reset via Email</h3>
                    <p>Forgot-password flow with an emailed reset link; resetting signs out every previous session.</p>
                </div>

                <div class="card">
                    <div class="card-icon">🧾</div>
                    <h3>Explainable Results</h3>
                    <p>
                        Every result shows matched symptoms, missing symptoms, severity, and a veterinary
                        warning when the condition ranks severe or critical.
                    </p>
                </div>

                <div class="card">
                    <div class="card-icon">🔒</div>
                    <h3>Immutable Records</h3>
                    <p>
                        Assessments are stored as snapshots — later edits to the knowledge base never rewrite
                        what a past health check said.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══ Tech stack ════════════════════════════════════════════ -->
    <section id="tech">
        <div class="wrap">
            <div class="section-label">Built with</div>
            <h2>Technology</h2>
            <p class="section-intro">
                A Laravel REST API backing a React Native mobile client.
            </p>

            <div class="badges">
                <span class="badge"><span class="dot"></span> Laravel</span>
                <span class="badge"><span class="dot"></span> PostgreSQL</span>
                <span class="badge"><span class="dot"></span> Laravel Sanctum</span>
                <span class="badge"><span class="dot"></span> React Native / Expo</span>
                <span class="badge"><span class="dot"></span> REST API (JSON)</span>
            </div>
        </div>
    </section>

    <!-- ══ Changelog ═════════════════════════════════════════════ -->
    <section id="changelog">
        <div class="wrap">
            <div class="section-label">App Releases</div>
            <h2>Changelog</h2>
            <p class="section-intro">
                Version history for the GAMEFOWL Android app — what changed in each release, newest first.
            </p>

            @if (empty($releases))
                <div class="changelog-empty">
                    <div class="card-icon">🗓️</div>
                    <h3>No releases yet</h3>
                    <p>
                        The first public build, <span class="version-tag">{{ $upcomingVersion }}</span>, is on the
                        way. Once the app ships, every release and what changed in it will be listed here.
                    </p>
                </div>
            @else
                <div class="timeline">
                    @foreach ($releases as $index => $release)
                        <details class="release{{ $index === 0 ? ' is-latest' : '' }}" {{ $index === 0 ? 'open' : '' }}>
                            {{-- <summary> takes phrasing content, so the header uses spans, not div/p. --}}
                            <summary class="release-head">
                                <span class="release-top">
                                    <span class="release-label">{{ $release['version'] }}</span>

                                    @if ($index === 0)
                                        <span class="pill-latest">Latest</span>
                                    @endif

                                    <span class="release-date">
                                        {{ \Illuminate\Support\Carbon::parse($release['date'])->format('M j, Y') }}
                                    </span>

                                    <svg class="release-chevron" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </span>

                                <span class="release-summary">{{ $release['summary'] }}</span>
                            </summary>

                            <div class="release-body">
                                <ul class="release-points">
                                    @foreach ($release['highlights'] as $highlight)
                                        {{-- Highlights are developer-authored constants in config/changelog.php
                                             and intentionally carry inline <code>/<em> markup. --}}
                                        <li>{!! $highlight !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- ══ Download ══════════════════════════════════════════════ -->
    <section id="download">
        <div class="wrap">
            <div class="download">
                <div class="card-icon">📱</div>
                <h2>Download the App</h2>
                <p>
                    GAMEFOWL runs on Android. Install the app, create an owner account, and start tracking
                    your birds' health right away.
                </p>

                <a class="btn btn-primary" href="{{ route('apk.download') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download APK for Android
                </a>

                <p class="meta">Android 7.0+ · Free · Capstone release</p>
            </div>
        </div>
    </section>

    <!-- ══ Footer ════════════════════════════════════════════════ -->
    <footer>
        <div class="wrap">
            <div class="foot-brand">GAMEFOWL</div>
            <div class="foot-sub">
                Expert System for Early Bird Disease Monitoring and Analysis in Gamefowl · Capstone Project
            </div>

            <div class="repo-links">
                <a href="https://github.com/rhondelp/Gamefowl-API" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56v-2c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.56-.29-5.25-1.28-5.25-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.84 1.18 3.1 0 4.43-2.69 5.4-5.26 5.69.41.36.78 1.06.78 2.14v3.17c0 .31.21.68.8.56A11.51 11.51 0 0023.5 12C23.5 5.73 18.27.5 12 .5z" />
                    </svg>
                    Backend Repository
                </a>

                <a href="https://github.com/rhondelp/Gamefowl-MobileApp" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56v-2c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.56-.29-5.25-1.28-5.25-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.84 1.18 3.1 0 4.43-2.69 5.4-5.26 5.69.41.36.78 1.06.78 2.14v3.17c0 .31.21.68.8.56A11.51 11.51 0 0023.5 12C23.5 5.73 18.27.5 12 .5z" />
                    </svg>
                    Mobile App Repository
                </a>
            </div>

            <p class="disclaimer">
                <strong>⚠ Disclaimer:</strong> This system is an educational/support tool and is
                <strong>not a replacement for a licensed veterinarian</strong>. All diagnostic output is presented
                as a <em>possible</em> condition based on submitted symptoms — never as a confirmed diagnosis.
                Always consult a veterinarian for severe or worsening cases.
            </p>

            <p class="copyright">&copy; {{ date('Y') }} GAMEFOWL Capstone Project. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
