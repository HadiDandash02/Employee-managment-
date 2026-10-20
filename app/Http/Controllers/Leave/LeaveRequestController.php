<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;

use App\Enums\LeaveStatus;
use App\Http\Requests\Leave\DecideLeaveRequestRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\Request;

/**
 * Leave requests. A thin controller: it reads the request, calls LeaveRequestService and returns
 * a page. Validation lives in the Form Requests, access in LeaveRequestPolicy (routes), and the
 * business rules + transaction in the service.
 */
class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveRequestService $leaveRequests,
    ) {
    }

    /**
     * My requests (Employee) or the requests to review (Manager: own department, HR: all).
     */
    public function index(Request $request, LeaveBalanceService $balances)
    {
        $status = LeaveStatus::tryFrom((string) $request->query('status'));
        $visible = LeaveRequest::visibleTo($request->user());
        $employee = $request->user()->employee;

        return view('leave.index', [
            // "My leave this year" card; null for a login without an employee record.
            'balance' => $employee ? $balances->for($employee) : null,
            'leaveRequests' => (clone $visible)
                ->with(['employee.department', 'approver'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            // One query for all tab counts: [pending => 3, approved => 5, …]
            'counts' => (clone $visible)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request, LeaveBalanceService $balances)
    {
        return view('leave.create', [
            'balance' => $balances->for($request->user()->employee),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request)
    {
        $this->leaveRequests->submit($request->user()->employee, $request->validated(), $request->user());

        return redirect()->route('leave-requests.index')->with('success', 'Your leave request was sent.');
    }

    public function show(LeaveRequest $leaveRequest, LeaveBalanceService $balances)
    {
        $leaveRequest->load(['employee.department', 'approver', 'actions.user']);

        return view('leave.show', [
            'leaveRequest' => $leaveRequest,
            // The employee's balance for the year of this leave: helps the manager decide.
            'balance' => $balances->for($leaveRequest->employee, $leaveRequest->start_date->year),
        ]);
    }

    public function approve(DecideLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->leaveRequests->approve($leaveRequest, $request->user(), $request->validated('note'));

        return redirect()->route('leave-requests.show', $leaveRequest)->with('success', 'Leave request approved.');
    }

    public function reject(DecideLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->leaveRequests->reject($leaveRequest, $request->user(), $request->validated('note'));

        return redirect()->route('leave-requests.show', $leaveRequest)->with('success', 'Leave request rejected.');
    }
}
