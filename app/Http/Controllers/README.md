# Controllers — organized by area

Each folder is one **area of the app**, named after who uses it. A feature's controller, views and
tests live in folders with the **same area name**, so you can find everything for a feature quickly.

| Area | Used by | URL prefix | Controllers | Views | Tests |
|---|---|---|---|---|---|
| **Auth** | Visitors who are not logged in | `/login` | `Auth/LoginController` | `resources/views/auth/` | `tests/Feature/Auth/` |
| **Account** | Every logged-in user | `/{role}/account`, `/{role}/notifications` | `Account/AccountController` (change own password), `Account/NotificationController` (the bell: list, open, mark all read) | `resources/views/account/` | `tests/Feature/Account/` |
| **Staff** | Admin, HR, Manager | `/{role}/employees` | `Staff/EmployeeController` (Manage Employees: list, filters, CRUD, trash) | `resources/views/staff/employees/` | `tests/Feature/Staff/` |
| **Admin** | Admin and HR (the management panel) | `/admin` for Admin, `/hr` for HR | `Admin/DashboardController`, `Admin/UserController` (Manage Users), `Admin/DepartmentController` (Manage Departments) | `resources/views/admin/` | `tests/Feature/Admin/` |
| **Leave** | Every role: Employees request, Managers (own department) and HR approve/reject | `/{role}/leave-requests` | `Leave/LeaveRequestController` (thin: calls `LeaveRequestService`) | `resources/views/leave/` | `tests/Feature/Leave/` |
| **Employee** | Employee role (self-service) | `/{role}/dashboard`, `/{role}/my-profile` | `Employee/DashboardController`, `Employee/ProfileController` | `resources/views/employee/` | `tests/Feature/Employee/` |

`Controller.php` (in this folder) is Laravel's base class that every controller extends.

## Who may open what

Access is **not** checked inside the controllers; the routes do it with middleware
(see `routes/web.php`):

- `auth` — must be logged in.
- `can:<permission>` — must have the permission (roles and permissions: `config/permissions.php`).
- `active` — the account must not be suspended (`app/Http/Middleware/CheckEmployeeStatus.php`).
- `SetPanelPrefix` — Admins use `/admin/...`, HR uses `/hr/...`; the other prefix gives 404.

## Related folders (same area names)

- **Form Requests (validation):** `app/Http/Requests/Staff/` (employees), `app/Http/Requests/Admin/` (departments).
- **Services (business logic):** `app/Services/Employee/DashboardService.php`, `app/Services/Leave/LeaveRequestService.php`,
  `app/Services/Leave/LeaveBalanceService.php` (leave earned: base 3 + 1.25 per month, see `config/leave.php`)
  (business rules + `DB::transaction`; a broken rule throws `App\Exceptions\BusinessRuleException`).
- **Enums (fixed lists of values):** `app/Enums/LeaveStatus.php`, `app/Enums/LeaveAction.php`.
- **Models** are shared by all areas: `app/Models/`.
- **Shared views:** `resources/views/layouts/` (page frame, sidebar, top bar) and `resources/views/partials/`
  (avatar, table styles).

## Adding a new feature

1. Decide the area (who uses it?) — or create a new area folder if none fits.
2. Controller in `app/Http/Controllers/<Area>/`, namespace `App\Http\Controllers\<Area>`.
3. Views in `resources/views/<area>/`, Form Request in `app/Http/Requests/<Area>/`.
4. Route in `routes/web.php` with the right middleware (`can:...`, `active`, ...).
5. Tests in `tests/Feature/<Area>/`, namespace `Tests\Feature\<Area>`.
