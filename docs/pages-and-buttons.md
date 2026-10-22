# Every page and every button

This guide covers each screen of the Employee Management System:

- **what** each button does;
- **which route** it calls (HTTP method, URL, route name, controller method);
- **who** may use it, and what protects it;
- **why** it was built that way.

Every logged-in address starts with the user's **role**: `/admin/…`, `/hr/…`, `/manager/…` or `/employee/…`. In this guide that part is written `/{role}/`. For example, Leave Requests is `/hr/leave-requests` for HR and `/employee/leave-requests` for an employee.

The full route list is at the end. You can always check it yourself with `php artisan route:list --except-vendor`.

---

## How to read the tables

| Column | Meaning |
|---|---|
| **Button** | The text or icon you click. |
| **What happens** | What the user sees, and what changes in the database. |
| **Route** | `METHOD /url` → `route.name` → `Controller@method`. |
| **Protected by** | Checks that run **before** the controller (middleware, Gate or Policy). If one fails you get **403**, or **404** for a wrong panel prefix. |

### Two kinds of buttons

1. **Normal links and forms.** The browser loads a new page (`GET`), or sends a form (`POST`/`PUT`/`DELETE`). Afterwards the server **redirects** back with a green flash message (`->with('success', …)`).
   - HTML forms can only send GET and POST, so `@method('PUT')` and `@method('DELETE')` add a hidden `_method` field.
   - `@csrf` adds the security token. Without it the server answers **419**.
2. **AJAX buttons** (the employee and user tables). JavaScript calls the route with `fetch()`, sending headers `Accept: application/json` and `X-CSRF-TOKEN`. The controller answers with **JSON** (`{"message": "…"}`), and the table reloads without leaving the page.

### Guards on every logged-in page

All logged-in routes share `auth` + `auth.session` + `SetPanelPrefix` (see the top of `routes/web.php`):

- `auth`: you must be logged in. If not, you're redirected to `/login`.
- `auth.session`: when you change your password, your other browsers are logged out.
- `SetPanelPrefix`: the address starts with your role (`/admin`, `/hr`, `/manager`, `/employee`) and `route()` fills it in for you. Another role's prefix gives **404**. Old addresses without a prefix (`/employees/5`) redirect to your own (`/hr/employees/5`).
- `active` (on the employee and leave pages): a suspended account gets **403**.

### Who has which permission

From `config/permissions.php`:

| | Admin | HR | Manager | Employee |
|---|:-:|:-:|:-:|:-:|
| Open Manage Employees | ✓ | ✓ | ✓ (own department only) | |
| Create / edit / delete employees | ✓ | ✓ | | |
| Manage users, manage departments | ✓ | ✓ | | |
| Own profile, edit own phone and address | ✓ | ✓ | ✓ | ✓ |
| Request leave | ✓ | ✓ | ✓ | ✓ |
| Approve or reject leave | all | all | own department | |

---

## 1. The frame around every page (`layouts/app.blade.php`)

### Sidebar (left)

The menu only shows links the user may open, using `@can`. Hiding them is only for convenience: every route checks again on the server.

| Button | What happens | Route | Shown when |
|---|---|---|---|
| **App name** (top) | Goes to your start page. | `GET /` → `home` → closure: `redirect($user->homeUrl())` | always |
| **Dashboard** | Admin/HR: the management dashboard. Manager/Employee: the personal dashboard. | `GET /{role}/dashboard` → `dashboard` → `Account\DashboardController` (hands over to `Admin\…` or `Employee\DashboardController`) | always |
| **My Profile** | Your own employee record. | `GET /{role}/my-profile` → `profile.show` → `Employee\ProfileController@show` | same |
| **Leave Requests** + yellow number | Leave list. The number counts pending requests **you** can review (your own are not counted). | `GET /{role}/leave-requests` → `leave-requests.index` | `@can('viewAny', LeaveRequest::class)` |
| **Manage Employees** | Employee table. | `GET /{role}/employees` → `employees.index` | `@can('employees.view')` |
| **Manage Departments** | Department list. | `GET /{role}/departments` → `admin.departments.index` | `@can('departments.manage')` |
| **Manage Users** | User table. | `GET /{role}/users` → `admin.users.index` | `@can('users.manage')` |

