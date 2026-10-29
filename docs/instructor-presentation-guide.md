# Presenting my Employee Management System to my instructor

How to use this guide:
- **Part 1–2:** what to say first;
- **Part 3:** where the code of every page is;
- **Part 4:** how to find any code fast while the instructor watches;
- **Part 5:** the questions they will probably ask, with short answers to say in your own words;
- **Part 6:** the demo order;
- **Part 7:** the full reference: every model and controller, what it is and why it exists.

Every answer gives the file to open, so you can **show** the code, not only talk about it.

---

## Part 1: The 30-second introduction

> "My project is an **Employee Management System** built with **Laravel 12** and **MySQL**.
> It has **4 roles**: Admin, HR, Manager and Employee, and each role sees a different part of the system.
> HR and Admin manage employees, departments and user logins. A Manager sees only their own department
> and approves its leave. An Employee sees their own profile and asks for leave.
> The final module is **Leave Management**:
> - an employee requests full or half days;
> - the manager or HR approves or rejects it, with strict status rules;
> - everything is saved in a history and the people involved get notifications.
>
> There is also a **REST API** protected with Sanctum tokens, and more than **240 automated tests**."

---

## Part 2: What I used and WHY

| What | Why I chose it |
|---|---|
| **Laravel 12** (PHP 8.2) | MVC structure, routing, validation, authentication, Eloquent ORM and testing are built in, so I write the business logic, not the plumbing. |
| **MySQL** (XAMPP, port 3307) | A relational database: my data has real relations (department → employees → leave requests) and needs foreign keys and transactions. |
| **Eloquent ORM** (Models) | I work with PHP objects (`$employee->department->name`) instead of writing SQL by hand. It also protects against SQL injection, because it uses prepared statements. |
| **Migrations** (`database/migrations`) | The database structure is in code and in Git, so anyone can rebuild the same tables with `php artisan migrate`. |
| **Factories and Seeders** (`database/factories`, `database/seeders`) | Fake but realistic data for testing and demos; `AdminUserSeeder` creates the first admin. |
| **Blade** (`resources/views`) | Laravel's template engine: layouts, `@csrf`, `@can`, and automatic escaping (`{{ }}`) against XSS. |
| **Bootstrap 5 + Bootstrap Icons** | A professional, responsive design quickly, without writing all the CSS myself. |
| **jQuery + DataTables** (server-side) | The employee and user tables: search, sort, paging, column choice, export (Excel/PDF/print). **Server-side** means the database does the paging, so it stays fast with thousands of rows. |
| **Form Requests** (`app/Http/Requests`) | Validation is separate from the controller and reused (the web form and the API use the same rules). |
| **Gates + Policies** (`config/permissions.php`, `app/Policies`) | Authorization in one place. A **Gate** answers "may this role do X at all?"; a **Policy** answers "may this user do X to THIS record?". |
| **Middleware** (`app/Http/Middleware`) | Checks that run before every controller: logged in, not suspended (`active`), the right `/role/` prefix. |
| **Service layer** (`app/Services`) | The business rules of leave (balance, overlap, status transitions) live in one class, not in the controller, so the web, the API and the tests all use the same rules. |
| **DB transactions + `lockForUpdate`** | Saving a request and its history row is all-or-nothing, and two managers clicking at the same time cannot both approve. |
| **Enums** (`app/Enums`) | `LeaveStatus::Approved` instead of the text `'approved'` typed everywhere: no typos, and the allowed transitions live in one place. |
| **Soft deletes** | A deleted employee goes to the **trash** and can be restored; nothing is lost by mistake. |
| **Database notifications** | The bell icon: approvers are told about new requests, and employees about the decision. |
| **Laravel Sanctum** | Token login for the API (a mobile app or Postman has no cookie session). |
| **PHPUnit feature and unit tests** (in-memory SQLite) | They prove every rule works and catch mistakes when I change code. They run fast and never touch the real database. |
| **Git** (branches EM1…EM8 → master) | Every week's work on its own branch, merged when done; I can go back to any version. |

---

## Part 3: Every page → its URL → its controller → its view

`{role}` in the URL = `admin`, `hr`, `manager` or `employee` (e.g. `/hr/employees`). Routes are in `routes/web.php`.

