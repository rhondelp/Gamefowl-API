<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | GAMEFOWL API</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 420px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 4px;
        }

        h2 {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }

        .error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
        }

        button[type="submit"]:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #95a5a6;
        }

        .footer a {
            color: #3498db;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>GAMEFOWL API</h1>
            <p>Expert System for Gamefowl Health</p>
        </div>

        <h2>Reset Your Password</h2>
        <p class="subtitle">Enter your new password below.</p>

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="error">{{ $error }}</div>
            @endforeach
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Enter new password (min. 8 characters)">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm new password">
            </div>

            <button type="submit">Reset Password</button>
        </form>

        <div class="footer">
            <p>Remember your password? <a href="/login">Back to login</a></p>
        </div>
    </div>
</body>
</html>