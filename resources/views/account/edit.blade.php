@extends('layouts.app')

@section('title', 'My account')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">My account</h1>
        <div class="text-muted small">Your profile and password.</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-body text-center py-4">
                    <span class="avatar text-bg-primary mb-3" style="width: 72px; height: 72px; font-size: 26px">{{ $user->initials }}</span>
                    <div class="fw-bold fs-5">{{ $user->name }}</div>
                    <div class="text-muted small mb-2">{{ '@'.$user->username }}</div>
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                        <i class="bi bi-shield-check me-1"></i>{{ $user->role_label }}
                    </span>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-3">
                        <span class="text-muted">Email</span> <span class="text-truncate ms-3">{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-3">
                        <span class="text-muted">Member since</span> <span>{{ $user->created_at?->format('d M Y') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <form method="POST" action="{{ route('account.password') }}" class="panel" id="change-password" style="scroll-margin-top: 5rem">
                @csrf
                @method('PUT')

                <div class="panel-header">
                    <h2 class="panel-title">Change password</h2>
                </div>
                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="current_password" class="form-label">Current password <span class="text-danger">*</span></label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
                                   class="form-control @error('current_password', 'password') is-invalid @enderror" style="max-width: 24rem">
                            @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">New password <span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"
                                   class="form-control @error('password', 'password') is-invalid @enderror" placeholder="At least 8 characters">
                            @error('password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm new password <span class="text-danger">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
                                   class="form-control">
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        After changing it, you stay logged in here, but other browsers using this account are logged out.
                    </p>
                </div>
                <div class="panel-body border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-generate">
                        <i class="bi bi-key me-1"></i> Change password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Coming from "Change password" in the profile menu: put the cursor in the first password box.
    if (location.hash === '#change-password' || {{ $errors->password->any() ? 'true' : 'false' }}) {
        document.getElementById('current_password').focus();
    }
</script>
@endpush