| Page | URL | Controller@method | View (resources/views/…) | Who can open it |
|---|---|---|---|---|
| Login | `/login` | `Auth\LoginController@create` (form), `@store` (submit) | `auth/login.blade.php` | guests |
| Log out (button) | `POST /logout` | `Auth\LoginController@destroy` | – | logged in |
| Dashboard | `/{role}/dashboard` | `Account\DashboardController` → `Admin\DashboardController` or `Employee\DashboardController` | `admin/dashboard` or `employee/dashboard` | everyone (different dashboard per role) |
| Employee list | `/{role}/employees` | `Staff\EmployeeController@index` (page) + `@data` (table JSON) | `staff/employees/index` | Admin, HR, Manager (own dept.) |
| Add employee | `/{role}/employees/create` | `Staff\EmployeeController@create`, `@store` | `staff/employees/create` | Admin, HR |
| Employee profile | `/{role}/employees/{id}` | `Staff\EmployeeController@show` | `staff/employees/show` | Admin, HR, Manager (own dept.) |
| Edit employee | `/{role}/employees/{id}/edit` | `Staff\EmployeeController@edit`, `@update` | `staff/employees/edit` | Admin, HR |
| Delete / trash / restore | buttons on the list | `Staff\EmployeeController@destroy`, `@restore`, `@forceDelete`, `@bulk…`, `@emptyTrash` | – | Admin, HR |
| My Profile | `/{role}/my-profile` | `Employee\ProfileController@show` | `employee/profile` | linked logins |
| Edit my phone/address | `/{role}/my-profile/edit` | `Employee\ProfileController@edit`, `@update` | `employee/profile-edit` | linked logins (own record only) |
| Leave list | `/{role}/leave-requests` | `Leave\LeaveRequestController@index` | `leave/index` | everyone (own / team / all) |
| Request leave | `/{role}/leave-requests/create` | `Leave\LeaveRequestController@create`, `@store` | `leave/create` | logins linked to an employee |
| One leave request | `/{role}/leave-requests/{id}` | `Leave\LeaveRequestController@show` | `leave/show` | owner, team manager, HR |
| Approve / Reject / Cancel | buttons on that page | `Leave\LeaveRequestController@approve`, `@reject`, `@cancel` | – | approver / approver / owner |
| Notifications | `/{role}/notifications` | `Account\NotificationController@index`, `@open`, `@readAll` | `account/notifications` | everyone |
| My account (password) | `/{role}/account` | `Account\AccountController@edit`, `@updatePassword` | `account/edit` | everyone |
| Manage Users | `/admin/users`, `/hr/users` | `Admin\UserController@index`, `@data`, `@create`, `@store`, `@edit`, `@update`, `@destroy` | `admin/users/*` | Admin, HR (HR never sees Admins) |
| Manage Departments | `/admin/departments`, `/hr/departments` | `Admin\DepartmentController@…` | `admin/departments/*` | Admin, HR |
| API | `/api/login`, `/api/employees…` | `Api\AuthController`, `Api\EmployeeController` (`routes/api.php`) | JSON (`EmployeeResource`) | Sanctum token |

Shared pieces of the design:
- `layouts/app.blade.php`: the sidebar, the top bar, the bell and the user menu;
- `layouts/_alerts.blade.php`: the green and yellow messages;
- `partials/`: the avatar, the leave balance card, one notification.

---

## Part 4: How to find the code of ANY page in 10 seconds

**Way 1: ask Laravel** (the best one to show the instructor):
```bash
php artisan route:list --path=leave-requests
```
It prints `URL → route name → Controller@method`. Then open the controller, and the method shows `view('leave.index')` → `resources/views/leave/index.blade.php`.

**Way 2: from the button.** The Blade code of a button has `route('leave-requests.approve', …)`. Search that name in `routes/web.php` and you find the controller.

**Way 3: the folder rule I used:**
- controllers are grouped by who uses them: `Auth`, `Account`, `Employee`, `Staff`, `Admin`, `Leave`, `Api`;
- views use the same names: `staff/employees/index.blade.php` belongs to `Staff\EmployeeController@index`.

---

## Part 5: Questions the instructor may ask, with my answers

### MVC and Laravel basics

**Q: What is MVC? Show me in your project.**
A:
- **Model** = the data and its rules (`app/Models/Employee.php`);
- **View** = what the user sees (`resources/views/staff/employees/index.blade.php`);
- **Controller** = it receives the request, asks the model, and returns the view (`app/Http/Controllers/Staff/EmployeeController.php`).

The route (`routes/web.php`) connects a URL to a controller method.

**Q: What happens when I click "Approve"?**
A:
1. The form sends `POST /manager/leave-requests/5/approve` with the CSRF token.
2. The route's middleware checks that I'm logged in and not suspended, and `->can('approve', 'leaveRequest')` runs `LeaveRequestPolicy::approve`.
3. `DecideLeaveRequestRequest` validates the comment.
4. `LeaveRequestController@approve` calls `LeaveRequestService::approve()`. In a transaction it locks the row and checks: not my own request, status is PENDING, enough balance. Then it saves the status and a history row.
5. After the commit, the employee is notified.
6. Redirect back with "Leave request approved."

**Q: Why are your controllers so short?**
A: I keep them "thin":
- validation is in Form Requests;
- permissions are in Policies;
- business rules are in Services;
- query logic is in model scopes.

The controller only connects them. This way the API and the tests reuse the same rules.

**Q: What is Route Model Binding?**
A: In `Route::get('/employees/{employee}', …)` with `show(Employee $employee)`, Laravel loads the employee by its id automatically, or gives 404 if it doesn't exist. I don't write `Employee::findOrFail($id)`.

**Q: What is middleware? Which ones did you write?**
A: Code that runs before the controller. I wrote:
- `CheckEmployeeStatus` (alias `active`): a suspended user gets 403;
- `SetPanelPrefix`: the URL must use the user's own role prefix (`/hr/...`), otherwise 404.

Laravel's own middleware: `auth`, `guest`, `can:`, `throttle`, CSRF. They are registered in `bootstrap/app.php`, because Laravel 12 has no Kernel.php.

