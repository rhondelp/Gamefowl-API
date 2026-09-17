<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | GAMEFOWL</title>

    <!-- Poppins Font -->
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
            --border: #e2e8e4;
            --background: #f4f8f5;
            --white: #ffffff;
            --danger-bg: #fff5f5;
            --danger-border: #fecaca;
            --danger-text: #dc2626;
        }

        body {
            font-family: 'Poppins', sans-serif;
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
            margin-bottom: 34px;
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
            color: white;
            font-size: 25px;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(22, 163, 74, 0.20);
        }

        .logo h1 {
            color: var(--text-primary);
            font-size: 21px;
            line-height: 1.3;
            font-weight: 700;
            letter-spacing: -0.4px;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 400;
            margin-top: 5px;
        }

        /* Heading */
        h2 {
            color: var(--text-primary);
            font-size: 25px;
            line-height: 1.35;
            font-weight: 600;
            letter-spacing: -0.5px;
            margin-bottom: 7px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* Error */
        .error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .error-icon {
            flex-shrink: 0;
            font-size: 14px;
            margin-top: 1px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8a968f;
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
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 400;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background-color 0.2s ease;
        }

        input[type="password"]::placeholder {
            color: #9aa59e;
        }

        input[type="password"]:hover {
            border-color: #cbd5ce;
        }

        input[type="password"]:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.10);
        }

        /* Button */
        button[type="submit"] {
            width: 100%;
            height: 52px;
            margin-top: 4px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(
                135deg,
                var(--primary),
                var(--primary-light)
            );
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1px;
            cursor: pointer;
            box-shadow: 0 9px 20px rgba(22, 163, 74, 0.18);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        button[type="submit"]:hover {
            background: linear-gradient(
                135deg,
                var(--primary-dark),
                var(--primary)
            );
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.23);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 6px 14px rgba(22, 163, 74, 0.16);
        }

        button[type="submit"]:focus {
            outline: none;
            box-shadow:
                0 0 0 4px rgba(22, 163, 74, 0.14),
                0 9px 20px rgba(22, 163, 74, 0.18);
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 22px;
            border-top: 1px solid #edf1ee;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .footer a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* Security note */
        .security-note {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 18px;
            color: #8a968f;
            font-size: 10px;
            font-weight: 400;
        }

        .security-note span {
            color: var(--primary);
            font-size: 12px;
        }

        /* Mobile */
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .card {
                padding: 30px 22px;
                border-radius: 20px;
            }

            .logo {
                margin-bottom: 28px;
            }

            .logo-mark {
                width: 54px;
                height: 54px;
                border-radius: 16px;
                font-size: 23px;
            }

            .logo h1 {
                font-size: 19px;
            }

            h2 {
                font-size: 22px;
            }

            .subtitle {
                font-size: 12px;
                margin-bottom: 24px;
            }

            input[type="password"],
            button[type="submit"] {
                height: 50px;
            }
        }
    </style>
</head>

<body>

    <div class="card">

        <!-- Brand -->
        <div class="logo">
            <div class="logo-mark">G</div>

            <h1>GAMEFOWL</h1>

            <p>Expert System for Gamefowl Health</p>
        </div>

        <!-- Heading -->
        <h2>Reset Your Password</h2>

        <p class="subtitle">
            Create a new password to securely access your account.
        </p>

        <!-- Session Error -->
        @if (session('error'))
            <div class="error">
                <span class="error-icon">⚠</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Validation Errors -->
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="error">
                    <span class="error-icon">⚠</span>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        @endif

        <!-- Reset Password Form -->
        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- New Password -->
            <div class="form-group">
                <label for="password">New Password</label>

                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Enter new password (min. 8 characters)"
                    >
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>

                <div class="input-wrapper">
                    <span class="input-icon">🔐</span>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                    >
                </div>
            </div>

            <!-- Submit -->
            <button type="submit">
                Reset Password
            </button>
        </form>

        <!-- Footer -->
        {{-- <div class="footer">
            <p>
                Remember your password?
                <a href="/login">Back to login</a>
            </p>
        </div> --}}

        <!-- Security -->
        <div class="security-note">
            <span>●</span>
            Your password is securely protected
        </div>

    </div>

</body>
</html>
