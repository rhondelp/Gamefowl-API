<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful | GAMEFOWL API</title>
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
            text-align: center;
        }

        .logo {
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

        .success-icon {
            width: 64px;
            height: 64px;
            background-color: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .success-icon svg {
            width: 32px;
            height: 32px;
            stroke: white;
        }

        h2 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .message {
            color: #7f8c8d;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .message strong {
            color: #2c3e50;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background-color: #3498db;
            color: white;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
        }

        .footer {
            margin-top: 28px;
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

        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>

        <h2>Password Reset Successful</h2>

        <p class="message">
            Your password has been reset. <strong>You can now log in to the GAMEFOWL app</strong> using your new password.
        </p>

        <div class="footer">
            <p>All previous sessions have been logged out for security.</p>
        </div>
    </div>
</body>
</html>