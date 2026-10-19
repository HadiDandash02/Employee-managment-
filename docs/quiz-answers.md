# Week 2 — Theory Quiz Answers

Short answers, with examples from this project where possible.

---

## PHP: `=` vs `==` vs `===`

| Operator | Meaning | Example |
|---|---|---|
| `=` | **Assignment**: puts a value into a variable. | `$salary = 5000;` |
| `==` | **Loose comparison**: equal *after type juggling*. | `"5" == 5` → `true`, `0 == "a"` → `false` (PHP 8), `null == false` → `true` |
| `===` | **Strict comparison**: same value **and** same type. | `"5" === 5` → `false`, `5 === 5` → `true` |

Prefer `===`, because it avoids surprises. The project uses it everywhere, e.g. `$this->role === 'admin'` in `User::isAdmin()` and `in_array($role, $visibleRoles, true)` (the `true` makes it strict).

## Namespace

A namespace groups classes under a unique name, like folders, so two classes with the same name don't clash.
`App\Models\Employee` and `App\Http\Controllers\EmployeeController` live in different namespaces. With PSR-4,
the namespace matches the folder (`App\` = `app/`), so Composer can find the file automatically. You import a
class with `use App\Models\Employee;`.

## Composer

PHP's **dependency manager**. `composer.json` lists the packages the project needs (e.g. `laravel/framework`),
`composer install` downloads them into `vendor/`, and `composer.lock` pins the exact versions so every computer
gets the same ones. Composer also generates the **autoloader** (`vendor/autoload.php`) that loads classes from
their namespace, so there's no need for `require` everywhere.

## MVC

**Model – View – Controller**, a way to split responsibilities:

- **Model**: data and business rules (`app/Models/Employee.php`: relationships, scopes like `search()`).
- **View**: what the user sees (`resources/views/employees/index.blade.php`).
- **Controller**: receives the request, uses models, and returns a view or JSON (`EmployeeController`).

Each part can change without breaking the others: the same model feeds both the HTML page and the JSON for DataTables.

## Route

A route maps a **URL + HTTP method** to code. In `routes/web.php`:
```php
Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed();
```
`{employee}` is a route parameter. With **route model binding**, Laravel turns the id into an `Employee` model automatically.
Named routes let views use `route('employees.index')` instead of hard-coded URLs.

## Controller

A class that groups the actions for one resource. `EmployeeController` has `index`, `create`, `store`, `show`,
`edit`, `update`, `destroy` (resource actions), plus `restore` and `forceDelete`. It stays thin: validation is
in Form Requests, queries are in model scopes, and HTML is in Blade views.

## Blade

Laravel's **template engine**. `{{ $employee->full_name }}` prints a value **HTML-escaped** (protects against XSS).
Directives like `@if`, `@foreach`, `@can('employees.delete')`, `@csrf`, `@error('email')`, `@extends('layouts.app')`
and `@include('employees._form')` make views short and reusable. Blade compiles to plain PHP and caches it.

## Migration

A PHP file that **changes the database schema in code**, so the schema has version control like the code.
`up()` applies the change and `down()` reverts it. Example: `2026_10_19_000003_create_departments_table.php`
creates the table with `$table->foreignId('branch_id')->constrained()->cascadeOnDelete();`.
`php artisan migrate` runs new migrations, and `migrate:rollback` undoes them.

## Artisan

Laravel's **command-line tool** (`php artisan …`). Examples: `migrate`, `db:seed --class=OrganizationSeeder`,
`make:model Department -mf` (model + migration + factory), `route:list`, `test`, `tinker`, `serve`, `view:clear`.

## Middleware

Code that runs **before (and/or after) a request reaches the controller**, like layers around the app.
Examples here: `auth` (must be logged in), `can:employees.delete` (permission check → 403),
`guest` (only for logged-out users), `auth.session`, and our own `SetPanelPrefix` (`/admin` vs `/hr`).
Middleware can stop a request early (redirect to login, 403, 404) or let it continue with `$next($request)`.

## Request Lifecycle

1. The browser sends a request, and the web server points it to **`public/index.php`**.
2. Composer's autoloader is loaded and the Laravel **application** is created (`bootstrap/app.php`).
3. **Service providers** start up (e.g. `AppServiceProvider` registers the permission Gates).
4. The HTTP **kernel** sends the request through **global middleware** (sessions, cookies, CSRF…).
5. The **router** finds the matching route and runs its **route middleware** (`auth`, `can:…`).
6. **Form Request** validation and **route model binding** happen, then the **controller** method runs.
7. The controller returns a **response** (a Blade view, a redirect or JSON).
8. The response travels back out through the middleware and is sent to the browser.

## Git: fetch / pull / clone / merge

| Command | What it does |
|---|---|
| `git clone <url>` | Copies a remote repository to your computer for the first time (all history + a working folder). |
| `git fetch` | Downloads new commits from the remote **without changing your files**, so you can look first (`git log origin/master`). |
| `git merge <branch>` | Combines another branch into the current one, sometimes creating a merge commit or conflicts to fix. |
| `git pull` | `git fetch` **+** `git merge` in one step: download and apply the remote changes to your branch. |

## HTML: `id` vs `class`, and GET vs POST

- **`id`** is **unique** on the page (one element). It's used for labels (`<label for="email">`), anchors (`#change-password`) and JavaScript (`document.getElementById('employees-table')`).
- **`class`** can be **shared** by many elements. It's used for styling and grouping (`class="btn btn-primary"`, `class="row-check"`).
- **GET** asks for data. Parameters are in the URL (`/employees?search=sales`); it can be bookmarked and should not change data.
- **POST** sends data in the request body to create or change something (login, saving an employee). In Laravel, forms also need `@csrf`. PUT and DELETE are sent as POST with `@method('PUT')`.

## MySQL: Primary Key, Foreign Key, Index, JOIN

- **Primary key (PK):** uniquely identifies each row, can't be NULL (`employees.id`).
- **Foreign key (FK):** a column that points to another table's PK and is checked by the database (`employees.department_id → departments.id`). The rules say what happens on delete: `CASCADE` (delete children too), `RESTRICT` (refuse), or `SET NULL`.
- **Index:** a sorted lookup structure that makes `WHERE`, `ORDER BY` and `JOIN` fast, like a book's index (`employees (last_name, first_name)`, `employees.deleted_at`). A **unique** index also forbids duplicates (`employees.email`).
- **JOIN:** combines rows from tables using a key:
  ```sql
  SELECT e.first_name, d.name AS department, b.name AS branch
  FROM employees e
  JOIN departments d ON d.id = e.department_id
  JOIN branches b ON b.id = d.branch_id;
  ```
  `INNER JOIN` keeps only matches. `LEFT JOIN` keeps every row from the left table, even without a match.

## Validation rules: `required` / `nullable` / `sometimes` / `filled`

| Rule | Meaning | Used in this project |
|---|---|---|
| `required` | The field must be present **and** not empty. | `first_name`, `department_id` when creating an employee. |
| `nullable` | The field may be empty or `null`, and the other rules are skipped then. | `phone`, `address`, `hire_date`, `skills`. |
| `sometimes` | Only validate the field **if it is in the request**. | Update: send only `salary` and the other fields are left alone (partial update). |
| `filled` | **If present**, it must not be empty (but it may be absent). | Update: `first_name` may be left out, but `first_name=""` is rejected. |

`sometimes` + `filled` together mean "optional, but if you send it, it must have a value".

## Why validation belongs in Form Requests

- **Thin controllers:** `store(StoreEmployeeRequest $request)` only runs when the data is already valid, so the controller just saves.
- **Reuse:** rules, `messages()` and `attributes()` live in one place. `StoreEmployeeRequest` and `UpdateEmployeeRequest` share a base `EmployeeRequest`.
- **Automatic behaviour:** on failure, Laravel redirects back with errors and old input (or returns JSON 422 for AJAX).
- **Easier to read and test:** all the rules for "create an employee" are in one small class.
- `$request->validated()` / `safe()` returns **only validated fields**, which protects against mass-assignment of unexpected input.

## Why normalization matters

Normalization means **storing each fact once** and linking tables with keys instead of copying text.

- **No inconsistent data:** with free text, "HR", "hr" and "Human Resources" were three different departments. With `department_id` there is exactly one row.
- **Update in one place:** renaming a department changes one row in `departments`, not hundreds of employees.
- **Integrity:** foreign keys stop orphan rows (an employee in a department that doesn't exist) and can protect data (`RESTRICT`).
- **Less storage and faster queries:** small integer keys and indexes instead of repeated strings.
- **Flexible structure:** companies → branches → departments lets new questions be answered ("everyone in the Beirut branch") without changing the employees table.
