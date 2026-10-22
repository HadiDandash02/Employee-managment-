# Week 3 — Quiz & Debugging Interview Answers

Short answers, with examples from this project where possible.

---

## Laravel

### 1. Laravel's request lifecycle

1. `public/index.php` receives every request.
2. It loads Composer's autoloader and creates the app (`bootstrap/app.php`).
3. The **HTTP kernel** runs the **global middleware** (trim strings, maintenance mode, …).
4. The **router** finds the matching route in `routes/web.php`.
5. The route's **middleware** runs: `auth`, `SetPanelPrefix`, `active`, `can:…`.
6. **Route model binding** loads the models, and the **Form Request** validates the input.
7. The **controller** method runs and calls services and models.
8. It returns a **response** (a view, JSON or a redirect).
9. The response goes back out through the middleware to the browser.

### 2. What is middleware?

Middleware is code that runs **before** (or after) a controller, for many routes at once. It's like a guard at the door.

- `auth`: the user must be logged in.
- `active` ([CheckEmployeeStatus.php](../app/Http/Middleware/CheckEmployeeStatus.php)): a suspended account gets 403.

Middleware is registered in `bootstrap/app.php`. Laravel 11/12 has no `Kernel.php`.

### 3. Authentication vs authorization

- **Authentication** asks *who are you?* It is the login (`LoginController`, the `auth` middleware). If you fail it, you're redirected to `/login`.
- **Authorization** asks *what may you do?* That is Gates and Policies (`can:…`, `->can('approve', 'leaveRequest')`). If you fail it, you get **403**.

### 4. Gate vs Policy

| Gate | Policy |
|---|---|
| A general ability: "may this user do X **at all**?" | An ability on **one record**: "may this user do X **to this leave request**?" |
| `Gate::define('employees.create', …)`, built from `config/permissions.php` in `AppServiceProvider` | `LeaveRequestPolicy::approve($user, $leaveRequest)`, found automatically for the model |

Example: `leave.approve` is a Gate (is this person an approver?). `LeaveRequestPolicy::approve` is a Policy (may they approve **this** request, i.e. is it in their department?).

### 5. What is dependency injection?

A class **receives** what it needs instead of creating it with `new`.

```php
public function __construct(private LeaveRequestService $leaveRequests) {}
```

The controller never writes `new LeaveRequestService(...)`. That makes it easy to swap or mock in tests, and the class lists what it depends on.

### 6. What does the service container do?

It's Laravel's **factory**. It creates objects and fills in their constructor parameters automatically.
`LeaveRequestService` needs `LeaveDayCounter` and `LeaveBalanceService`. The container builds those first, then the service, then the controller.

### 7. Why use Form Requests?

- Validation moves out of the controller (`StoreLeaveRequestRequest`).
- Messages and field names live in one place.
- It runs **before** the controller, so the controller only ever sees valid data.
- `$request->validated()` returns only the allowed fields.

### 8. What is route-model binding?

`/leave-requests/{leaveRequest}` together with `show(LeaveRequest $leaveRequest)`: Laravel loads the row with that id automatically. If the row doesn't exist, it returns 404. `->withTrashed()` also finds soft-deleted rows.

### 9. What is mass assignment?

Filling many columns at once from an array: `LeaveRequest::create($data)`.
The danger: a hacker adds `status=approved` or `approved_by=1` to the form, and it gets saved.

### 10. What does `$fillable` do?

It is the **allow-list** of columns that mass assignment may fill. `LeaveRequest` allows the dates, periods, days and reason, but **not** `status` or `approved_by`. Only the service sets those. A test posts `status=approved` and checks that the request is still pending.

---

## Database

### 11. What is a transaction?

A group of queries that succeed or fail **together** (atomic).

```php
DB::transaction(function () { /* update leave_requests + insert leave_request_actions */ });
```

### 12. When should you use one?

When **one business action writes more than once**: approving a leave, creating an employee with its profile and skills, bulk deletes. If only half of the writes happen, the data becomes wrong.

### 13. What is rollback?

Undoing every write of the transaction when something fails. Laravel does it automatically when an exception leaves `DB::transaction()`.
In this project, a test makes the history insert fail and checks that the status change was undone too (`test_everything_is_rolled_back_when_the_history_row_fails`).

### 14. What is N+1?

You run **1** query for a list, then **N** more queries, one per row, for a relationship.
Example: 15 leave requests, and each row reads `$leave->employee->department`, which adds 30 extra queries.

### 15. How does eager loading solve it?

`with()` loads the relationship for **all** rows in one extra query:

