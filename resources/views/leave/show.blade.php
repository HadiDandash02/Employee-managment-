@extends('layouts.app')

@section('title', 'Leave request #'.$leaveRequest->id)

@section('content')
    @php
        $employee = $leaveRequest->employee;
        $c = $leaveRequest->status->color();
        $canDecide = $leaveRequest->isPending() && ! $leaveRequest->isOwnedBy(auth()->user()) && auth()->user()->can('approve', $leaveRequest);
    @endphp

    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}">Leave Requests</a></li>
            <li class="breadcrumb-item active" aria-current="page">#{{ $leaveRequest->id }}</li>
        </ol>
    </nav>

    @error('business')
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}</div>
    @enderror

    <div class="panel mb-4">
        <div class="panel-body d-flex flex-wrap align-items-center gap-3">
            @include('partials.avatar', ['size' => 56])
            <div class="me-auto">
                <h1 class="h4 fw-bold mb-1">{{ $employee->full_name }}</h1>
                <div class="text-muted small"><i class="bi bi-diagram-3 me-1"></i>{{ $employee->department?->name ?? '—' }}</div>
            </div>
            <span class="badge rounded-pill fs-6 bg-{{ $c }}-subtle text-{{ $c }}-emphasis border border-{{ $c }}-subtle">
                <i class="bi {{ $leaveRequest->status->icon() }} me-1"></i>{{ $leaveRequest->status->label() }}
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel mb-4">
                <div class="panel-header"><h2 class="panel-title">Request</h2></div>
                <div class="panel-body">
                    <div class="row g-4">
                        <div class="col-sm-4">
                            <div class="info-label">From</div>
                            <div>{{ $leaveRequest->start_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="info-label">To</div>
                            <div>{{ $leaveRequest->end_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="info-label">Days</div>
                            <div>{{ $leaveRequest->days }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Reason</div>
                            <div style="white-space: pre-line">{{ $leaveRequest->reason }}</div>
                        </div>
                        @if ($leaveRequest->approver)
                            <div class="col-12">
                                <div class="info-label">{{ $leaveRequest->status->label() }} by</div>
                                <div>{{ $leaveRequest->approver->name }} <span class="text-muted">· {{ $leaveRequest->approved_at?->format('d M Y H:i') }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($canDecide)
                {{-- One form, two buttons: formaction sends it to approve or reject --}}
                <form method="POST" action="{{ route('leave-requests.approve', $leaveRequest) }}" class="panel">
                    @csrf
                    <div class="panel-header"><h2 class="panel-title">Decision</h2></div>
                    <div class="panel-body">
                        <label for="note" class="form-label">Note <span class="text-muted small">(required when rejecting)</span></label>
                        <textarea id="note" name="note" rows="2" maxlength="500"
                                  class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="panel-body border-top d-flex justify-content-end gap-2">
                        <button type="submit" formaction="{{ route('leave-requests.reject', $leaveRequest) }}" class="btn btn-outline-danger"
                                onclick="return confirm('Reject this leave request?')">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Approve this leave request?')">
                            <i class="bi bi-check-circle me-1"></i> Approve
                        </button>
                    </div>
                </form>
            @elseif ($leaveRequest->isPending() && $leaveRequest->isOwnedBy(auth()->user()))
                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-hourglass-split me-1"></i> Waiting for your manager or HR. You cannot decide your own request.
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">History</h2></div>
                <ul class="list-group list-group-flush">
                    @foreach ($leaveRequest->actions as $action)
                        <li class="list-group-item px-3 py-3">
                            <div class="d-flex gap-2">
                                <i class="bi {{ $action->action->icon() }} mt-1"></i>
                                <div>
                                    <div class="fw-semibold small">
                                        {{ $action->action->label() }}
                                        <span class="text-muted fw-normal">by {{ $action->user?->name ?? 'a deleted user' }}</span>
                                    </div>
                                    <div class="small text-muted">{{ $action->created_at?->format('d M Y H:i') }}</div>
                                    @if ($action->note)
                                        <div class="small mt-1 p-2 bg-light rounded" style="white-space: pre-line">{{ $action->note }}</div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