### Top bar

| Button | What happens | Route |
|---|---|---|
| **☰** (menu) | Hides or shows the sidebar. Pure JavaScript, remembered in `localStorage`. No server call. | — |
| **🔔 Bell** + red number | Opens the 5 latest notifications. The number counts unread ones. | data loaded with the page (`$me->notifications()`) |
| ↳ a **notification** | Marks it read and opens what it's about, e.g. the leave request. | `GET /{role}/notifications/{id}` → `notifications.open` → `Account\NotificationController@open` |
| ↳ **Mark all as read** | Every unread notification becomes read. | `POST /{role}/notifications/read-all` → `notifications.read-all` → `@readAll` |
| ↳ **View all notifications** | The full list, 15 per page. | `GET /{role}/notifications` → `notifications.index` → `@index` |
| **Profile chip** (initials, name, role) | Opens the profile menu. | — |
| ↳ **Add User** (Admin/HR) | New user form. | `GET /{role}/users/create` → `admin.users.create` |
| ↳ **My account & password** | Opens a small submenu: *My account*, *Change password*. | `GET /{role}/account` → `account.edit` (`#change-password` scrolls to the form) |
| ↳ **Log out** | Ends the session. | `POST /logout` → `logout` → `Auth\LoginController@destroy` |

**Why:**
- **Log out is a POST form with `@csrf`, not a link.** Otherwise another website could log you out with an image tag.
- **Notifications are always loaded through `$request->user()->notifications()`.** So typing someone else's notification id gives **404**.

---

## 2. Login: `/login` (`auth/login.blade.php`)

| Button | What happens | Route |
|---|---|---|
| **Username or email** + **Password** | Your login details. | — |
| **👁 eye** | Shows or hides the password. JavaScript only. | — |
| **Remember me** | Keeps you logged in after closing the browser (remember cookie). | sent with the form |
| **Log in** | Checks the details. If they're right, it starts a new session and sends you to your start page. If they're wrong, you see a red message. | `POST /login` → `login.store` → `Auth\LoginController@store` |

**Protected by** `guest`: logged-in users are redirected away from this page.

**Why:**
- **After 5 wrong tries (`MAX_ATTEMPTS = 5`), the login is locked for 60 seconds** (`RateLimiter`). This stops password guessing.
- **`session()->regenerate()` gives you a new session id after login.** This prevents session fixation.
- **"Start page" depends on the role** (`User::homeUrl()`): the dashboard for Admin/HR, Manage Employees for a Manager, the employee dashboard for an Employee.

---

## 3. Admin / HR Dashboard: `/admin/dashboard` or `/hr/dashboard`

`GET /{role}/dashboard` → `dashboard` → `Account\DashboardController`, which shows this page for users with `users.manage` (`Admin\DashboardController`).

| Button | What happens | Route |
|---|---|---|
| **Add User** | New user form. | `GET /{role}/users/create` → `admin.users.create` |
| **Manage users (n)** | User table. | `GET /{role}/users` → `admin.users.index` |
| **⋮ → Edit** (users table) | Edit that user. | `GET /{role}/users/{user}/edit` → `admin.users.edit` |
| **⋮ → Reset password** | Same page, scrolled to the password fields. | `admin.users.edit` + `#set-password` |
| **⋮ → Delete** | Asks "Delete user …?", then deletes. You can't delete yourself, so the button is hidden on your own row. | `DELETE /{role}/users/{user}` → `admin.users.destroy` |
| **Department name** (Departments panel) | The employee list, filtered to that department. | `GET /{role}/employees?filters[department_id]=…` → `employees.index` |
| **An employee** (Recently added) | That employee's page. | `GET /{role}/employees/{employee}` → `employees.show` |
| **View all** | Employee list. | `employees.index` |

The **Roles & permissions** table only displays information. It reads `config/permissions.php`, so the table can never disagree with the real rules.

**Why:** HR sees `/hr/...` and never the word "Admin". HR also never sees Admin accounts (`manages` in the config). Opening an admin's edit URL as HR gives **404**.

---

## 4. Manage Employees: `/{role}/employees` (`staff/employees/index.blade.php`)

The page itself (`GET /{role}/employees` → `employees.index` → `EmployeeController@index`) only draws the filters and an empty table. **DataTables** then fetches the rows:

