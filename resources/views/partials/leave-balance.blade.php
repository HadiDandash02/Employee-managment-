{{-- An employee's leave for one year: earned (base + monthly), taken, waiting, remaining. Needs $balance (App\Services\Leave\LeaveBalance). --}}
@php($f = fn ($days) => App\Services\Leave\LeaveBalance::format($days))
<div class="panel {{ $class ?? '' }}">
    <div class="panel-header">
        <h2 class="panel-title"><i class="bi bi-calendar-heart me-1"></i> {{ $title ?? 'My leave in '.$balance->year }}</h2>
        <span class="small text-muted" title="Base days + days earned each month">
            {{ $f($balance->base) }} + {{ $balance->months }} {{ $balance->months === 1 ? 'month' : 'months' }} × {{ $f($balance->monthlyAccrual) }}
            = <strong>{{ $f($balance->earned()) }}</strong> days earned
        </span>
    </div>
    <div class="panel-body">
        <div class="row g-3 text-center mb-3">
            <div class="col-4">
                <div class="fs-4 fw-bold tabular text-success">{{ $f($balance->remaining()) }}</div>
                <div class="small text-muted">Remaining</div>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold tabular">{{ $f($balance->taken) }}</div>
                <div class="small text-muted">Taken</div>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold tabular text-warning-emphasis">{{ $f($balance->pending) }}</div>
                <div class="small text-muted">Waiting for approval</div>
            </div>
        </div>
        {{-- Taken (teal) + waiting (yellow) out of the days earned --}}
        @php($earned = $balance->earned())
        <div class="progress-stacked" style="height: 10px"
             aria-label="{{ $f($balance->taken + $balance->pending) }} of {{ $f($earned) }} earned days used or requested">
            @if ($earned > 0)
                <div class="progress" role="progressbar" style="width: {{ min(100, $balance->taken / $earned * 100) }}%">
                    <div class="progress-bar"></div>
                </div>
                <div class="progress" role="progressbar" style="width: {{ min(100, $balance->pending / $earned * 100) }}%">
                    <div class="progress-bar bg-warning"></div>
                </div>
            @endif
        </div>
    </div>
</div>
