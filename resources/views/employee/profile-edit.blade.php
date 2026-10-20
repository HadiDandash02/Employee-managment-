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

    <form method="POST" action="{{ route('profile.update') }}" class="panel" style="max-width: 40rem">
        @csrf
        @method('PUT')

        <div class="panel-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel"
                           value="{{ old('phone', $employee->profile?->phone) }}"
                           class="form-control @error('phone') is-invalid @enderror">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" rows="3" maxlength="255" autocomplete="street-address"
                              class="form-control @error('address') is-invalid @enderror">{{ old('address', $employee->profile?->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="panel-body border-top d-flex justify-content-end gap-2">
            <a href="{{ route('profile.show') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-generate">
                <i class="bi bi-check2 me-1"></i> Save
            </button>
        </div>
    </form>
@endsection