> `GET /{role}/employees/data?draw=…&start=…&length=…&search[value]=…&order[0][column]=…&filters[…]=…&status=active|trash`
> → `employees.data` → `EmployeeController@data` → JSON `{ data: [...], recordsTotal, recordsFiltered, trashTotal }`

This is called **server-side processing**: search, sort and paging run as SQL on the server, so the page stays fast with thousands of employees.

**Protected by** `can:viewAny,Employee` (Admin, HR, Manager). Inside `data()`:
- `Employee::visibleTo($user)` gives HR everyone, and a Manager **only their own department**;
- `?status=trash` gives **403** unless you have `employees.delete`.

### Top of the page

| Button | What happens | Route | Who |
|---|---|---|---|
| **Add Employee** | New employee form. | `GET /{role}/employees/create` → `employees.create` | `can:create,Employee` (Admin, HR) |

### Filters panel

| Button | What happens | Route |
|---|---|---|
| Department / Branch / Salary from–to / Born from–to / Added from–to | The filter values. | — |
| **Generate Report** | Reloads the table with the filters (`filters[…]` in the AJAX call). The **Filters** button shows how many filters are active. | `employees.data` |
| **Clear** | Empties the filters and reloads. | `employees.data` |
| **Collapse / Filters** (funnel) | Hides or shows the filter panel. No server call. | — |

### Table toolbar (DataTables)

| Button | What happens | Route |
|---|---|---|
| **Search table** | Searches name, email and department, word by word ("jane sales"). Waits 300 ms after you stop typing. | `employees.data` (`search[value]`) |
| **10 / 25 / 50 / 100 / All** | Rows per page. "All" (`-1`) is for exporting everything. | `employees.data` (`length`) |
| **Column headers** | Click to sort ascending or descending, done in SQL (`Employee::scopeSortBy`). | `employees.data` (`order`) |
| **Columns** | Choose which columns to show. Remembered in the browser (`stateSave`). | — |
| **Export → PDF / Excel / CSV / Print** | Downloads or prints the **rows currently loaded** (choose "All" first to export everything). Done in the browser with DataTables Buttons. | — |
| **Active / Trash** (Admin/HR) | Switches between normal employees and **soft-deleted** ones. The number shows how many are in the trash. | `employees.data` with `status=trash` |
| **Empty trash** (Trash view) | Asks first, then **permanently** deletes every trashed employee on every page. | `DELETE /{role}/employees/trash/all` → `employees.trash.empty` → `@emptyTrash` |

### Selecting rows (Admin/HR only: the checkbox column needs `employees.delete`)

| Button | What happens | Route |
|---|---|---|
| **☐ header box** | Selects or unselects every row on this page. | — |
| **☐ row box** | Selects one row. A bar appears: "**n** selected". | — |
| **Delete selected** (Active view) | Asks, then moves the selected employees to the trash. | `DELETE /{role}/employees` (JSON `{ids:[…]}`) → `employees.bulk-destroy` → `@bulkDestroy` |
| **Restore selected** (Trash view) | Brings them back. | `POST /{role}/employees/trash/restore` → `employees.trash.restore` → `@bulkRestore` |
| **Delete forever** (Trash view) | Asks, then deletes them permanently. The profile and skills go too, through the database cascade. | `DELETE /{role}/employees/trash` → `employees.trash.force` → `@bulkForceDelete` |
| **Clear selection** | Unticks everything. | — |

### Each row

| Button | What happens | Route | Protected by |
|---|---|---|---|
| **Employee name** | That employee's page. | `GET /{role}/employees/{employee}` → `employees.show` | `can:viewAny` + `can:view,employee` |
| **🔑 @username** under the name | The login linked to this employee. It's a link only if you may manage that user. | `GET /{role}/users/{user}/edit` → `admin.users.edit` | `can:users.manage` + `manage-user` |
| **⋮ → View** | Employee page. | `employees.show` | as above |
| **⋮ → Edit** | Edit form. Shown only if `row.can.update` is true (EmployeePolicy, per row). | `GET /{role}/employees/{employee}/edit` → `employees.edit` | `can:update,employee` |
| **⋮ → Delete** | Asks, then moves to the trash (soft delete). | `DELETE /{role}/employees/{employee}` → `employees.destroy` → `@destroy` | `can:delete,employee` |
| **⋮ → Restore** (Trash) | Brings back one employee. | `POST /{role}/employees/{employee}/restore` → `employees.restore` (`->withTrashed()`) | `can:restore,employee` |
| **⋮ → Delete forever** (Trash) | Permanent delete. | `DELETE /{role}/employees/{employee}/force` → `employees.force-delete` | `can:forceDelete,employee` |

