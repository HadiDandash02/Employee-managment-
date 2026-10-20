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
        <div class="text-muted small">A full day, half a day (morning or afternoon) or several days. Your manager (or HR) will answer.</div>
    </div>

    @error('business')
        <div class="alert alert-danger" style="max-width: 44rem"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}</div>
    @enderror

    <div style="max-width: 44rem">
        @include('partials.leave-balance', ['class' => 'mb-3'])
    </div>

    <form method="POST" action="{{ route('leave-requests.store') }}" class="panel" style="max-width: 44rem">
        @csrf

        <div class="panel-body">
            {{-- From: date + morning/afternoon --}}
            <div class="row g-2 mb-3">
                <div class="col-12"><span class="form-label fw-semibold">From <span class="text-danger">*</span></span></div>
                <div class="col-sm-6">
                    <label for="start_date" class="visually-hidden">Start date</label>
                    <input type="date" id="start_date" name="start_date" required value="{{ old('start_date') }}"
                           class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <div class="btn-group w-100" role="group" aria-label="Start in the morning or the afternoon">
                        @foreach (App\Enums\LeavePeriod::cases() as $period)
                            <input type="radio" class="btn-check" name="start_period" id="start_{{ $period->value }}" value="{{ $period->value }}"
                                   @checked(old('start_period', 'morning') === $period->value)>
                            <label class="btn btn-soft" for="start_{{ $period->value }}">{{ $period->label() }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- To: date + morning/afternoon --}}
            <div class="row g-2 mb-3">
                <div class="col-12"><span class="form-label fw-semibold">To <span class="text-danger">*</span></span></div>
                <div class="col-sm-6">
                    <label for="end_date" class="visually-hidden">End date</label>
                    <input type="date" id="end_date" name="end_date" required value="{{ old('end_date') }}"
                           class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <div class="btn-group w-100" role="group" aria-label="End in the morning or the afternoon">
                        @foreach (App\Enums\LeavePeriod::cases() as $period)
                            <input type="radio" class="btn-check" name="end_period" id="end_{{ $period->value }}" value="{{ $period->value }}"
                                   @checked(old('end_period', 'afternoon') === $period->value)>
                            <label class="btn btn-soft" for="end_{{ $period->value }}">{{ $period->label() }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Live count (the server counts again when saving) --}}
            <div class="alert alert-light border small py-2 mb-3" id="days-preview" aria-live="polite">
                <i class="bi bi-calendar-range me-1"></i> Choose the dates to see how many working days this is.
            </div>

            <label for="reason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
            <textarea id="reason" name="reason" rows="3" required maxlength="1000"
                      class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');
    const preview = document.getElementById('days-preview');
    const period = name => document.querySelector(`input[name="${name}"]:checked`)?.value;
    const remaining = {{ $balance->remaining() }};

    // Same rules as App\Services\Leave\LeaveDayCounter: Mon–Fri only, half days for morning/afternoon.
    function countDays() {
        if (!start.value || !end.value) return null;
        const first = new Date(start.value + 'T00:00:00');
        const last = new Date(end.value + 'T00:00:00');
        if (last < first) return null;

        let days = 0;
        for (const d = new Date(first); d <= last; d.setDate(d.getDate() + 1)) {
            if (d.getDay() === 0 || d.getDay() === 6) continue;
            let value = 1;
            if (d.getTime() === first.getTime() && period('start_period') === 'afternoon') value -= 0.5;
            if (d.getTime() === last.getTime() && period('end_period') === 'morning') value -= 0.5;
            days += Math.max(0, value);
        }
        return days;
    }

    function refresh() {
        end.min = start.value;
        const days = countDays();
        if (days === null) {
            preview.className = 'alert alert-light border small py-2 mb-3';
            preview.innerHTML = '<i class="bi bi-calendar-range me-1"></i> Choose the dates to see how many working days this is.';
        } else if (days <= 0) {
            preview.className = 'alert alert-warning small py-2 mb-3';
            preview.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> No working days: check the dates and morning/afternoon.';
        } else if (days > remaining) {
            preview.className = 'alert alert-danger small py-2 mb-3';
            preview.innerHTML = `<i class="bi bi-exclamation-octagon me-1"></i> <strong>${days}</strong> working days, but you only have <strong>${remaining}</strong> left.`;
        } else {
            preview.className = 'alert alert-success small py-2 mb-3';
            preview.innerHTML = `<i class="bi bi-check-circle me-1"></i> <strong>${days}</strong> working ${days === 1 ? 'day' : 'days'} (weekends not counted).`;
        }
    }

    start.addEventListener('change', () => {
        if (!end.value || end.value < start.value) end.value = start.value;
        refresh();
    });
    end.addEventListener('change', refresh);
    document.querySelectorAll('input[name$="_period"]').forEach(radio => radio.addEventListener('change', refresh));
    refresh();
</script>
@endpush
