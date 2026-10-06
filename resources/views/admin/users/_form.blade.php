@csrf

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Account</h2>
    </div>
    <div class="panel-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required
                       class="form-control @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text">@</span>
                    <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="50" required
                           class="form-control @error('username') is-invalid @enderror" autocomplete="off">
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Letters, numbers, dots, dashes and underscores. Used to log in.</div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required
                       class="form-control @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                <select id="role" name="role" class="form-select select-divider @error('role') is-invalid @enderror" required>
                    @foreach ($roles as $key => $role)
                        <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $role['label'] }} — {{ $role['description'] }}</option>
                    @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <hr class="my-4">

        <div class="form-section-title mb-3" id="set-password" style="scroll-margin-top: 5rem"><i class="bi bi-key me-1"></i> Password</div>
        @if ($user->exists)
            <p class="small text-muted">Leave both fields empty to keep the current password.</p>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label for="password" class="form-label">Password @unless ($user->exists)<span class="text-danger">*</span>@endunless</label>
                <input type="password" id="password" name="password" autocomplete="new-password" @required(! $user->exists)
                       class="form-control @error('password') is-invalid @enderror" placeholder="At least 8 characters">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm password @unless ($user->exists)<span class="text-danger">*</span>@endunless</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)
                       class="form-control">
            </div>
        </div>
    </div>

    <div class="panel-body border-top d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.users.index') }}" class="btn btn-clear">Cancel</a>
        <button type="submit" class="btn btn-generate">{{ $submitLabel }}</button>
    </div>
</div>

@push('scripts')
<script>
    // Coming from "Reset" in the users table: put the cursor in the new password box.
    if (location.hash === '#set-password') document.getElementById('password').focus();
</script>
@endpush
