@extends('layouts.app')

@section('title', 'Request leave')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}">Leave Requests</a></li>
            <li class="breadcrumb-item active" aria-current="page">Request leave</li>
        </ol>
    </nav>

    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">Request leave</h1>
        <div class="text-muted small">Your manager (or HR) will approve or reject it.</div>
    </div>

    @error('business')
        <div class="alert alert-danger" style="max-width: 40rem"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('leave-requests.store') }}" class="panel" style="max-width: 40rem">
        @csrf

        <div class="panel-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="start_date" class="form-label">Start date <span class="text-danger">*</span></label>
                    <input type="date" id="start_date" name="start_date" required value="{{ old('start_date') }}"
                           class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label for="end_date" class="form-label">End date <span class="text-danger">*</span></label>
                    <input type="date" id="end_date" name="end_date" required value="{{ old('end_date') }}"
                           class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea id="reason" name="reason" rows="3" required maxlength="1000"
                              class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="panel-body border-top d-flex justify-content-end gap-2">
            <a href="{{ route('leave-requests.index') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-generate">
                <i class="bi bi-send me-1"></i> Send request
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // The end date cannot be before the start date (the server checks it again).
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');
    start.addEventListener('change', () => {
        end.min = start.value;
        if (!end.value || end.value < start.value) end.value = start.value;
    });
</script>
@endpush