**Q: Why does every URL start with the role (`/hr/employees`)?**
A: So the address shows which panel you are in, and each role has its own space. A user can't open another role's prefix (404). Old links without a prefix are redirected (301).

### Database and Eloquent

**Q: Explain your database relationships.**
A:
- Company → many Branches → many Departments → many Employees (`hasMany` / `belongsTo`);
- Employee ↔ EmployeeProfile is **one-to-one** (`hasOne`);
- Employee ↔ Skill is **many-to-many** through the pivot `employee_skill`, with a `level`;
- Employee → many LeaveRequests → many LeaveRequestActions (the history);
- User ↔ Employee via `users.employee_id`, because a login is not the same thing as an employee.

The diagram is in `docs/er-diagram.svg`.

**Q: Why are User and Employee separate tables?**
A: Not every employee needs a login, and an Admin may not be an employee. Login data (password, role, suspended) and HR data (salary, department) are different things. `employee_id` links them, and one login per employee is enforced.

**Q: What are `$fillable` and mass assignment?**
A: `$fillable` lists the columns a form may fill. If a hacker adds `status=approved` or `role=admin` to a form, it is ignored. For example, `LeaveRequest` has no `status` in `$fillable`; only the service sets it.

**Q: What is a scope? Give an example.**
A: A reusable piece of a query. `Employee::visibleTo($user)`: HR gets everyone, a Manager only their department, an Employee only themselves. The security rule is written once and used by the list, the table JSON and the API.

**Q: What is the N+1 problem? How do you avoid it?**
A: Loading a list, then running one extra query per row for its relation. I use **eager loading**: `Employee::with('department')`, so it's 2 queries instead of 1 + N. The API Resource uses `whenLoaded()` for the same reason.

**Q: What are soft deletes?**
A: `delete()` only fills `deleted_at`, so the row is hidden but kept. HR can restore it from the trash, or delete it forever. The `SoftDeletes` trait is on `Employee` and `Department`.

**Q: What is a migration? A seeder? A factory?**
A:
- **Migration** = the table structure in code (`php artisan migrate`);
- **Seeder** = data to insert (the first admin, the departments);
- **Factory** = fake data for tests (`Employee::factory()->count(10)->create()`).

### Validation

**Q: Where is your validation? Why there?**
A: In **Form Requests**: `app/Http/Requests/Staff/StoreEmployeeRequest.php` (email required, a valid email, unique; department must exist; salary ≥ 0). If it fails, Laravel goes back to the form with the errors and the old input. For the API it answers **422 JSON**. The same class serves both. The custom messages are in `EmployeeRequest::messages()`.

**Q: What's the difference between validation and a business rule?**
A:
- **Validation** checks that the input is well-formed: "the date is a date", "the end date is after the start date". It's a Form Request and fails as 422.
- A **business rule** checks that it is allowed **right now**: "you have enough leave left", "only a pending request can be approved". It's in the Service and throws `BusinessRuleException`, which goes back with the message.

### Authentication and authorization (security)

**Q: Authentication vs authorization?**
A:
- **Authentication** = *who are you?* (login, `auth` middleware);
- **Authorization** = *what may you do?* (Gates, Policies, `->can()`).

**Q: How do roles and permissions work?**
A:
- `config/permissions.php` lists each role's permissions, e.g. manager → `employees.view-team`, `leave.approve`.
- `AppServiceProvider` turns every permission into a **Gate**.
- **Policies** add the record check: `EmployeePolicy`, `LeaveRequestPolicy`.

They are checked in three places: in **routes** (`->can()`, the real protection), in **Blade** (`@can`, which only hides buttons), and in **services** (`Gate::authorize`).

**Q: If I hide the Edit button, is that enough?**
A: No. Hiding is only cosmetic. If someone types `/manager/employees/5/edit` by hand, the route's `->can('update', 'employee')` gives **403**. My tests type the URLs by hand to prove it (`tests/Feature/Staff/EmployeeAuthorizationTest.php`).

**Q: Can a Manager approve another department's leave?**
A: No. `LeaveRequestPolicy::approve` compares the manager's department with the employee's department, so the answer is 403. Even the list only shows their team (`LeaveRequest::visibleTo`). It is tested in `LeaveFinalAssessmentTest`.

**Q: Can an employee approve their own leave?**
A: No. Even HR can't approve their own request: `LeaveRequestService` checks `isOwnedBy()` and refuses.

**Q: What is CSRF? What is the 419 error?**
A: CSRF is when another website tricks your browser into sending a form to my site. Every form has `@csrf`, a secret token. If the token is wrong or old, Laravel refuses with **419 Page Expired**. I handle it in `bootstrap/app.php`: logout still logs you out, and other forms go back with a friendly "session expired" message. The protection stays on.

**Q: How are passwords stored?**
A: Hashed (bcrypt) with the `'password' => 'hashed'` cast, never in plain text. Login uses `Auth::attempt()`, which compares hashes. Login is throttled to 5 tries, against guessing. Changing the password logs out the account's other devices.

**Q: How do you prevent XSS and SQL injection?**
A:
- **XSS:** Blade `{{ }}` escapes all output.
- **SQL injection:** Eloquent and the query builder use prepared statements. In search I also escape `%` and `_` in `LIKE`.

