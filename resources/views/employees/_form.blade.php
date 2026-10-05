@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="first_name" class="form-label">First Name</label>
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}"
               class="form-control @error('first_name') is-invalid @enderror">
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="last_name" class="form-label">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}"
               class="form-control @error('last_name') is-invalid @enderror">
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="department" class="form-label">Department</label>
        <input type="text" id="department" name="department" value="{{ old('department', $employee->department) }}"
               class="form-control @error('department') is-invalid @enderror">
        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="salary" class="form-label">Salary</label>
        <input type="number" step="0.01" min="0" id="salary" name="salary" value="{{ old('salary', $employee->salary) }}"
               class="form-control @error('salary') is-invalid @enderror">
        @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="date_of_birth" class="form-label">Date of Birth</label>
        <input type="date" id="date_of_birth" name="date_of_birth"
               value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}"
               class="form-control @error('date_of_birth') is-invalid @enderror">
        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
</div>
