@csrf

{{-- Shared by Add user (create) and Edit user (edit): the form on the left, a summary + Save on the right. --}}
<div class="row g-4">
    <div class="col-xl-8 d-flex flex-column gap-4">

        {{-- Profile --}}
        <div class="panel">
            <div class="panel-header justify-content-start">
                <span class="section-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h2 class="panel-title">Profile</h2>
                    <div class="small text-muted">Who this person is and how they log in.</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required
                                   class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Sara Haddad">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">@</span>
                            <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="50" required
                                   class="form-control @error('username') is-invalid @enderror" autocomplete="off" placeholder="e.g. sara.haddad">
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Letters, numbers, dots, dashes and underscores. Used to log in.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required
                                   class="form-control @error('email') is-invalid @enderror" placeholder="name@company.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Can also be used to log in instead of the username.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & access --}}
        <div class="panel">
            <div class="panel-header justify-content-start">
                <span class="section-icon"><i class="bi bi-shield-check"></i></span>
                <div>
                    <h2 class="panel-title">Role &amp; access</h2>
                    <div class="small text-muted">What this login may see and do.</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select id="role" name="role" class="form-select select-divider @error('role') is-invalid @enderror" required>
                            @foreach ($roles as $key => $role)
                                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $role['label'] }}</option>
                            @endforeach
                        </select>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="role-hint mt-2" id="role-description"></div>
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

                    {{-- Active / suspended (users.is_active). Suspended users get 403 on their pages (CheckEmployeeStatus). --}}
                    <div class="col-12">
                        <div class="status-box">
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" role="switch"
                                       id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                                <label class="form-check-label fw-semibold" for="is_active">Account is active</label>
                                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="small text-muted mt-1">Turn off to suspend: the user can still log in, but gets "403 Forbidden" on the employee pages.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="panel" id="set-password" style="scroll-margin-top: 5rem">
            <div class="panel-header justify-content-start">
                <span class="section-icon"><i class="bi bi-key"></i></span>
                <div>
                    <h2 class="panel-title">Password</h2>
                    <div class="small text-muted">
                        @if ($user->exists)
                            Leave both fields empty to keep the current password.
                        @else
                            At least 8 characters. Give it to the user so they can log in.
                        @endif
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">{{ $user->exists ? 'New password' : 'Password' }} @unless ($user->exists)<span class="text-danger">*</span>@endunless</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
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
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)
                                   class="form-control border-end-0" placeholder="Type it again">
                            <button type="button" class="btn password-toggle" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary + Save (stays in view while scrolling on wide screens) --}}
    <div class="col-xl-4">
        <div class="panel summary-panel">
            <div class="panel-header">
                <h2 class="panel-title">Summary</h2>
            </div>
            <div class="panel-body">
                @if ($user->exists)
                    <dl class="summary-list mb-0">
                        <div><dt>Role</dt><dd><x-role-badge :user="$user" /></dd></div>
                        <div><dt>Status</dt><dd>
                            @if ($user->is_active)
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Active</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="bi bi-slash-circle me-1"></i>Suspended</span>
                            @endif
                        </dd></div>
                        <div><dt>Employee</dt><dd>
                            @if ($user->employee && auth()->user()->can('employees.view'))
                                <a href="{{ route('employees.show', $user->employee) }}">{{ $user->employee->full_name }}</a>
                            @else
                                {{ $user->employee?->full_name ?? 'Not linked' }}
                            @endif
                        </dd></div>
                        <div><dt>Created</dt><dd title="{{ $user->created_at?->format('d M Y H:i') }}">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd></div>
                        <div><dt>Last updated</dt><dd title="{{ $user->updated_at?->format('d M Y H:i') }}">{{ $user->updated_at?->diffForHumans() ?? '—' }}</dd></div>
                    </dl>
                @else
                    <ul class="summary-steps mb-0">
                        <li><i class="bi bi-1-circle"></i> Fill in the profile and choose a role.</li>
                        <li><i class="bi bi-2-circle"></i> For the Employee role, link the login to its employee record.</li>
                        <li><i class="bi bi-3-circle"></i> Set a password and give it to the user.</li>
                    </ul>
                @endif
            </div>
            <div class="panel-body border-top d-grid gap-2">
                <x-button type="submit" icon="bi-check2">{{ $submitLabel }}</x-button>
                <x-button variant="secondary" href="{{ route('admin.users.index') }}">Cancel</x-button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .section-icon { width: 2.25rem; height: 2.25rem; border-radius: .55rem; display: inline-flex; align-items: center; justify-content: center; background: var(--brand-light); color: var(--brand); font-size: 1.05rem; flex-shrink: 0; }
    .panel-header.justify-content-start { gap: .8rem; }
    .panel-header .panel-title + div { margin-top: .15rem; }
    .role-hint { font-size: .78rem; color: #374151; background: #f6f8f8; border: 1px solid #e3e8e8; border-radius: .5rem; padding: .5rem .7rem; }
    .role-hint:empty { display: none; }
    .status-box { border: 1px solid #e3e8e8; border-radius: .6rem; padding: .8rem 1rem; background: #fbfcfc; }
    @media (min-width: 1200px) { .summary-panel { position: sticky; top: 5.5rem; } }
    .summary-list > div { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed #e3e8e8; }
    .summary-list > div:last-child { border-bottom: 0; }
    .summary-list dt { font-weight: 500; color: #6b7280; font-size: .8rem; }
    .summary-list dd { margin: 0; font-weight: 600; font-size: .82rem; text-align: right; }
    .summary-steps { list-style: none; padding: 0; display: grid; gap: .7rem; font-size: .82rem; color: #374151; }
    .summary-steps i { color: var(--brand); margin-right: .4rem; }
</style>
@endpush

@push('scripts')
<script>
    // Coming from "Reset password" in the users table: put the cursor in the new password box.
    if (location.hash === '#set-password') document.getElementById('password').focus();

    (() => {
        const role = document.getElementById('role');
        // What each role may do, shown under the role list.
        const descriptions = @json(collect($roles)->map(fn ($r) => $r['description']));

        const sync = () => {
            document.getElementById('role-description').textContent = descriptions[role.value] ?? '';

            // "Linked employee" is required only for the Employee role.
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