### The Leave module (final project)

**Q: Explain the leave flow.**
A:
1. **Submit:** the employee picks dates and morning/afternoon for half days. `LeaveRequestService::submit()` refuses if:
   - the dates are reversed;
   - it's weekend only;
   - it runs across two years;
   - there isn't enough balance;
   - it overlaps another pending or approved request.

   Otherwise it saves the request (PENDING) and a "submitted" history row in a transaction, then notifies the approvers.
2. **Decide:** the team manager or HR approves or rejects. Rejecting needs a comment. The employee is notified.
3. **Cancel:** the owner can cancel only while it is PENDING.

**Q: What are the status rules?**
A: Only **PENDING → APPROVED, REJECTED or CANCELLED**. Approved, rejected and cancelled are final. The table is in `app/Enums/LeaveStatus.php` (`allowedTransitions()`), and `LeaveRequestService::ensureTransition()` checks every change. A blocked change (e.g. APPROVED → REJECTED) throws `BusinessRuleException`.

**Q: Why a transaction? Why `lockForUpdate`?**
A:
- **Transaction:** every action writes two tables (the request and its history). If the second write fails, the first is rolled back, so the data is never half-saved.
- **`lockForUpdate`:** locks the row until the commit. If two managers click Approve at the same moment, the second waits, sees "already Approved", and is refused.

**Q: Why are notifications sent after the commit?**
A: So nobody is told about a change that was then rolled back.

**Q: How is the leave balance calculated?**
A: In `LeaveBalanceService`:
- everyone starts with **3 days** (`base_leave_days`; HR can change it per employee);
- they earn **1.25 days per month** (from the hire month in the hire year), set in `config/leave.php`;
- remaining = earned − approved − pending. Pending days are reserved, so you can't request them twice.

**Q: Why the history table?**
A: For an **audit trail**: who did what, when, from which status to which, and with which comment. Statuses are not just overwritten; you can always see the story on the request page.

**Q: Where are the search and pagination?**
A: In `LeaveRequestController@index`:
- `LeaveRequest::visibleTo($user)->search($term)`, where the search matches the employee name or the reason;
- `->paginate(10)->withQueryString()`, so page 2 keeps the search;
- the search box and the `links()` are in `resources/views/leave/index.blade.php`.

### API

**Q: Explain your API.**
A: `routes/api.php`, `Route::apiResource('employees', Api\EmployeeController::class)`:
- `index` (10 per page, with `?search=` and `?department_id=`), `show`, `store` (201), `update`, `destroy` (204);
- `EmployeeResource` controls the JSON, so only the needed fields leave the server;
- the same Form Requests and Policies as the web.

**Q: How is the API protected?**
A: With **Laravel Sanctum**:
1. `POST /api/login` returns a token;
2. every request sends `Authorization: Bearer <token>`;
3. no token gives **401**, and not being allowed gives **403**.

Errors are always JSON, with 422 in the format `{"message": "The given data was invalid.", "errors": {...}}`.

**Q: Why an API Resource instead of returning the model?**
A: Returning the model would expose every column (`deleted_at`, internal ids, maybe private data). The Resource is a whitelist, and adding a new column to the table does **not** add it to the API.

### Testing

**Q: How do you know it works?**
A: More than 240 automated tests: `php artisan test`.
- **Feature tests** send real requests (`$this->actingAs($hr)->post(...)`) and check the redirect, the database (`assertDatabaseHas`) and the 403s.
- **Unit tests** check small pieces, for example the `full_name` and `age` accessors.
- They use `RefreshDatabase` with an in-memory SQLite database, so the real data is never touched.

**Q: Show me a test for the leave rules.**
A: `tests/Feature/Leave/LeaveFinalAssessmentTest.php`:
- cancel;
- 7 blocked transitions;
- search and 10 per page;
- the two policies.

Run only that file with `php artisan test tests/Feature/Leave/LeaveFinalAssessmentTest.php`.

### The "improve" questions

**Q: What would you improve with more time?**
A:
- email notifications as well as the bell;
- public holidays in the day count;
- a leave calendar view;
- leave endpoints in the API;
- queued notifications;
- a CI pipeline that runs the tests on every push.

**Q: What was the hardest part?**
A: The leave rules together: half days, the balance with pending days reserved, overlap checks, and making approve safe when two people click at once (transaction + `lockForUpdate`).

---

## Part 6: Demo script (5 minutes)

Before you start: run `php artisan serve` and open `http://127.0.0.1:8000`.

1. **Log in as an Employee** and show:
   - the dashboard;
   - My Profile (they can edit only the phone and address);
   - Request leave: a half day, and the balance card;
   - an error: overlapping dates → the business rule message.
2. **Log in as that department's Manager** and show:
   - the bell notification;
   - the leave list (only their team) with **search** and **pagination**;
   - opening the request → **Approve** → the history row.
3. **Try a blocked action:**
   - Reject the approved request (or open its URL in a second tab) → "already Approved: it cannot be rejected";
   - type another department's request URL → **403**.
4. **As the Employee:** create a new request → **Cancel** → status CANCELLED and the history row.
5. **As HR:** show
   - Manage Employees (DataTables search, filter, export, trash and restore);
   - Manage Users (link a login to an employee, suspend).
