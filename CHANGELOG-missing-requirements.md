# Changelog — Week 2 missing requirements

All changes are backwards-compatible with the existing database. The old department text is kept,
and auth, roles (`/admin`, `/hr`), DataTables, filters and bulk delete keep working.
Tests: **63 → 90 passing** (`php artisan test`).

## 1. Tables & relationships — *Database design, foreign keys, indexes, Eloquent relationships*

| Change | Why |
|---|---|
| New tables `companies`, `branches`, `departments` (migrations `2026_10_19_000001…3`) | Normalized hierarchy company → branch → department instead of free text. |
| FK rules: `branches.company_id` **cascade**, `departments.branch_id` **cascade**, `employees.department_id` **restrict** | Shows both delete behaviours: children follow their parent, but a department that still has employees can't be deleted. |
| Unique and extra indexes: `(company_id, name)`, `(branch_id, name)`, `departments.name`, `employees (last_name, first_name)` | Data integrity (no duplicate names in one parent) and faster search/sort. |
| `employees.department_id` added and **backfilled** from the old text (migration `…000005`); old column kept as `department_legacy` | Safe on the existing MySQL data: every employee gets the matching department under *Default Company → Head Office*. |
| `employee_profiles` table + `Employee::profile()` **hasOne** (`…000006`) | hasOne example (phone, address, hire date). `employee_id` is unique, so it's one-to-one. |
| `skills` + pivot `employee_skill` + `Employee::skills()` / `Skill::employees()` **belongsToMany** with `level` on the pivot (`…000004`, `…000007`) | Many-to-many example with extra pivot data. |
| Models `Company`, `Branch`, `Department`, `Skill`, `EmployeeProfile` with hasMany / belongsTo (+ `Branch::employees()` hasManyThrough) | Eloquent relationships on both sides. |
| Factories for every model, `EmployeeFactory::inDepartment()` and `withDetails()`; `OrganizationSeeder` (2 companies, 3 branches, 8 departments, 8 skills) | Test data and sample data. The seeder is safe to re-run and never touches employees. |
| Employee form: department **dropdown grouped by company · branch**, optional phone/address/hire date, skills checkboxes. Profile page shows branch, company, profile and skills. List shows the branch under the department. Dashboard department stats use `withCount`/`withAvg`. | The UI now uses the relationships instead of free text. |
| Search, filter and sort by department now go through the relationship (`whereHas`, `department_id`, sub-query on `departments.name`) | Same behaviour as before, on normalized data. |

## 2. Soft deletes — *SoftDeletes, restore, forceDelete*

| Change | Why |
|---|---|
| `employees.deleted_at` (indexed) + `SoftDeletes` on `Employee` (`…000008`) | "Delete" now moves an employee to the trash instead of erasing it. |
| Employees list: **Active / Trash (n)** switch. In Trash: **Restore** and **Delete forever** per row | Trash, restore and force-delete in the list, as required. |
| Routes `POST employees/{id}/restore`, `DELETE employees/{id}/force` with `withTrashed()` and `can:employees.delete` | Only Admin/HR can use the trash. Force delete works only on trashed employees. |
| Delete / bulk delete messages now say "moved to the trash" | Clear feedback. |
| Unique email still counts trashed employees, with a custom message | Matches the database's unique index and explains what to do (restore). |

## 3. Validation — *Form Requests, custom messages/attributes, exists/sometimes/filled/nullable*

| Change | Why |
|---|---|
| New `app/Http/Requests/EmployeeRequest.php` base class with `messages()` and `attributes()`, shared by Store/Update | Custom messages and readable field names in one place. |
| `exists:departments,id`, `skills.*` → `exists:skills,id` | The id must be a real row. |
| `nullable` on phone, address, hire date, skills | Optional fields stored as NULL. |
| Update rules use `sometimes` + `filled` | Partial updates (e.g. only the salary) are allowed, but a field that is sent can't be empty. |
| Hidden `skills=""` in the form | Unticking every skill really removes them. A partial update without `skills` keeps them. |

## 4. ER diagram — *Database design documentation*

`docs/er-diagram.md` + `docs/er-diagram.svg`: the ER diagram as an image (shows in any Markdown viewer) and as Mermaid code (left-to-right, teal theme, FK delete rules on each key, with a symbol legend), plus a table explaining every relationship (type, FK, on-delete rule, Eloquent methods, reason), the indexes, soft deletes and the migration from the old schema.

