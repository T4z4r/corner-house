<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') | Corner House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem 1.5rem;
            background: linear-gradient(180deg, #f8f6f0 0%, #ede9df 100%);
            position: relative;
        }
        .error-brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--ch-accent);
            margin-bottom: 2.5rem;
            text-decoration: none;
        }
        .error-code {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(6rem, 18vw, 12rem);
            font-weight: 700;
            line-height: 1;
            color: rgba(31, 111, 67, 0.12);
            letter-spacing: -0.04em;
            user-select: none;
        }
        .error-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 600;
            color: var(--ch-forest);
            margin: 1rem 0 0.75rem;
        }
        .error-message {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 400;
            color: #6c757d;
            max-width: 460px;
            line-height: 1.65;
            margin-bottom: 2.5rem;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .error-btn {
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.7rem 1.75rem;
            border-radius: 999px;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .error-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .error-btn-primary {
            background: var(--ch-forest);
            color: #fff;
        }
        .error-btn-outline {
            background: transparent;
            border: 1.5px solid var(--ch-forest);
            color: var(--ch-forest);
        }
        .error-footer {
            position: absolute;
            bottom: 1.5rem;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            color: #8a9097;
        }
        .error-footer a {
            color: var(--ch-accent);
            text-decoration: none;
        }
        .error-leaf {
            position: absolute;
            opacity: 0.06;
            pointer-events: none;
        }
        .error-leaf-1 {
            top: 12%;
            left: 8%;
            width: 120px;
            height: 120px;
            background: var(--ch-forest);
            border-radius: 0 50% 50% 50%;
            transform: rotate(25deg);
        }
        .error-leaf-2 {
            bottom: 15%;
            right: 10%;
            width: 80px;
            height: 80px;
            background: var(--ch-accent);
            border-radius: 50% 0 50% 50%;
            transform: rotate(-15deg);
        }
    </style>
</head>
<body class="ch-website">
    <div class="error-page">
        <div class="error-leaf error-leaf-1" aria-hidden="true"></div>
        <div class="error-leaf error-leaf-2" aria-hidden="true"></div>

        <a href="{{ route('home') }}" class="error-brand">Corner House</a>

        @yield('content')

        <div class="error-footer">
            <a href="{{ route('home') }}">Back to home</a> &middot; Northamptonshire
        </div>
    </div>
</body>
</html>