6. **The code:** run `php artisan route:list --path=leave-requests`, then open:
   1. the controller;
   2. `LeaveRequestService` (`ensureTransition`, the transaction);
   3. `LeaveStatus::allowedTransitions()`;
   4. `LeaveRequestPolicy`.
7. **The tests:** run `php artisan test` → all green.

## Useful commands

```bash
php artisan serve                                # start the site on 127.0.0.1:8000
php artisan route:list --path=employees          # which controller answers a URL
php artisan test                                 # all tests
php artisan test --filter=LeaveFinalAssessment   # one test class
php artisan migrate                              # create/update the tables
php artisan db:seed                              # insert the first admin + demo data
php artisan tinker                               # try code live: App\Models\Employee::count()
```
Errors are logged in `storage/logs/laravel.log`.

---

## Part 7: Reference: every model and every controller explained

The detailed reference behind the answers above. Read the Models first: the Controllers only make sense once you know the data.

---

### 7.1 The big picture: what happens when you click a button

```
Browser  ──►  Route (routes/web.php or routes/api.php)
                │   which URL → which controller method
                ▼
             Middleware          auth (logged in?), active (not suspended?), can:… (allowed?), CSRF token
                ▼
             Form Request        is the input correct? (app/Http/Requests)        → 422 / back with errors
                ▼
             Controller          reads the request, calls models/services, picks the answer
                ▼
             Service (optional)  business rules + DB transaction (app/Services)    → BusinessRuleException
                ▼
             Model               one table: read / save rows, relationships, scopes
                ▼
             Database (MySQL employee_crud)
                ▲
             View (Blade) or JSON (API Resource)  ◄── the answer sent back to the browser
```

**The rule we follow: "thin controllers".** A controller should be short. It must NOT contain:
- validation: that goes in a **Form Request**;
- "who may do this": that goes in a **Policy** or a **Gate**, checked on the route;
- business rules ("only pending can be approved"): that goes in a **Service**;
- query details ("only my department"): that goes in a **scope** on the Model.

---

### 7.2 Models (app/Models)

A **Model** is a PHP class for one database table. `Employee` = table `employees`, one object = one row.
A Model knows:
- **`$fillable`**: which columns a form may fill (protection against "mass assignment": a hacker adding `role=admin` to a form);
- **casts**: how to convert a column (`'date'` → a Carbon date, `'boolean'`, an Enum, `'hashed'` for passwords);
- **relationships**: how it is linked to other tables (`belongsTo`, `hasMany`, `hasOne`, `belongsToMany`);
- **accessors**: computed fields that are not columns (`$employee->full_name`);
- **scopes**: reusable query pieces (`Employee::search('jane')->visibleTo($user)`).

#### How the tables are linked

```
Company 1──* Branch 1──* Department 1──* Employee 1──1 EmployeeProfile
                                            │  *
                                            │  └──* Skill            (pivot employee_skill, with "level")
                                            │1
                                            ├──1 User (the login, users.employee_id)
                                            └──* LeaveRequest 1──* LeaveRequestAction (history)
```
(The full diagram is in `docs/er-diagram.svg`.)

#### Company
- **What:** a company (table `companies`, column `name`).
- **Why:** the top level of the organisation, so a department can show "Sales – Beirut branch – Acme".
- **Relationships:** `branches()`: one company has many branches (`hasMany`).

#### Branch
- **What:** an office of a company (`company_id`, `name`, `city`).
- **Why:** departments live in a branch; the employee list can be filtered by branch.
- **Relationships:**
  - `company()` (`belongsTo`);
  - `departments()` (`hasMany`);
  - `employees()` (`hasManyThrough`: the employees of all its departments, without a direct column).

#### Department
- **What:** a department such as Sales or Support (`branch_id`, `name`, `code`, `description`).
- **Why:** every employee belongs to one. It is also the base of the **Manager** rights: a Manager only sees and approves their own department.
- **Relationships:**
  - `branch()` (`belongsTo`);
  - `employees()` (`hasMany`).
- **Extra:**
  - `location` accessor: "Branch, City";
  - `search()` scope: for the departments page.
- **Soft deletes:** a deleted department goes to the trash; it cannot be deleted while employees use it (restrict on delete).

#### Employee (the centre of the system)
- **What:** one staff member (`first_name`, `last_name`, `email`, `department_id`, `salary`, `base_leave_days`, `date_of_birth`).
- **Why:** everything is about employees: the list, the profile, leave, the API.
- **Casts:**
  - `date_of_birth` → date;
  - `salary` → `decimal:2` (always "4200.00");
  - `base_leave_days` → float.
- **Default:** `base_leave_days = 3`: every employee starts with 3 days of leave.
- **Soft deletes:** "Delete" puts the employee in the **trash** (`deleted_at` is filled); they can be restored. "Delete forever" really removes the row.
- **Relationships:**
  - `department()`: belongs to one department;
  - `profile()`: one `EmployeeProfile` (phone, address, hire date);
  - `user()`: the login account of this employee, if any;
  - `leaveRequests()`: all their leave requests;
  - `skills()`: many-to-many with the skill `level` on the pivot table.
