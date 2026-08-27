<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Daily Cuts &mdash; Coming Soon</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #faf7f2;
            color: #3d2c2b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .brand {
            font-size: 1.1rem;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: #8f000d;
            margin-bottom: 1.75rem;
        }
        h1 {
            font-size: 2.75rem;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            color: #1f1413;
        }
        p {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 1.05rem;
            line-height: 1.7;
            color: #6b5a58;
            max-width: 34rem;
        }
        .status {
            margin-top: 2.25rem;
            padding: 0.55rem 1.25rem;
            border: 1px solid #8f000d;
            color: #8f000d;
            border-radius: 999px;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 0.8rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .mark { margin-bottom: 1.5rem; }
        .retry { margin-top: 2rem; font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 0.85rem; color: #9a8b89; }
        .retry a { color: #8f000d; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="mark">
        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" aria-hidden="true">
            <rect x="1" y="1" width="54" height="54" rx="27" stroke="#8f000d" stroke-width="2"/>
            <text x="50%" y="56%" text-anchor="middle" dominant-baseline="middle"
                  font-family="Georgia, serif" font-size="30" fill="#8f000d">T</text>
        </svg>
    </div>
    <div class="brand">The Daily Cuts</div>
    <h1>We're getting ready.<br>Fresh flavors are on the way.</h1>
    <p>
        Our store is currently undergoing final preparations before our soft launch.
        Check back soon &mdash; we can't wait to serve you.
    </p>
    <div class="status">Coming Soon</div>
    @if (isset($retryAfter))
        <div class="retry">Please try again in {{ $retryAfter }} seconds.</div>
    @endif
</body>
</html>
