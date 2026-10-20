@csrf

@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            Please fix {{ $errors->count() === 1 ? 'the error' : 'the '.$errors->count().' errors' }} below.
        </div>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="form-section-title mb-3"><i class="bi bi-person-vcard me-1"></i> Personal information</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}"
                       class="form-control @error('first_name') is-invalid @enderror" maxlength="100" required autofocus>
                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}"
                       class="form-control @error('last_name') is-invalid @enderror" maxlength="100" required>
                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}"
                           class="form-control @error('email') is-invalid @enderror" maxlength="255" required
                           placeholder="name@company.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="col-md-6">
                <label for="date_of_birth" class="form-label">Date of birth <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}"
                           max="{{ now()->subDay()->format('Y-m-d') }}"
                           class="form-control @error('date_of_birth') is-invalid @enderror" required>
                    @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text" id="age-hint"></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="form-section-title mb-3"><i class="bi bi-briefcase me-1"></i> Employment</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                    <select id="department_id" name="department_id" required
                            class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">Choose a department</option>
                        @foreach (($departments ?? collect())->groupBy('location') as $location => $group)
                            <optgroup label="{{ $location }}">
                                @foreach ($group as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id) === (string) $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Grouped by company · branch.</div>
            </div>

            <div class="col-md-6">
                <label for="salary" class="form-label">Annual salary <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-cash-stack"></i></span>
                    <input type="number" step="0.01" min="0" max="99999999.99" id="salary" name="salary"
                           value="{{ old('salary', $employee->salary) }}" required
                           class="form-control @error('salary') is-invalid @enderror" placeholder="0.00">
                    @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text" id="salary-hint"></div>
            </div>

            <div class="col-md-6">
                <label for="base_leave_days" class="form-label">Base leave days</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-calendar-heart"></i></span>
                    <input type="number" step="0.5" min="0" max="60" id="base_leave_days" name="base_leave_days"
                           value="{{ old('base_leave_days', $employee->base_leave_days) }}" required
                           class="form-control @error('base_leave_days') is-invalid @enderror">
                    <span class="input-group-text">working days</span>
                    @error('base_leave_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">
                    Given at the start of every year (default {{ config('leave.base_days') }}).
                    On top, everyone earns {{ config('leave.monthly_accrual') }} days each month.
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Saved in employee_profiles (Employee hasOne EmployeeProfile); all optional --}}
        <div class="form-section-title mb-3"><i class="bi bi-telephone me-1"></i> Contact &amp; profile <span class="text-muted fw-normal text-lowercase">(optional)</span></div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="phone" class="form-label">Phone number</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $employee->profile?->phone) }}" maxlength="30"
                       class="form-control @error('phone') is-invalid @enderror" placeholder="+961 70 123 456">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label for="hire_date" class="form-label">Hire date</label>
                <input type="date" id="hire_date" name="hire_date" max="{{ now()->format('Y-m-d') }}"
                       value="{{ old('hire_date', $employee->profile?->hire_date?->format('Y-m-d')) }}"
                       class="form-control @error('hire_date') is-invalid @enderror">
                @error('hire_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label for="address" class="form-label">Address</label>
                <input type="text" id="address" name="address" value="{{ old('address', $employee->profile?->address) }}" maxlength="255"
                       class="form-control @error('address') is-invalid @enderror" placeholder="Street, city">
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <hr class="my-4">

        {{-- Saved in the employee_skill pivot table (Employee belongsToMany Skill) --}}
        <div class="form-section-title mb-3"><i class="bi bi-stars me-1"></i> Skills <span class="text-muted fw-normal text-lowercase">(optional)</span></div>

        {{-- Always sent, so unticking every box removes all skills --}}
        <input type="hidden" name="skills" value="">
        @php($selectedSkills = collect(old('skills', $employee->exists ? $employee->skills->pluck('id')->all() : []))->map(fn ($id) => (int) $id))
        <div class="d-flex flex-wrap gap-2 @error('skills') is-invalid @enderror @error('skills.*') is-invalid @enderror">
            @forelse ($skills ?? [] as $skill)
                <input type="checkbox" class="btn-check" name="skills[]" id="skill-{{ $skill->id }}" value="{{ $skill->id }}"
                       autocomplete="off" @checked($selectedSkills->contains($skill->id))>
                <label class="btn btn-sm btn-outline-primary rounded-pill" for="skill-{{ $skill->id }}">{{ $skill->name }}</label>
            @empty
                <span class="small text-muted">No skills yet. Run <code>php artisan db:seed --class=OrganizationSeeder</code> to add some.</span>
            @endforelse
        </div>
        @error('skills') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @error('skills.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2 p-3">
        <button type="submit" class="btn btn-primary" id="submit-btn">
            <span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>
            <i class="bi bi-check-lg me-1"></i> {{ $submitLabel }}
        </button>
        <a href="{{ $cancelUrl ?? route('employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i> Cancel
        </a>
        <span class="badge text-bg-warning ms-auto d-none" id="unsaved-badge">
            <i class="bi bi-pencil me-1"></i> Unsaved changes
        </span>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('submit-btn').closest('form');
    const dob = document.getElementById('date_of_birth');
    const salary = document.getElementById('salary');
    const ageHint = document.getElementById('age-hint');
    const salaryHint = document.getElementById('salary-hint');
    const badge = document.getElementById('unsaved-badge');
    const initial = new URLSearchParams(new FormData(form)).toString();
    let submitting = false;

    function updateAge() {
        if (!dob.value) { ageHint.textContent = ''; return; }
        const birth = new Date(dob.value + 'T00:00:00');
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        if (today < new Date(today.getFullYear(), birth.getMonth(), birth.getDate())) age--;
        ageHint.textContent = age >= 0 ? `Age: ${age} years` : '';
    }

    function updateSalary() {
        const value = parseFloat(salary.value);
        salaryHint.textContent = isNaN(value) ? '' :
            `${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} per year · ` +
            `${(value / 12).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} per month`;
    }

    const isDirty = () => new URLSearchParams(new FormData(form)).toString() !== initial;

    form.addEventListener('input', () => badge.classList.toggle('d-none', !isDirty()));
    dob.addEventListener('input', updateAge);
    salary.addEventListener('input', updateSalary);

    // Warn before leaving the page with unsaved changes.
    window.addEventListener('beforeunload', event => {
        if (!submitting && isDirty()) event.preventDefault();
    });

    // Prevent double submits and show a spinner.
    form.addEventListener('submit', () => {
        submitting = true;
        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.querySelector('.spinner-border').classList.remove('d-none');
        button.querySelector('.bi').classList.add('d-none');
    });

    updateAge();
    updateSalary();
})();
</script>
@endpush