- **Accessors:**
  - `full_name` → "Jane Doe" (tested in `tests/Unit/EmployeeTest.php`);
  - `age` → from `date_of_birth`, or null;
  - `initials`, `avatar_color`, `department_color` → only for the design (avatar circle, badge colour).
- **Scopes:**
  - `search($term)`: every word must match the first name, last name, email or department name ("doe sales" works);
  - `visibleTo($user)`: **security**. HR/Admin get everyone, a Manager only their department, an Employee only themselves. Used by the list, the DataTables JSON and the API, so the rule is written once;
  - `filter($filters)`: the filter panel (department, branch, salary min/max, born from/to, added from/to). Wrong values are ignored;
  - `sortBy($sort, $direction)`: sort by a column from the `SORTABLE` list only. An unknown column falls back to newest first, so a user can't sort by a secret column.

#### EmployeeProfile
- **What:** extra details of an employee: `phone`, `address`, `hire_date` (table `employee_profiles`).
- **Why a separate table:** a one-to-one example (`hasOne` / `belongsTo`), and it keeps the private contact data apart. The employee may edit **only** this part of their record ("My Profile").
- **Relationships:** `employee()` (`belongsTo`).
- `hire_date` is also used by the leave balance: monthly leave is earned from the hire month.

#### Skill
- **What:** a skill name (PHP, Excel, …).
- **Why:** a many-to-many example: one employee has many skills, one skill belongs to many employees.
- **Relationships:** `employees()` (`belongsToMany` through `employee_skill`, which also stores the `level`).

#### User (the login)
- **What:** a person who can log in (`name`, `username`, `email`, `password`, `role`, `employee_id`, `is_active`).
- **Why separate from Employee:** not every employee has a login, and an Admin may have no employee record. `employee_id` links a login to its employee.
- **Casts:**
  - `password` → `hashed`: saved encrypted automatically, never in plain text;
  - `is_active` → boolean. `false` = **suspended**: the `active` middleware blocks them.
- **Hidden:** `password` and `remember_token` are never put in JSON.
- **Traits:**
  - `Notifiable`: the bell notifications;
  - `HasApiTokens`: Sanctum tokens for the API.
- **Roles and permissions:** the roles (admin, hr, manager, employee) and what each may do are in `config/permissions.php`.
  - `hasPermission('leave.approve')` answers from that file, and `isAdmin()` checks the admin role (`*` = everything).
  - `manageableRoles()`, `canManageRole()`, `canManageUser()`: HR may manage Managers and Employees, but **never** an Admin account.
- **URL prefix:** `url_prefix` gives `admin`, `hr`, `manager` or `employee`, so every URL starts with the role (`/hr/employees`). `homeUrl()` is the start page after login.
- **Design helpers:** `panel_label`, `panel_icon`, `role_color`, `role_label`, `initials`.

#### LeaveRequest
- **What:** one request for leave (`employee_id`, `start_date`, `start_period`, `end_date`, `end_period`, `days`, `reason`, `status`, `approved_by`, `approved_at`).
- **Why:** the final project: employees ask, managers/HR decide.
- **`$fillable` on purpose has NO `status`, `approved_by` or `approved_at`.** Those are set only by `LeaveRequestService`, so a form can never send `status=approved`.
- **Casts:**
  - `status` → the **`LeaveStatus` enum** (Pending, Approved, Rejected, Cancelled);
  - `start_period` and `end_period` → the `LeavePeriod` enum (morning, afternoon), which is how half days work.
- **Relationships:**
  - `employee()`: also finds an employee who is in the trash;
  - `approver()`: the User who decided;
  - `actions()`: the history rows.
- **Helpers:**
  - `isPending()`;
  - `isOwnedBy($user)`: is this my request?;
  - `period_text` → "07 Oct 2026 (afternoon) → 09 Oct 2026";
  - `days_text` and `days_label` → "1.5 days";
  - `startSlot()` and `endSlot()`: "2026-11-02-1" strings used to compare half days.
- **Scopes:**
  - `visibleTo($user)`: HR all, Manager own department (+ their own requests), Employee only their own;
  - `search($term)`: by employee name or reason;
  - `overlapping(...)`: requests of the same employee on the same dates (to refuse double leave).

#### LeaveRequestAction (the history)
- **What:** one line of a request's history (`leave_request_id`, `user_id`, `action`, `from_status`, `to_status`, `comment`).
- **Why:** an **audit trail**: who submitted, who approved/rejected/cancelled, when, with which comment. Nothing is overwritten; a new row is added each time.
- **Casts:**
  - `action` → the `LeaveAction` enum;
  - `from_status` and `to_status` → `LeaveStatus`.
- **Relationships:**
  - `leaveRequest()`;
  - `user()`: who did it.

#### Notifications (no model file)
The bell uses Laravel's built-in `DatabaseNotification` model (table `notifications`). `LeaveRequestSubmitted` tells the approvers; `LeaveRequestDecided` tells the employee.

---

### 7.3 Controllers (app/Http/Controllers)

Folders group the controllers by **who uses them**:

| Folder | For whom |
|---|---|
| `Auth` | guests (login) |
| `Account` | every logged-in user |
| `Employee` | the employee's own pages |
| `Staff` | managing employees (Admin, HR, Manager) |
| `Admin` | Admin and HR only |
| `Leave` | leave requests (every role) |
| `Api` | the JSON API (Sanctum token) |

