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
                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                    <input type="text" id="department" name="department" list="department-options"
                           value="{{ old('department', $employee->department) }}" maxlength="100" required
                           class="form-control @error('department') is-invalid @enderror" placeholder="e.g. Engineering">
                    @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <datalist id="department-options">
                    @foreach ($departments ?? [] as $department)
                        <option value="{{ $department }}"></option>
                    @endforeach
                </datalist>
                <div class="form-text">Pick an existing department or type a new one.</div>
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
        </div>
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
