@extends('layouts.app')

@section('title', 'Log in')

@push('styles')
    <style>
        .login-wrap { min-height: calc(100vh - 3rem); display: flex; align-items: center; justify-content: center; padding: 1.5rem 0; }
        .login-card { width: 100%; max-width: 940px; overflow: hidden; display: grid; grid-template-columns: 1fr 1fr; }
        .login-side {
            background: linear-gradient(160deg, #13907f 0%, #0b5f54 100%); color: #fff; padding: 2.5rem;
            display: flex; flex-direction: column; justify-content: space-between; position: relative;
        }
        .login-side::after {
            content: ''; position: absolute; right: -80px; bottom: -80px; width: 260px; height: 260px;
            border-radius: 50%; background: rgba(255, 255, 255, .07);
        }
        .login-side .brand-mark { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, .15); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .login-side li { display: flex; gap: .6rem; margin-bottom: .7rem; opacity: .92; }
        .login-form { padding: 2.75rem 2.5rem; }
        .login-form .form-control { min-height: 2.75rem; }
        .password-toggle { border: 1px solid #dfe5e5; border-left: 0; background: #fff; color: #6b7280; border-radius: 0 .55rem .55rem 0; }
        @media (max-width: 767.98px) {
            .login-card { grid-template-columns: 1fr; }
            .login-side { display: none; }
            .login-form { padding: 2rem 1.5rem; }
        }
    </style>
@endpush

@section('content')
    <div class="login-wrap">
        <div class="panel login-card shadow-sm">
            <div class="login-side">
                <div>
                    <span class="brand-mark mb-4"><i class="bi bi-people-fill"></i></span>
                    <h1 class="h3 fw-bold mb-2">Employee Management</h1>
                    <p class="opacity-75 mb-4">Manage employee records, departments and salaries in one place.</p>
                    <ul class="list-unstyled small mb-0">
                        <li><i class="bi bi-check2-circle"></i> Search, filter and export employee data</li>
                        <li><i class="bi bi-check2-circle"></i> Role-based access: Admin, HR, Manager, Viewer</li>
                        <li><i class="bi bi-check2-circle"></i> Dashboard with user management</li>
                    </ul>
                </div>
                <div class="small opacity-75">&copy; {{ date('Y') }} Employee Management</div>
            </div>

            <div class="login-form">
                <h2 class="h4 fw-bold mb-1">Welcome back</h2>
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
                            <button type="button" class="btn password-toggle" id="password-toggle" aria-label="Show password">
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
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('password-toggle').addEventListener('click', event => {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        event.currentTarget.innerHTML = `<i class="bi ${show ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        event.currentTarget.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    document.getElementById('login-form').addEventListener('submit', () => {
        const button = document.getElementById('login-btn');
        button.disabled = true;
        button.querySelector('.spinner-border').classList.remove('d-none');
    });
</script>
@endpush