`Controller.php` is the empty parent class that every controller extends.

#### Auth\LoginController
- **Why:** to log in and out with a session (cookie).
- **Methods:**
  - **`create()`**, `GET /login`: shows the login form.
  - **`store()`**, `POST /login`:
    1. checks the username **or** email and the password;
    2. after **5 wrong tries** it locks for a short time (RateLimiter: protection against password guessing);
    3. makes a new session id (`regenerate()`, protection against session fixation);
    4. sends the user to their start page (`homeUrl()`).
  - **`destroy()`**, `POST /logout`: logs out, destroys the session and the CSRF token, and goes to the login page.
    If the page was old (419 Page Expired), `bootstrap/app.php` logs out anyway. See section 4.

#### Account\DashboardController
- **Why:** one URL, `/{role}/dashboard`, for every role. A route can only call one controller, so this one **hands over**.
- **`__invoke()`** (a one-method controller):
  - Admin/HR → `Admin\DashboardController`;
  - Manager/Employee → `Employee\DashboardController`.

#### Admin\DashboardController
- **Why:** the management overview for Admin and HR.
- **`__invoke()`** shows:
  - totals: employees, departments, average salary, payroll, users;
  - each department with its employee count and average salary (`withCount`, `withAvg`, done by the database, not PHP loops);
  - the 5 newest employees;
  - the users and the roles/permissions table.
  It only shows roles this person may manage (HR never sees Admins).

#### Employee\DashboardController
- **Why:** the personal start page of an employee.
- **`__invoke()`:** asks `DashboardService` for a summary of the logged-in employee.
  - It shows **constructor injection**: Laravel creates the service and passes it in; the controller never writes `new`.
  - Access is checked on the route: `auth`, `can:profile.view-own`, `active`.

#### Employee\ProfileController ("My Profile")
- **Why:** an employee sees **only their own** record and may change **only** their phone and address.
- **Methods:**
  - **`show()`**, `GET /{role}/my-profile`: their own employee record (from `users.employee_id`), or a message if the login is not linked.
  - **`edit()`** and **`update()`**: phone and address only.
    - `UpdateOwnContactRequest` validates the input.
    - `EmployeePolicy::updateContact` checks it is their own record.
    - Extra fields a hacker adds (salary, department) are ignored.

#### Account\AccountController ("My account")
- **Why:** every user can change their own password.
- **Methods:**
  - **`edit()`**: the page;
  - **`updatePassword()`**:
    1. checks the current password;
    2. saves the new one (hashed automatically by the `hashed` cast);
    3. **logs this account out on every other browser/device** (`Auth::logoutOtherDevices`), so a stolen session stops working.

#### Account\NotificationController (the bell)
- **Why:** to see leave notifications.
- **Security:** always goes through `$request->user()->notifications()`, so nobody can open another person's notification by changing the id.
- **Methods:**
  - **`index()`**: the list;
  - **`open()`**: marks it read and goes to the leave request it is about;
  - **`readAll()`**: marks everything read.

#### Staff\EmployeeController (Manage Employees, the biggest one)
- **Why:** the employee list and the full add/edit/delete of employees.
- **Who:** Admin and HR can do everything; a Manager can only **see** their department. Every route has `->can(...)`, which runs `EmployeePolicy`.
- **The list:**
  - **`index()`**: the page only; the table rows come from `data()`.
  - **`data()`**: **JSON for DataTables**. It does the search, filters, sorting and paging on the server, and returns only the rows from `visibleTo($user)`. `?status=trash` lists the trash, but only for users who may delete.
- **Add and edit:**
  - **`create()`** and **`store()`**: the form and the save.
    1. `StoreEmployeeRequest` validates (email unique, department exists, …).
    2. The save runs in a `DB::transaction` so employee + profile + skills are saved together or not at all.
  - **`show()`**: the profile page: details, skills, login account, department average, 5 colleagues.
  - **`edit()`** and **`update()`**: the same as create, with `UpdateEmployeeRequest`. Only the fields that were sent change.
- **Delete and the trash:**
  - **`destroy()`**: **soft delete** (to the trash).
  - **`bulkDestroy()`**, **`bulkRestore()`**, **`bulkForceDelete()`**: the checkboxes, many at once.
  - **`emptyTrash()`**: deletes everything in the trash for good.
  - **`restore()`** and **`forceDelete()`**: one employee out of the trash, or deleted forever.
- **Private helpers:**
  - `saveDetails()`: profile + skills;
  - `validatedIds()`: checks the id list;
  - `respond()`: a JSON answer for AJAX or a redirect for normal forms.

#### Admin\UserController (Manage Users)
- **Why:** to create the **logins** and link each one to an employee.
- **Who:** Admin and HR (`can:users.manage`). HR can never see, edit or delete an Admin account.
- **Methods:**
  - **`index()`** and **`data()`**: the page and its DataTables JSON (search by name, filter by role, sort, page).
  - **`create()`** and **`store()`**:
    - the role defaults to Employee;
    - an Employee login **must** be linked to an employee;
    - one login per employee.
  - **`edit()`** and **`update()`**: change the role, link, password, or **suspend** (`is_active = false`).
  - **`destroy()`**: delete a login. The employee record stays.

