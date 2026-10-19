<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employees') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')
    <style>
        /* ---- Theme (teal) ---- */
        :root {
            --brand: #0f7b6c;
            --brand-dark: #0b6357;
            --brand-light: #e6f4f1;
            --bs-primary: #0f7b6c;
            --bs-primary-rgb: 15, 123, 108;
            --bs-primary-text-emphasis: #06433b;
            --bs-primary-bg-subtle: #d4ede8;
            --bs-primary-border-subtle: #a9dbd1;
            --bs-link-color: #0f7b6c;
            --bs-link-color-rgb: 15, 123, 108;
            --bs-link-hover-color: #0b6357;
            --bs-link-hover-color-rgb: 11, 99, 87;
            --bs-body-font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --bs-body-font-size: .875rem;
            --bs-border-radius: .5rem;
        }
        body { background: #f3f6f6; }
        .btn-primary {
            --bs-btn-bg: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-bg: var(--brand-dark); --bs-btn-hover-border-color: var(--brand-dark);
            --bs-btn-active-bg: var(--brand-dark); --bs-btn-active-border-color: var(--brand-dark);
            --bs-btn-disabled-bg: var(--brand); --bs-btn-disabled-border-color: var(--brand);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-bg: var(--brand); --bs-btn-hover-border-color: var(--brand);
            --bs-btn-active-bg: var(--brand-dark); --bs-btn-active-border-color: var(--brand-dark);
        }
        .form-control:focus, .form-select:focus, .form-check-input:focus {
            border-color: #6fbfb2; box-shadow: 0 0 0 .2rem rgba(15, 123, 108, .15);
        }
        .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }
        .form-check-input:indeterminate { background-color: var(--brand); border-color: var(--brand); }
        .page-link { color: var(--brand); }
        .progress, .progress-stacked { --bs-progress-bar-bg: var(--brand); }
        .active > .page-link, .page-link.active { background-color: var(--brand); border-color: var(--brand); }

        /* ---- App shell: sidebar on the left, top bar + content on the right ---- */
        :root { --sidebar-width: 264px; }
        .brand-mark {
            width: 34px; height: 34px; border-radius: 10px; background: var(--brand); color: #fff;
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sidebar {
            position: fixed; inset: 0 auto 0 0; width: var(--sidebar-width); z-index: 1040;
            background: #fff; border-right: 1px solid #e3e8e8; display: flex; flex-direction: column;
            transition: transform .25s ease;
        }
        .app-main { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left .25s ease; }
        body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
        body.sidebar-collapsed .app-main { margin-left: 0; }

        /* App name at the top of the sidebar */
        .sidebar-brand {
            display: flex; align-items: center; gap: .65rem; padding: 1.1rem 1.25rem; color: #111;
            text-decoration: none; border-bottom: 1px solid #eef1f1; margin-bottom: .25rem;
        }
        .sidebar-brand:hover { color: var(--brand); }

        /* Profile button on the right of the top bar (after the date) */
        .profile-chip {
            display: flex; align-items: center; gap: .6rem; border: 1px solid #e3e8e8; background: #fff;
            padding: .25rem .75rem .25rem .3rem; border-radius: 999px; text-align: left;
        }
        .profile-chip:hover, .profile-chip[aria-expanded="true"] { background: #f3f6f6; border-color: #cfd8d8; }
        .profile-chip:focus { outline: 0; }
        .profile-chip:focus-visible { box-shadow: 0 0 0 .2rem rgba(15, 123, 108, .25); }
        .profile-avatar { width: 34px; height: 34px; font-size: 13px; background: var(--brand); color: #fff; }
        .profile-text { display: flex; flex-direction: column; min-width: 0; max-width: 11rem; line-height: 1.15; }
        .profile-name { font-weight: 700; font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #111; }
        .profile-role { font-size: .7rem; color: var(--brand); font-weight: 600; }
        .profile-chevron, .submenu-chevron { font-size: .75rem; opacity: .7; transition: transform .2s ease; }
        .profile-chip[aria-expanded="true"] .profile-chevron, .submenu-toggle:not(.collapsed) .submenu-chevron { transform: rotate(180deg); }

        /* Profile menu (drops down from the profile button) with the "My account & password" submenu */
        .profile-menu { width: 17rem; padding: .4rem; border-radius: .7rem; margin-top: .4rem !important; }
        .profile-meta { font-size: .72rem; color: #6b7280; padding: .4rem .75rem .55rem; border-bottom: 1px solid #eef1f1; margin-bottom: .3rem; }
        .profile-menu .side-link { margin-bottom: 0; font-size: .82rem; gap: .55rem; padding: .55rem .6rem; white-space: nowrap; }
        .profile-menu .side-link.active::before { display: none; }
        .submenu { margin: .15rem 0 .25rem 1.6rem; padding-left: .75rem; border-left: 2px solid #e3ecea; }
        .submenu-link {
            display: block; padding: .4rem .6rem; border-radius: .45rem; font-size: .8rem; color: #4b5563; text-decoration: none;
        }
        .submenu-link:hover { background: #eef4f3; color: #111; }
        .submenu-link.active { color: var(--brand); font-weight: 600; }

        /* Menu */
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 0 .75rem 1rem; }
        .nav-section {
            font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: #9aa5a5; padding: 1rem .75rem .4rem;
        }
        .side-link {
            display: flex; align-items: center; gap: .7rem; width: 100%; padding: .6rem .75rem; margin-bottom: 2px;
            border: 0; background: none; border-radius: .55rem; color: #374151; font-weight: 500; font-size: .85rem;
            text-decoration: none; position: relative; text-align: left;
        }
        .side-link i { font-size: 1.05rem; color: #6b7280; width: 1.2rem; text-align: center; }
        .side-link:hover { background: #f3f6f6; color: #111; }
        .side-link.active { background: var(--brand-light); color: var(--brand); font-weight: 600; }
        .side-link.active i { color: var(--brand); }
        .side-link.active::before {
            content: ''; position: absolute; left: -.75rem; top: .45rem; bottom: .45rem; width: 4px;
            border-radius: 0 4px 4px 0; background: var(--brand);
        }
        .side-link-danger, .side-link-danger i { color: #c0264b; }
        .side-link-danger:hover { background: #fff1f3; color: #a51d3e; }

        /* Top bar + content */
        .topbar {
            position: sticky; top: 0; z-index: 1030; display: flex; align-items: center; gap: .75rem;
            padding: .6rem 1.5rem; background: rgba(255, 255, 255, .92); backdrop-filter: blur(6px);
            border-bottom: 1px solid #e6ebeb;
        }
        .app-content { padding: 1.5rem 1.5rem 3rem; }
        .chip {
            display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem; border-radius: 999px;
            background: #fff; border: 1px solid #e3e8e8; font-weight: 600; font-size: .8rem;
        }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--brand); }

        /* Small screens: sidebar slides in over the page */
        .sidebar-backdrop { display: none; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .app-main { margin-left: 0 !important; }
            body.sidebar-open .sidebar { transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, .2); }
            body.sidebar-open .sidebar-backdrop { display: block; position: fixed; inset: 0; z-index: 1035; background: rgba(17, 24, 39, .35); }
            .app-content { padding: 1rem 1rem 2.5rem; }
            .topbar { padding: .6rem 1rem; }
        }
        .stat-card { padding: 1.1rem; display: flex; align-items: center; gap: .9rem; height: 100%; }
        .stat-icon { width: 44px; height: 44px; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
        .stat-label { font-size: .75rem; color: #6b7280; font-weight: 500; }

        /* Cards / panels */
        .panel { background: #fff; border: 1px solid #e3e8e8; border-radius: .6rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1.1rem; border-bottom: 1px solid #e9eded; }
        .panel-title { font-size: .78rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; margin: 0; color: #111; }
        .panel-body { padding: 1.1rem; }

        /* Form controls (filters and forms) */
        .form-label { font-weight: 600; font-size: .8rem; color: #1f2937; margin-bottom: .35rem; }
        .form-control, .form-select { border-color: #dfe5e5; border-radius: .55rem; min-height: 2.35rem; font-size: .82rem; }
        .form-select.select-divider {
            background-image:
                url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23111' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.4' d='m3 6 5 5 5-5'/%3e%3c/svg%3e"),
                linear-gradient(#dfe5e5, #dfe5e5);
            background-size: 12px 12px, 1px 100%;
            background-position: right 12px center, right 36px center;
            padding-right: 3.2rem;
        }
        .btn-generate {
            background: linear-gradient(180deg, #13907f, #0d6e61); color: #fff; border: 0; font-weight: 600;
            padding: .55rem 1.1rem; border-radius: .55rem; box-shadow: 0 2px 6px rgba(15, 123, 108, .35);
        }
        .btn-generate:hover, .btn-generate:focus { background: linear-gradient(180deg, #11806f, #0a5c51); color: #fff; }
        .btn-clear { background: #fff1f3; color: #c0264b; border: 1px solid #f6c6d1; font-weight: 600; padding: .5rem 1rem; border-radius: .55rem; }
        .btn-clear:hover, .btn-clear:focus { background: #ffe3e9; color: #a51d3e; border-color: #f0a9ba; }
        .btn-soft { background: #fff; border: 1px solid #dfe5e5; color: #111; font-weight: 600; font-size: .78rem; border-radius: .45rem; }
        .btn-soft:hover, .btn-soft:focus, .btn-soft.show { background: #f3f6f6; border-color: #cfd8d8; color: #111; }

        .btn-icon { width: 2rem; height: 2rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        /* Row actions: one "⋮" button that opens View / Edit / Delete */
        .actions-toggle { color: #4b5563; border-radius: 50%; }
        .actions-toggle:hover, .actions-toggle[aria-expanded="true"] { background: var(--brand-light); color: var(--brand); }
        .actions-menu .dropdown-menu { min-width: 11rem; padding: .35rem; border: 1px solid #e3e8e8; border-radius: .6rem; box-shadow: 0 8px 24px rgba(16, 24, 40, .12); }
        .actions-menu .dropdown-item { display: flex; align-items: center; gap: .6rem; font-size: .82rem; padding: .45rem .7rem; border-radius: .4rem; }
        .actions-menu .dropdown-item i { width: 1rem; text-align: center; }
        .actions-menu .dropdown-item:active { background: var(--brand-light); color: var(--brand); }
        .actions-menu .dropdown-item.text-danger:hover { background: #fff1f3; }
        .actions-menu .dropdown-header { font-size: .7rem; padding: .35rem .7rem; }
        .tabular { font-variant-numeric: tabular-nums; }
        .avatar { display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; flex-shrink: 0; }
        .info-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); }
        .form-section-title { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); font-weight: 600; }
    </style>
</head>
<body>
    @auth
    @php($me = auth()->user())
    <aside class="sidebar" id="sidebar" aria-label="Main menu">
        {{-- App name at the top of the sidebar --}}
        <a href="{{ route('home') }}" class="sidebar-brand">
            <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
            <span class="lh-sm">
                <span class="d-block fw-bold">Employee Management</span>
                <span class="d-block text-muted" style="font-size: .7rem">{{ $me->panel_slug ? $me->panel_label.' panel' : 'Staff records' }}</span>
            </span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section">Menu</div>
            @can('users.manage')
                <a href="{{ route('admin.dashboard') }}" class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>
            @endcan
            {{-- Own employee record: always for the Employee role, for others only when their login is linked --}}
            @if ($me->can('profile.view-own') && ($me->employee_id || $me->cannot('employees.view')))
                @cannot('users.manage')
                    <a href="{{ route('employee.dashboard') }}" class="side-link {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </a>
                @endcannot
                <a href="{{ route('profile.show') }}" class="side-link {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                    <i class="bi bi-person-vcard"></i> My Profile
                </a>
            @endif
            @can('employees.view')
                <a href="{{ route('employees.index') }}" class="side-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Manage Employees
                </a>
            @endcan
            @can('departments.manage')
                <a href="{{ route('admin.departments.index') }}" class="side-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> Manage Departments
                </a>
            @endcan
            @can('users.manage')
                <a href="{{ route('admin.users.index') }}" class="side-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Manage Users
                </a>
            @endcan
        </nav>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="btn btn-soft btn-icon" id="sidebar-toggle" aria-label="Toggle menu" aria-controls="sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="fw-semibold text-truncate">@yield('title', 'Employees')</div>

            {{-- Right side: today's date, then the profile menu --}}
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="chip d-none d-md-inline-flex"><span class="dot"></span> {{ now()->format('l, d M Y') }}</span>

                <div class="dropdown">
                    <button type="button" class="profile-chip" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Profile menu">
                        <span class="avatar profile-avatar">{{ $me->initials }}</span>
                        <span class="profile-text d-none d-sm-flex">
                            <span class="profile-name">{{ $me->name }}</span>
                            <span class="profile-role">{{ $me->role_label }}</span>
                        </span>
                        <i class="bi bi-chevron-down profile-chevron"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end profile-menu shadow border-0">
                        <div class="profile-meta">
                            <div class="fw-semibold text-body">{{ $me->name }}</div>
                            <div class="text-truncate">{{ '@'.$me->username }} · {{ $me->email }}</div>
                        </div>

                        @can('users.manage')
                            <a href="{{ route('admin.users.create') }}" class="side-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                                <i class="bi bi-person-add"></i> Add User
                            </a>
                        @endcan

                        {{-- Submenu --}}
                        @php($accountOpen = request()->routeIs('account.*'))
                        <button type="button" class="side-link submenu-toggle {{ $accountOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                                data-bs-target="#account-submenu" aria-expanded="{{ $accountOpen ? 'true' : 'false' }}" aria-controls="account-submenu">
                            <i class="bi bi-person-gear"></i> My account &amp; password
                            <i class="bi bi-chevron-down submenu-chevron ms-auto"></i>
                        </button>
                        <div class="collapse {{ $accountOpen ? 'show' : '' }}" id="account-submenu">
                            <div class="submenu">
                                <a href="{{ route('account.edit') }}" class="submenu-link {{ $accountOpen ? 'active' : '' }}">My account</a>
                                <a href="{{ route('account.edit') }}#change-password" class="submenu-link">Change password</a>
                            </div>
                        </div>

                        <hr class="dropdown-divider my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="side-link side-link-danger"><i class="bi bi-box-arrow-right"></i> Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
            @include('layouts._alerts')
            @yield('content')
        </main>
    </div>
    @else
        @hasSection('fullpage')
            {{-- Full-screen guest page (the login page): it places the alerts itself --}}
            @yield('content')
        @else
            <main class="container">
                @include('layouts._alerts')
                @yield('content')
            </main>
        @endif
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @auth
    <script>
        // Sidebar: hide/show on desktop (remembered), slide in over the page on small screens.
        (() => {
            const body = document.body;
            const mobile = () => window.matchMedia('(max-width: 991.98px)').matches;
            try { if (localStorage.getItem('sidebar-collapsed') === '1') body.classList.add('sidebar-collapsed'); } catch (e) {}

            document.getElementById('sidebar-toggle').addEventListener('click', () => {
                if (mobile()) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    try { localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0'); } catch (e) {}
                }
            });
            document.getElementById('sidebar-backdrop').addEventListener('click', () => body.classList.remove('sidebar-open'));
        })();
    </script>
    @endauth
    <script>
        /**
         * "⋮" row-actions menu used by the DataTables (employees, users).
         * The menu is positioned "fixed" so it is never cut off by the table's sideways scroll.
         */
        window.actionsMenu = (name, items) => {
            const label = String(name ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            return `<div class="dropdown actions-menu d-inline-block">
                <button type="button" class="btn btn-sm btn-icon actions-toggle" data-bs-toggle="dropdown"
                        data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Actions for ${label}">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">${items}</ul>
            </div>`;
        };
    </script>
    @stack('scripts')
</body>
</html>
