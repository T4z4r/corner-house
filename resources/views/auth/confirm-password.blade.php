@extends('layouts.admin.app')

@section('title', 'Confirm your password')

@section('pageLoader', false)

@push('styles')
    <style>
        .ch-confirm {
            position: relative;
            min-height: calc(100vh - 130px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow: hidden;
        }

        .ch-confirm-bg {
            position: absolute;
            inset: -2.5rem;
            background: url('{{ asset('images/hero-garden.jpg') }}') center / cover no-repeat;
            filter: blur(14px) saturate(1.1);
            transform: scale(1.15);
        }

        .ch-confirm-scrim {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(60% 40% at 50% 30%, rgba(31, 111, 67, .18), transparent 70%),
                rgba(12, 22, 19, .62);
        }

        .ch-confirm-modal {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            margin: auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 30px 70px -22px rgba(0, 0, 0, .65);
            padding: 2.25rem 1.9rem 2rem;
            text-align: center;
        }

        .ch-confirm-icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: rgba(31, 111, 67, .12);
            color: #1f6f43;
        }

        .ch-confirm-icon i {
            font-size: 1.5rem;
        }

        .ch-confirm-modal h1 {
            margin: 0 0 .4rem;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -.01em;
            color: #1f2937;
        }

        .ch-confirm-lead {
            margin: 0 0 1.4rem;
            font-size: .9rem;
            line-height: 1.55;
            color: #6b7280;
        }

        .ch-confirm-field {
            margin-bottom: 1.1rem;
            text-align: left;
        }

        .ch-confirm-wrap {
            position: relative;
        }

        .ch-confirm-wrap .ch-confirm-input {
            padding-right: 2.9rem;
        }

        .ch-confirm-input {
            width: 100%;
            padding: .65rem .9rem;
            font: inherit;
            font-size: .95rem;
            color: #1f2937;
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: .6rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .ch-confirm-input::placeholder {
            color: #9ca3af;
        }

        .ch-confirm-input:focus {
            outline: none;
            border-color: #1f6f43;
            box-shadow: 0 0 0 3px rgba(31, 111, 67, .16);
        }

        .ch-confirm-input.is-invalid {
            border-color: #dc3545;
        }

        .ch-confirm-input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, .14);
        }

        .ch-confirm-toggle {
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
            color: #6b7280;
            cursor: pointer;
        }

        .ch-confirm-toggle:hover {
            color: #374151;
        }

        .ch-confirm-toggle svg {
            width: 20px;
            height: 20px;
            color: currentColor;
            pointer-events: none;
            grid-area: 1 / 1;
        }

        .ch-confirm-toggle svg[hidden] {
            display: none;
        }

        .ch-confirm-error {
            margin-top: .3rem;
            font-size: .8rem;
            color: #dc3545;
        }

        .ch-confirm-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            width: 100%;
            padding: .72rem 1rem;
            font: inherit;
            font-size: .95rem;
            font-weight: 600;
            color: #fff;
            border: 0;
            border-radius: .6rem;
            cursor: pointer;
            background: linear-gradient(135deg, #1f6f43 0%, #174f30 100%);
            box-shadow: 0 10px 22px -12px rgba(23, 79, 48, .65);
            transition: filter .15s ease;
        }

        .ch-confirm-btn:hover {
            filter: brightness(1.07);
        }

        .ch-confirm-btn:focus-visible {
            outline: 3px solid rgba(201, 162, 39, .55);
            outline-offset: 2px;
        }

        .ch-confirm-note {
            margin: 1.1rem 0 0;
            font-size: .78rem;
            color: #9ca3af;
        }

        .ch-confirm-cancel {
            display: inline-block;
            margin-top: .85rem;
            font-size: .84rem;
            color: #6b7280;
            text-decoration: none;
        }

        .ch-confirm-cancel:hover {
            color: #374151;
            text-decoration: underline;
        }
    </style>
@endpush

@section('content')
    <section class="ch-confirm">
        <div class="ch-confirm-bg" aria-hidden="true"></div>
        <div class="ch-confirm-scrim" aria-hidden="true"></div>

        <div class="ch-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <div class="ch-confirm-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 id="confirmTitle">Confirm your password</h1>
            <p class="ch-confirm-lead">
                For your security, please enter your password to continue to
                <strong>{{ $continueLabel }}</strong>.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="ch-confirm-field">
                    <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                    <div class="ch-confirm-wrap">
                        <input type="password"
                               class="ch-confirm-input @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               required
                               autofocus
                               autocomplete="current-password">
                        <button type="button" class="ch-confirm-toggle" id="togglePassword" aria-label="Show password" tabindex="-1">
                            <svg id="iconEye" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="iconEyeSlash" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                <path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                <line x1="2" y1="2" x2="22" y2="22"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="ch-confirm-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="ch-confirm-btn">
                    <i class="bi bi-unlock"></i>
                    Unlock &amp; continue
                </button>
            </form>

            <p class="ch-confirm-note">You won't be asked again for 3 hours on this device.</p>

            <a href="{{ route('admin.dashboard') }}" class="ch-confirm-cancel">Cancel and go to dashboard</a>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            var toggle = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            var eye = document.getElementById('iconEye');
            var slash = document.getElementById('iconEyeSlash');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.hidden = show;
                slash.hidden = !show;
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
@endpush