#### Admin\DepartmentController (Manage Departments)
- **Why:** to add, edit and delete departments (Admin/HR, `can:departments.manage`).
- **Methods:**
  - **`index()`**: list with search and employee counts;
  - **`create()`** and **`store()`**, **`edit()`** and **`update()`**: validated by `DepartmentRequest`;
  - **`destroy()`**: soft delete, refused while employees are still in it.

#### Leave\LeaveRequestController
- **Why:** the leave pages for every role. A **thin controller**: all the rules are in `LeaveRequestService`.
- **Methods:**
  - **`index()`**: the leave list.
    - The rows come from `visibleTo($user)`: own requests for an Employee, the team for a Manager, everyone for HR.
    - It has the **search** (employee name or reason), status tabs with counts (one query), **10 per page**, and the "My leave this year" balance card.
  - **`create()`**: the form with the current balance.
  - **`store()`**: `StoreLeaveRequestRequest` validates the form, then `service->submit()`:
    - checks the dates, weekends, the balance, overlaps and the year;
    - saves the request + history in a transaction;
    - notifies the approvers.
  - **`show()`**: one request with its full history and the employee's balance (helps the manager decide).
  - **`approve()`** and **`reject()`**:
    - the route checks `LeaveRequestPolicy::approve` (HR all, Manager only their team);
    - then the service checks:
      - not your own request;
      - **strict transition: only PENDING can change**;
      - enough balance;
    - and finally it notifies the employee.
  - **`cancel()`**: only the owner, only while PENDING → CANCELLED + a history row; the days become free again.

#### Api\AuthController
- **Why:** an app (mobile, Postman) has no cookie session, so it uses a **token** (Laravel Sanctum).
- **Methods:**
  - **`login()`**, `POST /api/login`: checks the username/email and password, refuses suspended users, and returns `{"token": "…"}`. Limited to 5 tries a minute.
  - **`logout()`**, `POST /api/logout`: deletes the token that was used.

#### Api\EmployeeController
- **Why:** the REST API: the same employee data as JSON (`/api/employees`).
- **Reuse:** the same `StoreEmployeeRequest`, `UpdateEmployeeRequest` and `EmployeePolicy` as the web pages, so the rules are not written twice.
- **`middleware()`:** the policy checks run **before** validation, so an unauthorised user gets 403, not a list of the form's rules.
- **Methods:**
  - **`index()`**: 10 per page, `?search=` and `?department_id=`, only the rows from `visibleTo($user)`;
  - **`show()`**, **`store()`** (201), **`update()`**, **`destroy()`** (204, soft delete).
- **The answer:** `EmployeeResource` returns only `id`, `full_name`, `email`, `department`, `salary`, `created_at`, never `deleted_at` or other internal columns.
- **Errors:** always JSON. 401 not logged in, 403 not allowed, 404 not found, 422 `{"message": "The given data was invalid.", "errors": {...}}`.

---

### 7.4 Where the other rules live (not in controllers)

| Folder / file | What it does | Example |
|---|---|---|
| `routes/web.php` | URL → controller, with `->can()` and middleware | `/hr/employees/5/edit` → `EmployeeController@edit` if `EmployeePolicy::update` says yes |
| `routes/api.php` | the API URLs (`/api/...`) | `Route::apiResource('employees', ...)` |
| `app/Http/Requests` | **Form Requests**: validation + custom messages | `StoreEmployeeRequest`: email required, unique |
| `app/Policies` | **who may do what to THIS record** | `LeaveRequestPolicy::approve`: Manager only for their team |
| `config/permissions.php` | roles and their permissions (the Gates are built from it) | manager → `employees.view-team`, `leave.approve` |
| `app/Http/Middleware` | checks before the controller | `CheckEmployeeStatus` (`active`): suspended → 403; `SetPanelPrefix`: the `/hr/` prefix |
| `app/Services` | business rules + transactions | `LeaveRequestService`: strict transitions, balance, overlap |
| `app/Enums` | fixed lists of values | `LeaveStatus`: allowed transitions, label, colour |
| `app/Http/Resources` | the JSON shape of the API | `EmployeeResource` |
| `bootstrap/app.php` | middleware aliases + error handling | JSON errors for `/api/*`; the **419 Page Expired** handling |

#### 419 Page Expired: why it happened and how it is handled now
Every form has a hidden **CSRF token** (`@csrf`). Laravel compares it with the token in your session; if they differ, it answers 419.
That happens when:
- **two tabs:** you logged out or in on one tab, then sent a form from the other;
- **the Back button:** the old page still has the old token;
- **the session expired:** after 120 minutes (`SESSION_LIFETIME` in `.env`).

Now `bootstrap/app.php` catches it:
- **Log out** with an old token → you are logged out anyway and see the login page;
- **any other form** → back to the page with a yellow "Your session expired, so the form was not sent. Please try again." Your typed text is kept (not passwords). The form is still **not** processed, so the protection stays;
- **AJAX/JSON** requests keep the normal 419.

It is tested in `tests/Feature/Auth/ExpiredPageTest.php`.