## 5. Theory quiz — *PHP, Laravel, Git, HTML, MySQL, validation, normalization*

`docs/quiz-answers.md`: answers for = / == / ===, namespace, Composer, MVC, Route, Controller, Blade, Migration, Artisan, Middleware, Request Lifecycle, git fetch/pull/clone/merge, HTML id/class and GET/POST, MySQL PK/FK/Index/JOIN, required/nullable/sometimes/filled, Form Requests, and normalization.

## Tests

- Existing tests updated for the department FK (`inDepartment('Sales')`, `department_id`) and soft deletes (`assertSoftDeleted`).
- New: `RelationshipsTest` (every relationship, restrict, cascade, unique hasOne), `DepartmentBackfillMigrationTest` (runs the real migration on old-style rows, and the rollback), `SoftDeleteTest` (trash, restore, force delete, permissions, unique email, dashboard counts), and `EmployeeValidationTest` (custom messages/attributes, exists, nullable, sometimes, filled, skills sync).

## How to apply on an existing database

```bash
# back up first (XAMPP MySQL on port 3307)
C:\xampp\mysql\bin\mysqldump -P 3307 -u root employee_crud > storage/app/private/employee_crud-before-week2.sql   (git-ignored)
php artisan migrate
php artisan db:seed --class=OrganizationSeeder   # optional: sample companies, branches, departments, skills
```

## 6. Manage Departments — *CRUD, Form Request, soft deletes, eager loading (N+1)*

| Change | Why |
|---|---|
| Migration `2026_10_19_000009`: `departments.code` (unique), `description` (nullable), `deleted_at`. Existing departments get a generated code such as `ENG-1` before the unique index is added | Safe on the existing data. Every department has a short unique code. |
| `SoftDeletes` on `Department` | Deleting a department keeps the row (`deleted_at`). Lists and dropdowns skip it automatically. |
| `DepartmentController` (index, create, store, edit, update, destroy) + `DepartmentRequest` (name required and unique per branch, code required/unique/`alpha_dash` and saved in capitals, branch `exists`, description `nullable`, custom messages and attributes) | Full CRUD with validation in a Form Request. |
| Index: `Department::with('branch.company')->withCount('employees')`, paginated, search by name or code | **N+1 fix**: the branch, company and employee count come from a fixed number of queries, whatever the number of rows (checked by a test). |
| Delete is refused while the department still has active employees | Same protection as the database's `restrictOnDelete`, which a soft delete would not trigger. |
| Views `admin/departments/{index,_form,create,edit}` (Bootstrap): Name, Code, Branch (+ company), Employees count (links to the filtered employee list), ⋮ Edit / Delete | The required list and forms. |
| Permission `departments.manage` (Admin, HR); routes `/admin/departments` and `/hr/departments`; sidebar link **Manage Departments** | Only Admin/HR can see and use it. |
| Tests: `DepartmentTest` (13 tests); `RelationshipsTest` now uses `forceDelete()` to check the database RESTRICT rule; the backfill test rolls back 5 migrations | 103 tests passing. |

## 7. Employee role (replaces Viewer) — *one-to-one relationship, authorization*

| Change | Why |
|---|---|
| Migration `2026_10_19_000010`: `users.employee_id` (FK to employees, **unique**, **SET NULL** on delete); existing `viewer` users become `employee`; default role is now `employee` | A login can belong to one employee record (one-to-one: `User::employee()` belongsTo, `Employee::user()` hasOne). |
| Role **Employee** with the new permission `profile.view-own` (Viewer removed). HR can manage Employee logins | Employees log in and see **only their own** record. |
| **My Profile** page (`/my-profile`): personal info, department, branch, company, hire date, salary and skills; no department average and no colleagues | Privacy: an employee never sees other employees' data. It's their start page after login. |
| Sidebar: **My Profile** (Employee role, or staff whose login is linked); **Manage Employees** only with `employees.view` | Each role only sees the menu items it can use. |
| Manage Users: **Linked employee** picker (required for the Employee role, one login per employee, already-linked employees not offered); the users table shows the linked employee | Admin/HR create employee logins. |
| Tests: `EmployeeRoleTest` (9) plus updated role tests | 116 tests passing. |
