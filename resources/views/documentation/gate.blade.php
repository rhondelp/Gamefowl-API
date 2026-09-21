<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation | GAMEFOWL</title>
    <meta name="robots" content="noindex, nofollow">

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
            --text-primary: #17211b;
            --text-secondary: #66736b;
            --text-muted: #8a968f;
            --border: #e2e8e4;
            --background: #f4f8f5;
            --white: #ffffff;
            --danger-bg: #fff5f5;
            --danger-border: #fecaca;
            --danger-text: #dc2626;
            --notice-bg: rgba(217, 119, 6, 0.07);
            --notice-border: rgba(217, 119, 6, 0.22);
            --notice-text: #b45309;
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
            padding: 42px;
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

        /* Brand */
        .logo {
            text-align: center;
            margin-bottom: 26px;
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

        .logo h1 {
            font-size: 21px;
            line-height: 1.3;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 4px;
        }

        .pill {
            display: flex;
            width: max-content;
            align-items: center;
            gap: 7px;
            margin: 0 auto 16px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.10);
            border: 1px solid rgba(22, 163, 74, 0.18);
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        /* Heading */
        h2 {
            font-size: 24px;
            line-height: 1.35;
            font-weight: 600;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.75;
            margin-bottom: 26px;
        }

        /* Messages */
        .message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .message-icon {
            flex-shrink: 0;
            font-size: 14px;
            margin-top: 1px;
        }

        .message.error {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
        }

        .message.notice {
            background: var(--notice-bg);
            border: 1px solid var(--notice-border);
            color: var(--notice-text);
        }

        /* Form */
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            pointer-events: none;
        }

        input[type="password"] {
            width: 100%;
            height: 52px;
            padding: 0 15px 0 44px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #fbfdfb;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        input[type="password"]::placeholder {
            color: #9aa59e;
        }

        input[type="password"]:hover {
            border-color: #cbd5ce;
        }

        input[type="password"]:focus {
            outline: none;
            background: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.10);
        }

        input[aria-invalid="true"] {
            border-color: var(--danger-border);
        }

        button[type="submit"] {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 9px 20px rgba(22, 163, 74, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        button[type="submit"]:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.23);
        }

        button[type="submit"]:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.24), 0 9px 20px rgba(22, 163, 74, 0.18);
        }

        button[type="submit"]:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #edf1ee;
            font-size: 12px;
            line-height: 1.8;
            color: var(--text-muted);
        }

        .footer a {
            display: inline-block;
            margin-bottom: 6px;
            color: var(--primary-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .card {
                padding: 30px 22px;
                border-radius: 20px;
            }

            h2 {
                font-size: 22px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .card {
                animation: none;
            }
        }
    </style>
</head>
<body>

    <main class="card">

        <div class="logo">
            <div class="logo-mark">G</div>
            <h1>GAMEFOWL</h1>
            <p>Expert System for Gamefowl Health</p>
        </div>

        <div class="pill">📘 System Documentation</div>

        <h2>How the expert system works</h2>

        <p class="subtitle">
            A plain-language guide to the knowledge base, the scoring formula, and the assessment flow.
            Enter the password you were given to continue.
        </p>

        @unless ($isConfigured)
            <div class="message notice" role="status">
                <span class="message-icon">ℹ</span>
                <span>Access to this page hasn't been set up on this server yet. Please contact the project team.</span>
            </div>
        @endunless

        @error('password')
            <div class="message error" id="password-error" role="alert">
                <span class="message-icon">⚠</span>
                <span>{{ $message }}</span>
            </div>
        @enderror

        <form method="POST" action="{{ route('docs.unlock') }}">
            @csrf

            <label for="password">Password</label>

            <div class="input-wrapper">
                <span class="input-icon" aria-hidden="true">🔒</span>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="Enter the documentation password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    @disabled(! $isConfigured)
                >
            </div>

            <button type="submit" @disabled(! $isConfigured)>View Documentation</button>
        </form>

        <div class="footer">
            <a href="{{ route('home') }}">← Back to home</a><br>
            Shared with the project's adviser and panel. Ask the GAMEFOWL team if you need the password.
        </div>

    </main>

</body>
</html>
