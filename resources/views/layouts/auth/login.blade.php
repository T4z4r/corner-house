<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') | {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <style>
        :root {
            --ch-primary: #1f6f43;
            --ch-primary-dark: #174f30;
            --ch-accent: #c9a227;
            --ch-ink: #1f2937;
            --ch-muted: #6b7280;
            --ch-border: #e5e9ef;
            --ch-danger: #dc3545;
            --ch-radius: .75rem;
        }

        * { box-sizing: border-box; }

        body.ch-auth {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: .925rem;
            line-height: 1.5;
            color: var(--ch-ink);
            background: linear-gradient(135deg, #174f30 0%, #1f6f43 60%, #2f8a57 100%);
        }

        .ch-auth-card {
            width: 100%;
            max-width: 440px;
            padding: clamp(1.75rem, 5vw, 3rem);
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 24px 50px -20px rgba(10, 30, 20, .55);
        }

        .ch-auth-head {
            display: grid;
            justify-items: center;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .ch-auth-head svg {
            width: 58px;
            height: 58px;
            color: var(--ch-primary);
            margin-bottom: .4rem;
        }

        .ch-auth-head h1 {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .ch-auth-head p {
            margin: .2rem 0 0;
            font-size: .85rem;
            color: var(--ch-muted);
        }

        .ch-auth-field { margin-bottom: 1rem; }

        .ch-auth-label {
            display: block;
            margin-bottom: .35rem;
            font-size: .84rem;
            font-weight: 600;
            color: #374151;
        }

        .ch-auth-input {
            width: 100%;
            padding: .62rem .85rem;
            font: inherit;
            font-size: .95rem;
            color: var(--ch-ink);
            background: #fff;
            border: 1px solid var(--ch-border);
            border-radius: var(--ch-radius);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .ch-auth-input::placeholder { color: #9ca3af; }

        .ch-auth-input:focus {
            outline: none;
            border-color: var(--ch-primary);
            box-shadow: 0 0 0 3px rgba(31, 111, 67, .16);
        }

        .ch-auth-input.is-invalid { border-color: var(--ch-danger); }

        .ch-auth-input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(220, 53, 69, .14); }

        .ch-auth-error {
            margin-top: .3rem;
            font-size: .8rem;
            color: var(--ch-danger);
        }

        .ch-auth-wrap { position: relative; }

        .ch-auth-wrap .ch-auth-input { padding-right: 2.9rem; }

        .ch-auth-toggle {
            position: absolute;
            top: 50%;
            right: .15rem;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--ch-muted);
            cursor: pointer;
        }

        .ch-auth-toggle:hover { color: #374151; }

        .ch-auth-toggle svg {
            width: 20px;
            height: 20px;
            color: currentColor;
            pointer-events: none;
        }

        .ch-auth-check {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.1rem;
            font-size: .9rem;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }

        .ch-auth-check input {
            width: 1rem;
            height: 1rem;
            margin: 0;
            accent-color: var(--ch-primary);
        }

        .ch-auth-btn {
            display: block;
            width: 100%;
            padding: .72rem 1rem;
            font: inherit;
            font-size: .95rem;
            font-weight: 600;
            color: #fff;
            border: 0;
            border-radius: var(--ch-radius);
            cursor: pointer;
            background: linear-gradient(135deg, var(--ch-primary) 0%, var(--ch-primary-dark) 100%);
            box-shadow: 0 10px 22px -12px rgba(23, 79, 48, .65);
            transition: filter .15s ease;
        }

        .ch-auth-btn:hover { filter: brightness(1.07); }

        .ch-auth-btn:focus-visible { outline: 3px solid rgba(201, 162, 39, .55); outline-offset: 2px; }
    </style>
</head>
<body class="ch-auth">
    @yield('content')
    @stack('scripts')
</body>
</html>