**Why:**
- **Soft delete first, then the trash.** Mistakes can be undone. "Delete forever" only works on rows that are already in the trash (`abort_unless($employee->trashed(), 404)`).
- **The bulk and trash routes are declared before `/{role}/employees/{employee}`.** Otherwise Laravel would read the word "trash" as an employee id.
- **Each row carries its own `can` flags.** The ⋮ menu follows the Policy for that exact employee, and the server still checks every click.

---

## 5. Employee details: `/{role}/employees/{id}` (`staff/employees/show.blade.php`)

`GET /{role}/employees/{employee}` → `employees.show` → `EmployeeController@show`. **Protected by** `can:viewAny,Employee` **and** `can:view,employee`, so a Manager typing another department's id gets **403**.

| Button | What happens | Route | Shown when |
|---|---|---|---|
| **Edit** | Edit form. | `GET /{role}/employees/{employee}/edit` → `employees.edit` | `@can('update', $employee)` |
| **Delete** | Asks, then moves to the trash and returns to the list. | `DELETE /{role}/employees/{employee}` → `employees.destroy` | `@can('delete', $employee)` |
| **Back** | Employee list. | `GET /{role}/employees` → `employees.index` | always |
| **Email address** | Opens your mail app (`mailto:`). | — | |
| **📋 copy** | Copies the email (`navigator.clipboard`). No server call. | — | |
| **Department badge** | Employee list filtered to this department. | `employees.index?filters[department_id]=…` | |
| **Login account → @username** | Edit the linked user. | `admin.users.edit` | `@can('manage-user', $employee->user)` |
| **A colleague** | That colleague's page. | `employees.show` | |
| **View all colleagues** | The list, filtered to the department. | `employees.index?filters[department_id]=…` | |

**Why:** the salary comparison and the colleague list are calculated in the controller (`avg`, `count`, `limit(5)`), not in Blade, which only displays.

---

## 6. Add / Edit employee: `/{role}/employees/create`, `/{role}/employees/{id}/edit`

Both pages use the same form, `staff/employees/_form.blade.php`.

| Button | What happens | Route | Protected by |
|---|---|---|---|
| Fields: first name, last name, email, date of birth, department, salary, **base leave days** | The `employees` row. | — | |
| Phone, hire date, address | The `employee_profiles` row (hasOne). | — | |
| **Skill pills** | Toggle skills (`employee_skill` pivot, belongsToMany). | — | |
| **Create employee** (create page) | Validates, then saves employee, profile and skills **in one transaction**. | `POST /{role}/employees` → `employees.store` → `@store` (`StoreEmployeeRequest`) | `can:create,Employee` |
| **Save changes** (edit page) | Validates, then updates all three in one transaction. | `PUT /{role}/employees/{employee}` → `employees.update` → `@update` (`UpdateEmployeeRequest`) | `can:update,employee` |
| **Cancel** | Back without saving. | `employees.index` or `employees.show` | |
| **View profile** (edit page) | Employee page. | `employees.show` | |
| **Move to trash** (Danger zone, edit page) | Asks, then soft-deletes. | `DELETE /{role}/employees/{employee}` → `employees.destroy` | `@can('delete', $employee)` |

**Why:**
- **Validation lives in Form Requests.** Wrong input sends you back with red messages under each field, and `old()` keeps what you typed.
- **The transaction** means an employee is never saved without its profile and skills.
- **Typing `/{role}/employees/25/edit` as a Manager or Employee gives 403.** This is EmployeePolicy::update, on the route.

---

## 7. Manage Departments: `/admin/departments` or `/hr/departments`

`GET /{role}/departments` → `admin.departments.index` → `Admin\DepartmentController@index`. **Protected by** `can:departments.manage` (Admin, HR). It's a normal Bootstrap table with server pagination, 15 per page.

