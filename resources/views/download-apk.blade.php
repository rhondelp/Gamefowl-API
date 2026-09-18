<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Download Coming Soon | GAMEFOWL</title>
    <meta name="robots" content="noindex">

    <!-- Poppins Font (matches the landing and password-reset pages) -->
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
            --primary: #16a34a;
            --primary-dark: #15803d;
            --primary-light: #22c55e;
            --accent: #d97706;
            --text-primary: #17211b;
            --text-secondary: #66736b;
            --text-muted: #8a968f;
            --border: #e2e8e4;
            --background: #f4f8f5;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.10), transparent 35%),
                radial-gradient(circle at bottom right, rgba(22, 163, 74, 0.08), transparent 35%),
                var(--background);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-primary);
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: var(--white);
            border: 1px solid rgba(226, 232, 228, 0.9);
            border-radius: 24px;
            padding: 42px 34px;
            text-align: center;
            box-shadow:
                0 20px 50px rgba(23, 33, 27, 0.08),
                0 4px 14px rgba(23, 33, 27, 0.04);
            animation: cardEnter 0.45s ease-out;
        }

        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-mark {
            width: 58px;
            height: 58px;
            margin: 0 auto 16px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            font-size: 25px;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(22, 163, 74, 0.20);
        }

        .brand h1 {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .brand p {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 4px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 28px 0 18px;
            padding: 7px 15px;
            border-radius: 999px;
            background: rgba(217, 119, 6, 0.08);
            border: 1px solid rgba(217, 119, 6, 0.20);
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
            line-height: 1.35;
            margin-bottom: 10px;
        }

        .message {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.85;
            margin-bottom: 26px;
        }

        .message strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            padding: 15px 24px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.26);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(22, 163, 74, 0.32);
        }

        .btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.28);
        }

        .btn svg {
            width: 17px;
            height: 17px;
        }

        .secondary-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 12px;
            font-weight: 500;
            color: var(--primary-dark);
            text-decoration: none;
        }

        .secondary-link:hover {
            text-decoration: underline;
        }

        .footer-note {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            line-height: 1.8;
            color: var(--text-muted);
        }

        @media (max-width: 480px) {
            .card {
                padding: 30px 22px;
            }
        }
    </style>
</head>
<body>

    <div class="card">

        <div class="brand">
            <div class="logo-mark">G</div>
            <h1>GAMEFOWL</h1>
            <p>Expert System for Gamefowl Health</p>
        </div>

        <div class="status-pill"><span class="dot"></span> Coming Soon</div>

        <h2>The Android app isn't ready to download yet</h2>

        <p class="message">
            The GAMEFOWL mobile app is still in development. <strong>The download will go live here</strong>
            as soon as the first Android build is released — this same link will serve the APK, so feel free
            to bookmark it.
        </p>

        <a class="btn" href="{{ url('/') }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Home
        </a>

        {{-- <a class="secondary-link" href="https://github.com/rhondelp/Gamefowl-MobileApp" target="_blank"
            rel="noopener noreferrer">
            Follow development on GitHub →
        </a> --}}

        <p class="footer-note">
            This system is an educational/support tool and is not a replacement for a licensed veterinarian.
        </p>
    </div>

</body>
</html>
