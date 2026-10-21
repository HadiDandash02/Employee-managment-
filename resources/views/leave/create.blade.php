@extends('layouts.app')

@section('title', 'Request leave')

@push('styles')
    <style>
        /* "From" and "To" cards */
        .when-card { border: 1px solid #e3e8e8; border-radius: .6rem; padding: 1rem; background: #fafcfc; height: 100%; }
        .when-label { font-size: .7rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #6b7280; margin-bottom: .6rem; }
        .when-label i { color: var(--brand); }
        .when-arrow { display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 1.4rem; }

        /* Morning / Afternoon switch */
        .period-toggle .btn { font-size: .85rem; }
        .period-toggle .btn-check:checked + .btn { background: var(--brand); border-color: var(--brand); color: #fff; }

        /* Quick choices */
        .preset { border: 1px solid #dfe5e5; background: #fff; border-radius: 999px; padding: .3rem .8rem; font-size: .8rem; color: #374151; }
        .preset:hover { border-color: var(--brand); color: var(--brand); }
        .preset i { color: var(--brand); }

        .section-title { font-size: .95rem; font-weight: 700; margin-bottom: .2rem; }
        .section-help { font-size: .82rem; color: #6b7280; margin-bottom: 1rem; }

        /* Summary on the right */
        @media (min-width: 1200px) { .leave-aside { position: sticky; top: 5rem; } }
        .summary-days { font-size: 2.6rem; font-weight: 800; line-height: 1; color: var(--brand); }
        .summary-state { border-radius: .5rem; padding: .6rem .75rem; font-size: .82rem; }
        .summary-list { font-size: .85rem; }
        .summary-list dt { font-weight: 500; color: #6b7280; }
        .summary-list dd { font-weight: 600; text-align: end; margin-bottom: .4rem; }
        .rules li { margin-bottom: .45rem; }
        .rules i { color: var(--brand); }
    </style>
@endpush

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}">Leave Requests</a></li>
            <li class="breadcrumb-item active" aria-current="page">Request leave</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0">Request leave</h1>
            <div class="text-muted small">A full day, half a day or several days. Your manager or HR will approve it.</div>
        </div>
        <a href="{{ route('leave-requests.index') }}" class="btn btn-soft">
            <i class="bi bi-arrow-left me-1"></i> My requests
        </a>
    </div>

    @error('business')
        <div class="alert alert-danger d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><strong>The request was not sent.</strong> {{ $message }}</div>
        </div>
    @enderror

    <form method="POST" action="{{ route('leave-requests.store') }}" id="leave-form">
        @csrf

        <div class="row g-4">
            {{-- Left: the form --}}
            <div class="col-xl-8">
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Leave details</h2>
                        <span class="small text-muted"><span class="text-danger">*</span> required</span>
                    </div>

                    {{-- 1. When --}}
                    <div class="panel-body p-4">
                        <div class="section-title">1. When are you away?</div>
                        <div class="section-help">Pick the first and last day. Choose <em>Afternoon</em> to start at midday, or <em>Morning</em> to come back at midday.</div>

                        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Quick choices">
                            <span class="small text-muted align-self-center me-1">Quick choice:</span>
                            <button type="button" class="preset" data-preset="full"><i class="bi bi-calendar-day me-1"></i>One full day</button>
                            <button type="button" class="preset" data-preset="morning"><i class="bi bi-sunrise me-1"></i>Morning only</button>
                            <button type="button" class="preset" data-preset="afternoon"><i class="bi bi-sun me-1"></i>Afternoon only</button>
                        </div>

                        <div class="row g-3 align-items-stretch">
                            <div class="col-md">
                                <div class="when-card">
                                    <div class="when-label"><i class="bi bi-box-arrow-right me-1"></i> From <span class="text-danger">*</span></div>
                                    <label for="start_date" class="visually-hidden">Start date</label>
                                    <input type="date" id="start_date" name="start_date" required value="{{ old('start_date') }}"
                                           class="form-control mb-2 @error('start_date') is-invalid @enderror">
                                    @error('start_date') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                                    <div class="btn-group w-100 period-toggle" role="group" aria-label="Start in the morning or the afternoon">
                                        @foreach (App\Enums\LeavePeriod::cases() as $period)
                                            <input type="radio" class="btn-check" name="start_period" id="start_{{ $period->value }}" value="{{ $period->value }}"
                                                   @checked(old('start_period', 'morning') === $period->value)>
                                            <label class="btn btn-outline-secondary" for="start_{{ $period->value }}">
                                                <i class="bi {{ $period === App\Enums\LeavePeriod::Morning ? 'bi-sunrise' : 'bi-sun' }} me-1"></i>{{ $period->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-auto when-arrow d-none d-md-flex"><i class="bi bi-arrow-right"></i></div>

                            <div class="col-md">
                                <div class="when-card">
                                    <div class="when-label"><i class="bi bi-box-arrow-in-left me-1"></i> To <span class="text-danger">*</span></div>
                                    <label for="end_date" class="visually-hidden">End date</label>
                                    <input type="date" id="end_date" name="end_date" required value="{{ old('end_date') }}"
                                           class="form-control mb-2 @error('end_date') is-invalid @enderror">
                                    @error('end_date') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                                    <div class="btn-group w-100 period-toggle" role="group" aria-label="End in the morning or the afternoon">
                                        @foreach (App\Enums\LeavePeriod::cases() as $period)
                                            <input type="radio" class="btn-check" name="end_period" id="end_{{ $period->value }}" value="{{ $period->value }}"
                                                   @checked(old('end_period', 'afternoon') === $period->value)>
                                            <label class="btn btn-outline-secondary" for="end_{{ $period->value }}">
                                                <i class="bi {{ $period === App\Enums\LeavePeriod::Morning ? 'bi-sunrise' : 'bi-sun' }} me-1"></i>{{ $period->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Why --}}
                    <div class="panel-body p-4 border-top">
                        <div class="section-title">2. Reason <span class="text-danger">*</span></div>
                        <div class="section-help">A short note for your manager, e.g. "Family trip" or "Doctor's appointment".</div>
                        <label for="reason" class="visually-hidden">Reason</label>
                        <textarea id="reason" name="reason" rows="4" required maxlength="1000"
                                  class="form-control @error('reason') is-invalid @enderror"
                                  placeholder="Write the reason for your leave…">{{ old('reason') }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div>@error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror</div>
                            <div class="small text-muted"><span id="reason-count">0</span> / 1000</div>
                        </div>
                    </div>

                    <div class="panel-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2 px-4">
                        <div class="small text-muted"><i class="bi bi-bell me-1"></i> You will get a notification when it is approved or rejected.</div>
                        <div class="d-flex gap-2 align-items-stretch">
                            <a href="{{ route('leave-requests.index') }}" class="btn btn-soft d-inline-flex align-items-center px-3">Cancel</a>
                            <button type="submit" class="btn btn-generate px-4" id="submit-button">
                                <i class="bi bi-send me-1"></i> Send request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: live summary, balance, rules --}}
            <div class="col-xl-4">
                <div class="leave-aside">
                    <div class="panel mb-4">
                        <div class="panel-header"><h2 class="panel-title">Your request</h2></div>
                        <div class="panel-body">
                            <div class="d-flex align-items-end gap-2 mb-1">
                                <span class="summary-days tabular" id="summary-days">0</span>
                                <span class="text-muted mb-1" id="summary-days-word">working days</span>
                            </div>
                            <div class="small text-muted mb-3" id="summary-period">Choose the dates.</div>

                            <dl class="row summary-list mb-3">
                                <dt class="col-7">Available now</dt>
                                <dd class="col-5 tabular">{{ App\Services\Leave\LeaveBalance::format($balance->remaining()) }} days</dd>
                                <dt class="col-7">Left after this request</dt>
                                <dd class="col-5 tabular" id="summary-after">—</dd>
                            </dl>

                            <div class="summary-state bg-light text-muted" id="summary-state" aria-live="polite">
                                <i class="bi bi-calendar-range me-1"></i> Choose the dates to see how many working days this is.
                            </div>
                        </div>
                    </div>

                    @include('partials.leave-balance', ['class' => 'mb-4'])

                    <div class="panel">
                        <div class="panel-header"><h2 class="panel-title">Good to know</h2></div>
                        <div class="panel-body">
                            <ul class="list-unstyled small mb-0 rules">
                                <li><i class="bi bi-check2 me-1"></i> Saturdays and Sundays are not counted.</li>
                                <li><i class="bi bi-check2 me-1"></i> You earn {{ config('leave.monthly_accrual') }} days each month, plus {{ App\Services\Leave\LeaveBalance::format($balance->base) }} days at the start of the year.</li>
                                <li><i class="bi bi-check2 me-1"></i> Days waiting for approval are kept aside for you.</li>
                                <li><i class="bi bi-check2 me-1"></i> One request stays inside one year.</li>
                                <li class="mb-0"><i class="bi bi-check2 me-1"></i> A morning and an afternoon of the same day can be two separate requests.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');
    const reason = document.getElementById('reason');
    const submitButton = document.getElementById('submit-button');
    const period = name => document.querySelector(`input[name="${name}"]:checked`)?.value;
    const setPeriod = (name, value) => { document.getElementById(`${name.replace('_period', '')}_${value}`).checked = true; };
    const remaining = {{ $balance->remaining() }};
    const fmt = n => Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');

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

    // "Mon 02 Nov, afternoon → Wed 04 Nov, morning"
    function describe() {
        const label = (value, half) => {
            const d = new Date(value + 'T00:00:00');
            return d.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }) + (half ? `, ${half}` : '');
        };
        if (start.value === end.value) {
            const s = period('start_period'), e = period('end_period');
            return label(start.value, s === 'afternoon' ? 'afternoon only' : (e === 'morning' ? 'morning only' : 'full day'));
        }
        return `${label(start.value, period('start_period') === 'afternoon' ? 'afternoon' : '')} → ${label(end.value, period('end_period') === 'morning' ? 'morning' : '')}`;
    }

    const state = (cls, icon, html) => {
        const box = document.getElementById('summary-state');
        box.className = `summary-state mb-0 ${cls.startsWith('alert-') ? 'alert ' + cls : cls}`;
        box.innerHTML = `<i class="bi ${icon} me-1"></i> ${html}`;
    };

    function refresh() {
        end.min = start.value;
        const days = countDays();
        const daysEl = document.getElementById('summary-days');
        const after = document.getElementById('summary-after');
        const periodEl = document.getElementById('summary-period');

        if (days === null) {
            daysEl.textContent = '0';
            document.getElementById('summary-days-word').textContent = 'working days';
            periodEl.textContent = 'Choose the dates.';
            after.textContent = '—';
            after.className = 'col-5 tabular';
            state('bg-light text-muted', 'bi-calendar-range', 'Choose the dates to see how many working days this is.');
            submitButton.disabled = false;
            return;
        }

        daysEl.textContent = fmt(days);
        document.getElementById('summary-days-word').textContent = days === 1 ? 'working day' : 'working days';
        periodEl.textContent = describe();
        after.textContent = `${fmt(remaining - days)} days`;
        after.className = `col-5 tabular ${remaining - days < 0 ? 'text-danger' : 'text-success'}`;

        if (days <= 0) {
            state('alert-warning', 'bi-exclamation-triangle', 'No working days: check the dates and morning/afternoon.');
            submitButton.disabled = true;
        } else if (days > remaining) {
            state('alert-danger', 'bi-exclamation-octagon', `Not enough leave: you only have <strong>${fmt(remaining)}</strong> days available.`);
            submitButton.disabled = true;
        } else {
            state('alert-success', 'bi-check-circle', 'Looks good. You can send the request.');
            submitButton.disabled = false;
        }
    }

    // Quick choices: one day, whole / morning / afternoon.
    document.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
        if (!start.value) start.value = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD, local date
        end.value = start.value;
        const preset = button.dataset.preset;
        setPeriod('start_period', preset === 'afternoon' ? 'afternoon' : 'morning');
        setPeriod('end_period', preset === 'morning' ? 'morning' : 'afternoon');
        refresh();
    }));

    start.addEventListener('change', () => {
        if (!end.value || end.value < start.value) end.value = start.value;
        refresh();
    });
    end.addEventListener('change', refresh);
    document.querySelectorAll('input[name$="_period"]').forEach(radio => radio.addEventListener('change', refresh));

    const countReason = () => { document.getElementById('reason-count').textContent = reason.value.length; };
    reason.addEventListener('input', countReason);

    countReason();
    refresh();
</script>
@endpush