| Button | What happens | Route |
|---|---|---|
| **Add Department** | New department form. | `GET /{role}/departments/create` → `admin.departments.create` |
| **Search** (🔍) | Searches name or code; the URL becomes `?search=…`. | `GET /{role}/departments?search=…` → `admin.departments.index` |
| **Clear** | Removes the search. | `admin.departments.index` |
| **Employees number** (badge) | Employee list filtered to this department. | `employees.index?filters[department_id]=…` |
| **⋮ → Edit** | Edit form. | `GET /{role}/departments/{department}/edit` → `admin.departments.edit` |
| **⋮ → Delete** | Asks, then soft-deletes. **Refused** while the department still has employees ("… still has n employees"). | `DELETE /{role}/departments/{department}` → `admin.departments.destroy` |
| **Create department / Save changes** (form) | Name, code (unique, uppercased), branch, description. | `POST /{role}/departments` → `admin.departments.store`; `PUT /{role}/departments/{department}` → `admin.departments.update` |
| **Cancel** (form) | Back to the list. | `admin.departments.index` |

**Why:** the list uses `with('branch.company')->withCount('employees')`. That avoids the **N+1** problem: one query for the counts instead of one per row.

---

## 8. Manage Users: `/admin/users` or `/hr/users`

Works like Manage Employees. The page is `GET /{role}/users` → `admin.users.index`, and the rows come from AJAX:

> `GET /{role}/users/data` → `admin.users.data` → `Admin\UserController@data` (JSON)

**Protected by** `can:users.manage`. HR never receives Admin accounts.

| Button | What happens | Route |
|---|---|---|
| **Add User** | New user form. | `GET /{role}/users/create` → `admin.users.create` |
| Name / Select role + **Generate Report** / **Clear** / **Collapse** | Filters the table. | `admin.users.data` |
| **Search, sort, rows per page** | Same as the employee table. | `admin.users.data` |
| **🔑 Set password** (Password column) | Edit page, scrolled to the password fields. | `admin.users.edit` + `#set-password` |
| **Employee link** under a name | The linked employee's page. | `employees.show` |
| **⋮ → Edit** | Edit form. | `GET /{role}/users/{user}/edit` → `admin.users.edit` |
| **⋮ → Reset password** | Same as Set password. | `admin.users.edit#set-password` |
| **⋮ → Delete** | Asks, then deletes (AJAX). Refused for your own account and for the last admin. | `DELETE /{role}/users/{user}` → `admin.users.destroy` |

### User form (create and edit)

| Button | What happens | Route |
|---|---|---|
| Name, username, email, **role** | The roles you may give come from `manages` (HR cannot give "Admin"). | — |
| **Linked employee** | Which employee this login belongs to. Required for the Employee role, and only one login per employee. | — |
| **Active** switch | Off = suspended. The `active` middleware then blocks the employee and leave pages (403). | — |
| Password + confirm, **👁** | Required when creating. Leave empty when editing to keep the current one. | — |
| **Create user / Save changes** | Validates and saves. | `POST /{role}/users` → `admin.users.store`; `PUT /{role}/users/{user}` → `admin.users.update` |
| **Cancel** | Back to the list. | `admin.users.index` |

---

## 9. Personal dashboard (Manager, Employee): `/manager/dashboard`, `/employee/dashboard`

`GET /{role}/dashboard` → `dashboard` → `Account\DashboardController` → `Employee\DashboardController`. It uses `DashboardService`, injected through the constructor.
**Protected by** `can:profile.view-own` + `active`.

| Button | What happens | Route |
|---|---|---|
| **My profile** (Quick links) | Your profile. | `GET /{role}/my-profile` → `profile.show` |
| **Change password** (Quick links) | Password form. | `GET /{role}/account#change-password` → `account.edit` |

The cards (years of service, days to your birthday, department size, skills) come from `DashboardService::summaryFor()`.

---

## 10. My Profile: `/{role}/my-profile`, and Edit contact info: `/{role}/my-profile/edit`

`Employee\ProfileController`. **Protected by** `can:profile.view-own` + `active`. It always uses `$request->user()->employee`, **never an id from the URL**, so there's nothing to change in the URL to see someone else.

