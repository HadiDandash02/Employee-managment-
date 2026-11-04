@extends('layouts.app')

@section('title', 'Edit contact info')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('profile.show') }}">My Profile</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit contact info</li>
        </ol>
    </nav>

    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">Edit contact info</h1>
        <div class="text-muted small">You can change your phone and address. For anything else (name, salary, department), ask HR.</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('profile.update') }}" class="panel">
                @csrf
                @method('PUT')

                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" placeholder="+961 70 123 456"
                                       value="{{ old('phone', $employee->profile?->phone) }}"
                                       class="form-control @error('phone') is-invalid @enderror">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-text">Digits, spaces, +, - and brackets.</div>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" rows="4" maxlength="255" autocomplete="street-address" placeholder="Street, building, city"
                                      class="form-control @error('address') is-invalid @enderror">{{ old('address', $employee->profile?->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="panel-body border-top d-flex justify-content-end gap-2">
                    <x-button variant="secondary" href="{{ route('profile.show') }}">Cancel</x-button>
                    <x-button icon="bi-check2" type="submit">Save</x-button>
                </div>
            </form>
        </div>

        {{-- What the employee sees but cannot change here --}}
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header"><h2 class="panel-title"><i class="bi bi-lock me-1"></i> Managed by HR</h2></div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Name</span> <span class="fw-semibold">{{ $employee->full_name }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span> <span class="fw-semibold text-break">{{ $employee->email }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Department</span> <span class="fw-semibold">{{ $employee->department?->name ?? '—' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Date of birth</span> <span class="fw-semibold">{{ $employee->date_of_birth?->format('d/m/Y') ?? '—' }}</span></li>
                </ul>
                <div class="panel-body border-top small text-muted">To change any of these, please contact HR.</div>
            </div>
        </div>
    </div>
@endsection
