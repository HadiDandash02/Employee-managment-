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

            {{-- Which employee record this login belongs to (users.employee_id) --}}
            <div class="col-md-6">
                <label for="employee_id" class="form-label">
                    Linked employee <span class="text-danger" id="employee-required">*</span>
                </label>
                <select id="employee_id" name="employee_id" class="form-select select-divider @error('employee_id') is-invalid @enderror">
                    <option value="">Not linked</option>
                    @foreach ($employees ?? [] as $employee)
                        <option value="{{ $employee->id }}" @selected((string) old('employee_id', $user->employee_id) === (string) $employee->id)>
                            {{ $employee->full_name }} — {{ $employee->email }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text" id="employee-hint">Required for the Employee role: they will only see this employee's profile.</div>
            </div>

            {{-- Active / suspended (users.is_active). Suspended employees get 403 on their pages (CheckEmployeeStatus). --}}
            <div class="col-12">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Account is active</label>
                    @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Turn off to suspend: the user can still log in, but gets "403 Forbidden" on the employee pages.</div>
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
                <div class="input-group has-validation">
                    <input type="password" id="password" name="password" autocomplete="new-password" @required(! $user->exists)
                           class="form-control border-end-0 @error('password') is-invalid @enderror" placeholder="At least 8 characters">
                    <button type="button" class="btn password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye"></i>
                    </button>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm password @unless ($user->exists)<span class="text-danger">*</span>@endunless</label>
                <div class="input-group">
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)
                           class="form-control border-end-0">
                    <button type="button" class="btn password-toggle" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
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

    // "Linked employee" is required only for the Employee role.
    (() => {
        const role = document.getElementById('role');
        const sync = () => {
            const isEmployee = role.value === 'employee';
            document.getElementById('employee_id').required = isEmployee;
            document.getElementById('employee-required').classList.toggle('d-none', !isEmployee);
            document.getElementById('employee-hint').textContent = isEmployee
                ? 'Required for the Employee role: they will only see this employee\'s profile.'
                : 'Optional: link a staff login to its own employee record (shows "My Profile").';
        };
        role.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