| Button | What happens | Route | Protected by |
|---|---|---|---|
| **Edit contact info** | Form with **phone and address only**. | `GET /{role}/my-profile/edit` → `profile.edit` → `@edit` | `Gate::authorize('updateContact', $employee)` |
| **Change password** | Password form. | `account.edit#change-password` | |
| **Save** (contact form) | Saves phone and address. Extra fields sent by a hacker (salary, …) are ignored, because only `validated()` is saved. | `PUT /{role}/my-profile` → `profile.update` → `@update` (`UpdateOwnContactRequest`) | same Gate |
| **Cancel** / breadcrumb | Back to the profile. | `profile.show` | |

---

## 11. My account: `/{role}/account`

| Button | What happens | Route |
|---|---|---|
| **Change password** | Needs the current password, a new one (at least 8 characters, different from the old one) and its confirmation. Afterwards your **other browsers are logged out** (`Auth::logoutOtherDevices`). | `PUT /{role}/account/password` → `account.password` → `Account\AccountController@updatePassword` |

Errors use a named error bag (`$errors->password`), so they appear under the right fields.

---

## 12. Leave Requests list: `/{role}/leave-requests` (`leave/index.blade.php`)

`GET /{role}/leave-requests` → `leave-requests.index` → `Leave\LeaveRequestController@index`.
**Protected by** `active` + `can:viewAny,LeaveRequest`. Rows come from `LeaveRequest::visibleTo($user)`:
- **HR/Admin** see all requests;
- a **Manager** sees their department's plus their own;
- an **Employee** sees only their own.

| Button | What happens | Route |
|---|---|---|
| **Request leave** | The request form. Shown only if your login is linked to an employee. | `GET /{role}/leave-requests/create` → `leave-requests.create` (`can:create`) |
| **My leave in 2026** (card) | Remaining, taken and waiting days. Information only (`LeaveBalanceService`). | — |
| **All / Pending / Approved / Rejected / Cancelled** tabs (+ counts) | Shows only that status. The search is kept. | `GET /{role}/leave-requests?status=pending&search=…` |
| **Search** (🔍) | Searches the employee name or the reason. The status tab is kept. | `GET /{role}/leave-requests?search=…&status=…` |
| **Clear** | Removes the search. | `leave-requests.index` |
| **Review** (a pending request you may decide) / **View** | The request page. | `GET /{role}/leave-requests/{leaveRequest}` → `leave-requests.show` (`can:view`) |
| **Page numbers** | 15 per page. `withQueryString()` keeps the status and search. | `?page=2&…` |

---

## 13. Request leave: `/{role}/leave-requests/create` (`leave/create.blade.php`)

`GET` → `leave-requests.create` → `@create`. **Protected by** `active` + `can:create,LeaveRequest` (`leave.request` and a linked employee).

| Button | What happens | Route |
|---|---|---|
| **One full day / Morning only / Afternoon only** | Fills in one day and the right Morning/Afternoon. JavaScript only. | — |
| **From**: date + **Morning / Afternoon** | When the leave starts (Afternoon = from midday). | — |
| **To**: date + **Morning / Afternoon** | When it ends (Morning = back at midday). | — |
| **Your request** box (right) | A **live preview**: working days (weekends skipped), "Left after this request", and a green, yellow or red message. It's JavaScript, and the server counts again. | — |
| **Reason** | Required, up to 1000 characters (counter shown). | — |
| **Send request** | Disabled in the browser when there are 0 days or not enough leave. On the server: `StoreLeaveRequestRequest` validates, then `LeaveRequestService::submit()` checks the business rules, saves in a transaction, and **after the commit** notifies the approvers. | `POST /{role}/leave-requests` → `leave-requests.store` → `@store` |
| **Cancel** / **My requests** | Back to the list. | `leave-requests.index` |