```php
LeaveRequest::with(['employee.department', 'approver'])->paginate(15); // 4 queries, not 46
```

For counts, use `withCount('employees')` (Manage Departments).

---

## Security

### 16. What is CSRF?

Cross-Site Request Forgery: another website makes your browser send a request (like "approve") while you're logged in.
Laravel puts a secret token in every form (`@csrf`) and refuses any POST without it (error 419).

### 17. What is XSS?

Cross-Site Scripting: someone stores `<script>` (for example in a leave reason), and it runs in other users' browsers.
Blade's `{{ }}` escapes it. Never use `{!! !!}` with user data. In JavaScript, the project escapes with `esc()` before building HTML.

### 18. What is SQL injection?

User input changes the SQL itself, as in `"WHERE name = '$name'"` with `name = ' OR 1=1 --`.
Eloquent and the query builder use **bound parameters** (`where('name', $name)`), so input is always data, never SQL. The search also escapes `%` and `_` with `addcslashes`.

### 19. Why shouldn't `.env` be committed?

It holds **secrets**: `APP_KEY`, database passwords, mail and API keys. They also differ per machine.
Git history is permanent and gets shared, so a leaked key has to be changed everywhere. Commit `.env.example` (no secrets) instead.

### 20. Why isn't hiding a button authorization?

Anyone can type the URL, send the form with DevTools, or use `curl`.
Hiding is only for UX. The **server** must check every request: `->can('approve', 'leaveRequest')` returns **403**, and the tests post directly to the URLs.

---

## Architecture

### 21. What belongs in a controller?

Glue only:
1. take the request,
2. call the service or model,
3. return a view, JSON or a redirect, with a flash message.

`LeaveRequestController` is about 100 lines and each method is a few lines.

### 22. What belongs in a service?

**Business logic**: rules, calculations, and operations that write several tables in a transaction. For example:
- `LeaveRequestService`: submit, approve, reject, cancel, notifications;
- `LeaveBalanceService`: days earned and remaining;
- `LeaveDayCounter`: counting working days.

### 23. When would you *not* create a service?

For **simple CRUD** with no rules. Manage Departments only does `Department::create($request->validated())`. A service there would only add a file that passes data along. Architecture is a tool, not a ritual.

### 24. Where should business rules live?

In **one place**, usually the service, so that every entry point uses them: a web form, an API, an artisan command, a queue job.
"Cannot approve your own request" lives in `LeaveRequestService::decide()`, not in the Blade view.

### 25. Why is duplicated business logic dangerous?

The copies drift apart. Someone fixes one and forgets the other, and the app behaves differently depending on which screen you use.
Example: if "remaining days" were calculated separately in the controller, the view and the service, one change to the 1.25-per-month rule would need three edits. Today it's only `LeaveBalanceService`.
(The day count in the form's JavaScript is a **preview** only. The server always counts again.)

---

## Debugging interview

### Scenario 1: "Approving a leave request gives a 500 error"

1. **Reproduce** it: same user, same request, same steps.
2. **Read the error**: with `APP_DEBUG=true` locally Laravel shows the page; otherwise open `storage/logs/laravel.log` (newest at the bottom).
3. **Find the failing line in my code** (`app/...`), not in `vendor/`.
4. **Inspect the data** involved, e.g. in `php artisan tinker`. Is `approved_by` null? Is the employee deleted? Is the department missing?
5. **Form a hypothesis**, for example "`auth()->id()` is null because the route has no `auth` middleware". Check it with `dd()` or `route:list`.
6. **Fix** it, ideally with a test that failed before.
7. **Retest**: the scenario, plus `php artisan test`.

Not: "I'll Google the error" before reading the log.

### Scenario 2: Changing `/employees/10` to `/employees/11` shows another employee

This is an **IDOR** (Insecure Direct Object Reference), meaning **authorization is missing on the backend**. The id comes from the user, so it can't be trusted.

Fix:
- Check a **Policy** on the record: `->can('view', 'employee')` on the route, or `Gate::authorize('view', $employee)` in the controller.
- Lists must also be filtered: `Employee::visibleTo($user)`, `LeaveRequest::visibleTo($user)`.
- Add a test that types the other id and expects **403** (`EmployeeAuthorizationTest`).

### Scenario 3: The employee is created, but creating their contract fails

Both writes must succeed together or not at all. Wrap them in `DB::transaction()`. When the contract fails, the exception causes a **rollback** and the employee row disappears too. Show the user an error and let them try again.
Without a transaction you get an employee without a contract: half-saved, inconsistent data.
Also send emails and notifications only **after** the commit, as `LeaveRequestService` does.
