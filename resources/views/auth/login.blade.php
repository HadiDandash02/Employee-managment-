@extends('layouts.app')

@section('title', 'Log in')

{{-- Use the whole screen instead of the normal page container --}}
@section('fullpage', true)

@push('styles')
    <style>
        /* Full-screen split: teal brand panel on the left, the form on the right */
        .login-page { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); background: #fff; }

        .login-side {
            position: relative; overflow: hidden; color: #fff; padding: 3rem 3.5rem;
            background: linear-gradient(155deg, #16a08d 0%, #0f7b6c 45%, #0a5249 100%);
            display: flex; flex-direction: column; justify-content: space-between;
        }
        /* soft decorative circles */
        .login-side::before, .login-side::after { content: ''; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .07); }
        .login-side::before { width: 420px; height: 420px; right: -140px; bottom: -140px; }
        .login-side::after { width: 220px; height: 220px; left: -80px; top: 35%; }
        .login-side > * { position: relative; z-index: 1; }
        .login-side .brand { display: flex; align-items: center; gap: .75rem; font-weight: 700; font-size: 1.1rem; }
        .login-side .brand-mark { width: 48px; height: 48px; border-radius: 14px; background: rgba(255, 255, 255, .16); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .login-side h1 { font-size: clamp(1.9rem, 3vw, 2.75rem); font-weight: 800; line-height: 1.15; letter-spacing: -.01em; }
        .login-side .lead-text { max-width: 30rem; opacity: .85; font-size: 1.05rem; }
        .login-feature { display: flex; gap: .85rem; align-items: flex-start; margin-bottom: 1.1rem; max-width: 30rem; }
        .login-feature .icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(255, 255, 255, .14); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .login-feature small { opacity: .75; }

        .login-main { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 3rem 2rem; }
        .login-box { width: 100%; max-width: 420px; }
        .login-box .form-control { min-height: 2.9rem; }
        .login-mobile-brand { display: none; }

        @media (max-width: 991.98px) {
            .login-page { grid-template-columns: 1fr; }
            .login-side { display: none; }
            .login-mobile-brand { display: flex; align-items: center; gap: .6rem; font-weight: 700; margin-bottom: 2rem; }
        }
    </style>
@endpush

@section('content')
    <div class="login-page">
        <aside class="login-side">
            <div class="brand">
                <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                Employee Management
            </div>

            <div>
                <h1 class="mb-3">All your employees,<br>in one place.</h1>
                <p class="lead-text mb-5">Manage employee records, departments, salaries and user access from a single dashboard.</p>

                <div class="login-feature">
                    <span class="icon"><i class="bi bi-funnel"></i></span>
                    <div><div class="fw-semibold">Search, filter &amp; export</div><small>Find anyone in seconds and export to Excel, CSV or PDF.</small></div>
                </div>
                <div class="login-feature">
                    <span class="icon"><i class="bi bi-diagram-3"></i></span>
                    <div><div class="fw-semibold">Companies, branches &amp; departments</div><small>Every employee in the right place, with skills and profile.</small></div>
                </div>
                <div class="login-feature">
                    <span class="icon"><i class="bi bi-shield-lock"></i></span>
                    <div><div class="fw-semibold">Role-based access</div><small>Admin, HR, Manager and Employee each see only what they need.</small></div>
                </div>
            </div>

            <div class="small opacity-75">&copy; {{ date('Y') }} Employee Management</div>
        </aside>

        <main class="login-main">
            <div class="login-box">
                <div class="login-mobile-brand">
                    <span class="brand-mark"><i class="bi bi-people-fill"></i></span> Employee Management
                </div>

                @include('layouts._alerts')

                <h2 class="h3 fw-bold mb-1">Welcome back</h2>
                <p class="text-muted mb-4">Log in with your username or email.</p>

                <form method="POST" action="{{ route('login.store') }}" id="login-form" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="login" class="form-label">Username or email</label>
                        <input type="text" id="login" name="login" value="{{ old('login') }}" autocomplete="username"
                               class="form-control @error('login') is-invalid @enderror" placeholder="e.g. admin" required autofocus>
                        @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group has-validation">
                            <input type="password" id="password" name="password" autocomplete="current-password"
                                   class="form-control border-end-0 @error('password') is-invalid @enderror" placeholder="Your password" required>
                            <button type="button" class="btn password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" @checked(old('remember'))>
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-generate w-100 py-2" id="login-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
                        Log in
                    </button>
                </form>
            </div>
        </main>
    </div>
@endsection

@push('scripts')
<script>
    // The eye button (show/hide password) is handled by the shared script in layouts/app.
    document.getElementById('login-form').addEventListener('submit', () => {
        const button = document.getElementById('login-btn');
        button.disabled = true;
        button.querySelector('.spinner-border').classList.remove('d-none');
    });
</script>
@endpush