**Business rules in the service** (a broken rule throws `BusinessRuleException`, and you come back with a red message):
- end not before start;
- one day cannot run afternoon → morning;
- not only weekend days;
- inside one year;
- no overlap with a pending or approved request (Monday morning and Monday afternoon don't overlap);
- not more than the days left. You earn 3 base days + 1.25 per month, and pending days are held back.

---

## 14. One leave request: `/{role}/leave-requests/{id}` (`leave/show.blade.php`)

`GET` → `leave-requests.show` → `@show`. **Protected by** `can:view,leaveRequest`: the owner, HR, or the Manager of that department. Anyone else gets **403**.

| Button | What happens | Route | Who |
|---|---|---|---|
| **Comment** box | Optional when approving, **required** when rejecting. | — | approver |
| **Approve** | Asks, then status = approved, `approved_by` = you, `approved_at` = now, plus a history row, **in one transaction with a row lock**. Then the employee is notified. | `POST /{role}/leave-requests/{id}/approve` → `leave-requests.approve` → `@approve` | `can:approve,leaveRequest` |
| **Reject** | Same form, but `formaction` sends it to the reject route. | `POST /{role}/leave-requests/{id}/reject` → `leave-requests.reject` → `@reject` | `can:approve,leaveRequest` |
| **Cancel request** | Only your own **pending** request. Asks, then status = cancelled, and the days are free again. | `POST /{role}/leave-requests/{id}/cancel` → `leave-requests.cancel` → `@cancel` | `can:cancel,leaveRequest`: **only the owner, not even HR** |
| **History** panel | Every step (submitted, approved, rejected, cancelled), with who, when and the comment. Rows in `leave_request_actions`. | — | |
| **Balance** card | That employee's earned, taken and remaining days for the leave's year, to help the approver decide. | — | |

**Rules checked in `LeaveRequestService`**, even if someone posts to the URL directly:
- you cannot decide your **own** request;
- only a **pending** request can be decided. A second click, or a second manager, gets "already Approved";
- approving cannot go over the days earned;
- only a pending request can be cancelled.

**Why:**
- **The policy answers "who"** (authorization, 403).
- **The service answers "is it allowed now"** (business rules, a red message).
- **The transaction plus `lockForUpdate()`** stops two approvers from both deciding the same request at the same moment.

---

## 15. Notifications: `/{role}/notifications`

| Button | What happens | Route |
|---|---|---|
| **A notification** | Marks it read and opens the leave request. | `GET /{role}/notifications/{id}` → `notifications.open` |
| **Mark all as read** | Marks every notification read. | `POST /{role}/notifications/read-all` → `notifications.read-all` |
| **Page numbers** | 15 per page. | `?page=2` |

Who gets one:
- **New leave request**: the department's Manager, HR and Admin (active accounts, not the requester).
- **Approved or rejected**: the employee, with the comment.

---

## Appendix: every route

| Method | URL | Name | Controller | Extra protection |
|---|---|---|---|---|
| GET | `/login` | `login` | `Auth\LoginController@create` | `guest` |
| POST | `/login` | `login.store` | `Auth\LoginController@store` | `guest`, 5 tries per minute |
| POST | `/logout` | `logout` | `Auth\LoginController@destroy` | |
| GET | `/` | `home` | closure → `homeUrl()` | |
| GET | `/{role}/account` | `account.edit` | `Account\AccountController@edit` | |
| PUT | `/{role}/account/password` | `account.password` | `Account\AccountController@updatePassword` | |
| GET | `/{role}/notifications` | `notifications.index` | `Account\NotificationController@index` | |
| POST | `/{role}/notifications/read-all` | `notifications.read-all` | `Account\NotificationController@readAll` | |
| GET | `/{role}/notifications/{notification}` | `notifications.open` | `Account\NotificationController@open` | own notifications only (404) |
| GET | `/{role}/my-profile` | `profile.show` | `Employee\ProfileController@show` | `can:profile.view-own`, `active` |
| GET | `/{role}/my-profile/edit` | `profile.edit` | `Employee\ProfileController@edit` | + `Gate::authorize('updateContact')` |
| PUT | `/{role}/my-profile` | `profile.update` | `Employee\ProfileController@update` | + `Gate::authorize('updateContact')` |
| GET | `/{role}/leave-requests` | `leave-requests.index` | `Leave\LeaveRequestController@index` | `active`, `can:viewAny` |
| GET | `/{role}/leave-requests/create` | `leave-requests.create` | `@create` | `active`, `can:create` |
| POST | `/{role}/leave-requests` | `leave-requests.store` | `@store` | `active`, `can:create` |
| GET | `/{role}/leave-requests/{leaveRequest}` | `leave-requests.show` | `@show` | `active`, `can:view` |
| POST | `/{role}/leave-requests/{leaveRequest}/approve` | `leave-requests.approve` | `@approve` | `active`, `can:approve` |
| POST | `/{role}/leave-requests/{leaveRequest}/reject` | `leave-requests.reject` | `@reject` | `active`, `can:approve` |
| POST | `/{role}/leave-requests/{leaveRequest}/cancel` | `leave-requests.cancel` | `@cancel` | `active`, `can:cancel` |
| GET | `/{role}/employees` | `employees.index` | `Staff\EmployeeController@index` | `can:viewAny,Employee` |
| GET | `/{role}/employees/data` | `employees.data` | `@data` (JSON) | `can:viewAny,Employee` |
| GET | `/{role}/employees/create` | `employees.create` | `@create` | `can:create,Employee` |
| POST | `/{role}/employees` | `employees.store` | `@store` | `can:create,Employee` |
| DELETE | `/{role}/employees` | `employees.bulk-destroy` | `@bulkDestroy` | `can:employees.delete` |
| POST | `/{role}/employees/trash/restore` | `employees.trash.restore` | `@bulkRestore` | `can:employees.delete` |
| DELETE | `/{role}/employees/trash/all` | `employees.trash.empty` | `@emptyTrash` | `can:employees.delete` |
| DELETE | `/{role}/employees/trash` | `employees.trash.force` | `@bulkForceDelete` | `can:employees.delete` |
| GET | `/{role}/employees/{employee}` | `employees.show` | `@show` | `can:viewAny` + `can:view,employee` |
| GET | `/{role}/employees/{employee}/edit` | `employees.edit` | `@edit` | `can:update,employee` |
| PUT | `/{role}/employees/{employee}` | `employees.update` | `@update` | `can:update,employee` |
| DELETE | `/{role}/employees/{employee}` | `employees.destroy` | `@destroy` | `can:delete,employee` |
| POST | `/{role}/employees/{employee}/restore` | `employees.restore` | `@restore` | `can:restore,employee`, `withTrashed` |
| DELETE | `/{role}/employees/{employee}/force` | `employees.force-delete` | `@forceDelete` | `can:forceDelete,employee`, `withTrashed` |
| GET | `/{role}` | `panel.home` | redirect → start page | |
| GET | `/{role}/dashboard` | `dashboard` | `Account\DashboardController` → Admin or Employee dashboard | Admin/HR: `users.manage`; others: `profile.view-own` + `active` |
| GET | `/{role}/users` | `admin.users.index` | `Admin\UserController@index` | `can:users.manage` |
| GET | `/{role}/users/data` | `admin.users.data` | `@data` (JSON) | `can:users.manage` |
| GET | `/{role}/users/create` | `admin.users.create` | `@create` | `can:users.manage` |
| POST | `/{role}/users` | `admin.users.store` | `@store` | `can:users.manage` |
| GET | `/{role}/users/{user}/edit` | `admin.users.edit` | `@edit` | `can:users.manage` (+ HR → admin = 404) |
| PUT/PATCH | `/{role}/users/{user}` | `admin.users.update` | `@update` | `can:users.manage` |
| DELETE | `/{role}/users/{user}` | `admin.users.destroy` | `@destroy` | `can:users.manage`, not self, not last admin |
| GET | `/{role}/departments` | `admin.departments.index` | `Admin\DepartmentController@index` | `can:departments.manage` |
| GET | `/{role}/departments/create` | `admin.departments.create` | `@create` | `can:departments.manage` |
| POST | `/{role}/departments` | `admin.departments.store` | `@store` | `can:departments.manage` |
| GET | `/{role}/departments/{department}/edit` | `admin.departments.edit` | `@edit` | `can:departments.manage` |
| PUT/PATCH | `/{role}/departments/{department}` | `admin.departments.update` | `@update` | `can:departments.manage` |
| DELETE | `/{role}/departments/{department}` | `admin.departments.destroy` | `@destroy` | `can:departments.manage`, refused if it has employees |

`{role}` is `admin` for Admins and `hr` for HR. Every row except `/login` also has `auth`, `auth.session` and `SetPanelPrefix`.
