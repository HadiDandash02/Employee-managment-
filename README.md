# Employee Management System

A Laravel 12 application for managing employee records, departments and user access, with
role-based permissions (Admin, HR, Manager, Employee).

## Features

- **Manage Employees** — DataTables list (search, sort, filters, export, pagination), add / edit / view,
  soft delete with a Trash (restore, delete forever, empty trash).
- **Manage Departments** — CRUD with code, branch and employee count (Admin, HR).
- **Manage Users** — logins, roles, linked employee, suspend / reactivate (Admin, HR).
- **Employee self-service** — Employee Dashboard and My Profile for the Employee role.
- **Organization** — companies → branches → departments → employees, plus profiles and skills.

## Roles

| Role | Can do | Starts on |
|---|---|---|
| Admin | Everything, all users | `/admin/dashboard` |
| HR | Same as Admin, but never sees Admin accounts | `/hr/dashboard` |
| Manager | View, add and edit employees | `/employees` |
| Employee | Only their own record (needs a linked employee) | `/employee/dashboard` |

Roles and permissions are defined in [`config/permissions.php`](config/permissions.php).

## Project structure

The code is organized **by area** — the same area name is used for controllers, views and tests:

| Area | Who | Controllers | Views | Tests |
|---|---|---|---|---|
| Auth | logged-out visitors | `app/Http/Controllers/Auth/` | `resources/views/auth/` | `tests/Feature/Auth/` |
| Account | every logged-in user | `app/Http/Controllers/Account/` | `resources/views/account/` | `tests/Feature/Account/` |
| Staff | Admin, HR, Manager | `app/Http/Controllers/Staff/` | `resources/views/staff/` | `tests/Feature/Staff/` |
| Admin | Admin, HR | `app/Http/Controllers/Admin/` | `resources/views/admin/` | `tests/Feature/Admin/` |
| Employee | Employee role | `app/Http/Controllers/Employee/` | `resources/views/employee/` | `tests/Feature/Employee/` |

More detail: [`app/Http/Controllers/README.md`](app/Http/Controllers/README.md).

Other important folders:

| Folder | Contents |
|---|---|
| `routes/web.php` | Every URL, with the middleware that protects it |
| `app/Http/Middleware/` | `CheckEmployeeStatus` (suspended → 403), `SetPanelPrefix` (`/admin` vs `/hr`) |
| `app/Http/Requests/` | Form Request validation, by area (`Staff/`, `Admin/`) |
| `app/Services/` | Business logic, by area (`Employee/DashboardService`) |
| `app/Models/` | Eloquent models and relationships (shared by all areas) |
| `app/Providers/AppServiceProvider.php` | Turns each permission into a Gate |
| `database/migrations/` | Database structure; `database/factories/` and `database/seeders/` for test/sample data |
| `resources/views/layouts/`, `resources/views/partials/` | Page frame (sidebar, top bar) and shared pieces |
| `tests/Feature/Database/` | Relationship and migration tests |
| `docs/` | ER diagram (`er-diagram.svg`) and theory notes |

## Request flow

```
Browser → public/index.php → bootstrap/app.php → routes/web.php
        → middleware (auth, can:…, active) → Form Request (validation)
        → Controller → Service / Model → MySQL
        → Blade view (or JSON for the tables) → Browser
```

## Setup

```bash
composer install
cp .env.example .env          # set DB_DATABASE, DB_PORT (3307 on XAMPP here), DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # tables + admin user + sample organization and employees
php artisan serve             # or open it through XAMPP Apache
```

Log in with username **`admin`** and the password from `ADMIN_PASSWORD` in `.env` (default `Admin@12345`),
then change it under *profile → My account & password*.

## Tests

```bash
php artisan test
```

Feature tests run against an in-memory SQLite database (see `phpunit.xml`), so they never touch your data.
