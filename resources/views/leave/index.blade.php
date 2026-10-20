@extends('layouts.app')

@section('title', 'Leave Requests')

@push('styles')
    <style>
        /* Teal header, like the other tables */
        .leave-table thead th {
            background: var(--brand); color: #fff; font-weight: 700; font-size: .78rem; white-space: nowrap;
            border: 0; border-right: 1px solid rgba(255, 255, 255, .18); padding: .85rem .75rem;
        }
        .leave-table thead th:last-child { border-right: 0; }
        .leave-table td { padding: .85rem .75rem; vertical-align: middle; font-size: .85rem; }
        .leave-table tbody tr:hover > td { background: #f7fbfa; }
        .leave-reason { max-width: 18rem; }
    </style>
@endpush

@section('content')
    @php
        $reviewer = auth()->user()->can('leave.approve');
        $total = $counts->sum();
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">{{ $reviewer ? 'Leave Requests' : 'My Leave Requests' }}</h1>
            <div class="text-muted small">
                {{ $reviewer ? 'Requests you can approve or reject, and your own.' : 'Ask for days off and follow the answer.' }}
            </div>
        </div>
        @can('create', App\Models\LeaveRequest::class)
            <a href="{{ route('leave-requests.create') }}" class="btn btn-generate">
                <i class="bi bi-calendar-plus me-1"></i> Request leave
            </a>
        @endcan
    </div>

    @if ($balance)
        @include('partials.leave-balance', ['class' => 'mb-4'])
    @endif

    <div class="panel">
        <div class="panel-header">
            {{-- Status tabs, with the number of requests in each --}}
            <ul class="nav nav-pills small gap-1">
                <li class="nav-item">
                    <a href="{{ route('leave-requests.index') }}" class="nav-link py-1 px-3 {{ $status ? '' : 'active' }}">
                        All <span class="badge rounded-pill text-bg-light border ms-1">{{ $total }}</span>
                    </a>
                </li>
                @foreach (App\Enums\LeaveStatus::cases() as $case)
                    <li class="nav-item">
                        <a href="{{ route('leave-requests.index', ['status' => $case->value]) }}"
                           class="nav-link py-1 px-3 {{ $status === $case ? 'active' : '' }}">
                            {{ $case->label() }} <span class="badge rounded-pill text-bg-light border ms-1">{{ $counts[$case->value] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="table-responsive">
            <table class="table leave-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 3rem">#</th>
                        <th>Employee</th>
                        <th>Dates</th>
                        <th class="text-center">Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Decided by</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaveRequests as $leaveRequest)
                        @php $employee = $leaveRequest->employee; @endphp
                        <tr>
                            <td class="text-muted tabular">{{ $leaveRequests->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @include('partials.avatar', ['size' => 32])
                                    <div class="lh-sm">
                                        <div class="fw-semibold">
                                            {{ $employee->full_name }}
                                            @if ($leaveRequest->isOwnedBy(auth()->user()))
                                                <span class="badge text-bg-light border fw-normal ms-1">You</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">{{ $employee->department?->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap tabular">{{ $leaveRequest->period_text }}</td>
                            <td class="text-center tabular">{{ $leaveRequest->days_text }}</td>
                            <td><div class="text-truncate leave-reason" title="{{ $leaveRequest->reason }}">{{ $leaveRequest->reason }}</div></td>
                            <td>
                                @php $c = $leaveRequest->status->color(); @endphp
                                <span class="badge rounded-pill bg-{{ $c }}-subtle text-{{ $c }}-emphasis border border-{{ $c }}-subtle">
                                    <i class="bi {{ $leaveRequest->status->icon() }} me-1"></i>{{ $leaveRequest->status->label() }}
                                </span>
                            </td>
                            <td class="small">
                                @if ($leaveRequest->approver)
                                    <div>{{ $leaveRequest->approver->name }}</div>
                                    <div class="text-muted">{{ $leaveRequest->approved_at?->format('d M Y H:i') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn btn-sm btn-soft">
                                    @if ($leaveRequest->isPending() && ! $leaveRequest->isOwnedBy(auth()->user()) && auth()->user()->can('approve', $leaveRequest))
                                        <i class="bi bi-check2-square me-1"></i> Review
                                    @else
                                        <i class="bi bi-eye me-1"></i> View
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-x fs-2 d-block mb-1 opacity-50"></i>
                                {{ $status ? 'No '.strtolower($status->label()).' leave requests.' : 'No leave requests yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leaveRequests->total() > 0)
            <div class="panel-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <div class="small text-muted">
                    Showing {{ $leaveRequests->firstItem() }} to {{ $leaveRequests->lastItem() }} of {{ $leaveRequests->total() }} requests
                </div>
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
@endsection
