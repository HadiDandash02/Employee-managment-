<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Light or dark: chosen with the 🌙/☀️ button, remembered per browser (applied before drawing, so no flash).
        try {
            if (localStorage.getItem("theme") === "dark") {
                document.documentElement.setAttribute("data-theme", "dark");
                document.documentElement.setAttribute("data-bs-theme", "dark");
            }
        } catch (e) {}
    </script>
    <title>@yield('title', 'Employees') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')
    <style>
        /* ================= Theme: one set of colour tokens for the whole system =================
           Change the look here: --brand* is the main colour (indigo), the rest are neutral greys. */
        :root {
            --brand: #4f46e5;              /* indigo 600: main actions, links, active items */
            --brand-dark: #4338ca;         /* hover */
            --brand-darker: #3730a3;       /* pressed */
            --brand-light: #eef2ff;        /* soft backgrounds (selected, active) */
            --brand-border: #c7d2fe;
            --ink: #0f172a;                /* main text */
            --ink-soft: #475569;           /* secondary text */
            --muted: #64748b;              /* hints, labels */
            --line: #e2e8f0;               /* borders */
            --line-soft: #eef2f6;          /* row separators */
            --surface: #ffffff;            /* cards */
            --canvas: #f5f7fb;             /* page background */
            --hover: #f8fafc;              /* row / item hover */
            --sidebar-bg: #ffffff;         /* light sidebar */
            --sidebar-text: #334155;
            --sidebar-muted: #94a3b8;
            --radius: .75rem;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, .05);
            --shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 1px 2px rgba(15, 23, 42, .04);
            --shadow-lg: 0 12px 32px rgba(15, 23, 42, .12);
            --focus-ring: 0 0 0 .2rem rgba(79, 70, 229, .2);

            /* Bootstrap uses these: text-bg-primary, links, badges, … follow the brand */
            --bs-primary: #4f46e5;
            --bs-primary-rgb: 79, 70, 229;
            --bs-primary-text-emphasis: #3730a3;
            --bs-primary-bg-subtle: #eef2ff;
            --bs-primary-border-subtle: #c7d2fe;
            --bs-link-color: #4f46e5;
            --bs-link-color-rgb: 79, 70, 229;
            --bs-link-hover-color: #4338ca;
            --bs-link-hover-color-rgb: 67, 56, 202;
            --bs-body-font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --bs-body-font-size: .875rem;
            --bs-body-color: #0f172a;
            --bs-secondary-color: #64748b;
            --bs-border-color: #e2e8f0;
            --bs-border-radius: .6rem;
        }
        body { background: var(--canvas); color: var(--ink); -webkit-font-smoothing: antialiased; }
        h1, .h1, h2, .h2, h3, .h3, h4, .h4 { letter-spacing: -.015em; color: var(--ink); }
        a { text-underline-offset: 2px; }

        /* ---- Buttons: one standard for the whole system (use the x-button component: resources/views/components/button.blade.php) ----
           primary = the main action · secondary = Cancel/Back/Clear · success · info · warning · danger · danger-outline */
        .btn {
            --bs-btn-font-weight: 600; --bs-btn-font-size: .82rem; --bs-btn-line-height: 1.25;
            --bs-btn-padding-x: 1rem; --bs-btn-padding-y: .55rem; --bs-btn-border-radius: .6rem;
            --bs-btn-focus-box-shadow: var(--focus-ring);
            --bs-btn-disabled-opacity: .55;
            white-space: nowrap; /* icon and text always on one line */
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .05s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-sm { --bs-btn-font-size: .76rem; --bs-btn-padding-x: .75rem; --bs-btn-padding-y: .34rem; --bs-btn-border-radius: .5rem; }
        .btn-primary, .btn-success, .btn-info, .btn-warning, .btn-danger { box-shadow: 0 1px 2px rgba(15, 23, 42, .12); }
        .btn-primary {
            --bs-btn-color: #fff; --bs-btn-bg: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: var(--brand-dark); --bs-btn-hover-border-color: var(--brand-dark);
            --bs-btn-active-color: #fff; --bs-btn-active-bg: var(--brand-darker); --bs-btn-active-border-color: var(--brand-darker);
            --bs-btn-disabled-color: #fff; --bs-btn-disabled-bg: var(--brand); --bs-btn-disabled-border-color: var(--brand);
        }
        .btn-soft {
            --bs-btn-color: #1e293b; --bs-btn-bg: #fff; --bs-btn-border-color: var(--line);
            --bs-btn-hover-color: var(--ink); --bs-btn-hover-bg: var(--hover); --bs-btn-hover-border-color: #cbd5e1;
            --bs-btn-active-color: var(--brand-darker); --bs-btn-active-bg: var(--brand-light); --bs-btn-active-border-color: var(--brand-border);
            --bs-btn-disabled-color: var(--muted); --bs-btn-disabled-bg: #fff; --bs-btn-disabled-border-color: var(--line);
            box-shadow: var(--shadow-sm);
        }
        .btn-success {
            --bs-btn-color: #fff; --bs-btn-bg: #16a34a; --bs-btn-border-color: #16a34a;
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #15803d; --bs-btn-hover-border-color: #15803d;
            --bs-btn-active-color: #fff; --bs-btn-active-bg: #166534; --bs-btn-active-border-color: #166534;
            --bs-btn-disabled-color: #fff; --bs-btn-disabled-bg: #16a34a; --bs-btn-disabled-border-color: #16a34a;
        }
        .btn-info {
            --bs-btn-color: #fff; --bs-btn-bg: #2563eb; --bs-btn-border-color: #2563eb;
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #1d4ed8; --bs-btn-hover-border-color: #1d4ed8;
            --bs-btn-active-color: #fff; --bs-btn-active-bg: #1e40af; --bs-btn-active-border-color: #1e40af;
            --bs-btn-disabled-color: #fff; --bs-btn-disabled-bg: #2563eb; --bs-btn-disabled-border-color: #2563eb;
        }
        .btn-warning {
            --bs-btn-color: #451a03; --bs-btn-bg: #fbbf24; --bs-btn-border-color: #f59e0b;
            --bs-btn-hover-color: #451a03; --bs-btn-hover-bg: #f59e0b; --bs-btn-hover-border-color: #d97706;
            --bs-btn-active-color: #451a03; --bs-btn-active-bg: #d97706; --bs-btn-active-border-color: #d97706;
            --bs-btn-disabled-color: #451a03; --bs-btn-disabled-bg: #fbbf24; --bs-btn-disabled-border-color: #fbbf24;
        }
        .btn-danger {
            --bs-btn-color: #fff; --bs-btn-bg: #dc2626; --bs-btn-border-color: #dc2626;
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #b91c1c; --bs-btn-hover-border-color: #b91c1c;
            --bs-btn-active-color: #fff; --bs-btn-active-bg: #991b1b; --bs-btn-active-border-color: #991b1b;
            --bs-btn-disabled-color: #fff; --bs-btn-disabled-bg: #dc2626; --bs-btn-disabled-border-color: #dc2626;
        }
        .btn-outline-danger {
            --bs-btn-color: #dc2626; --bs-btn-bg: #fff; --bs-btn-border-color: #fecaca;
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #dc2626; --bs-btn-hover-border-color: #dc2626;
            --bs-btn-active-color: #fff; --bs-btn-active-bg: #b91c1c; --bs-btn-active-border-color: #b91c1c;
        }
        .btn-outline-primary {
            --bs-btn-color: var(--brand); --bs-btn-border-color: var(--brand-border);
            --bs-btn-hover-color: #fff; --bs-btn-hover-bg: var(--brand); --bs-btn-hover-border-color: var(--brand);
            --bs-btn-active-color: #fff; --bs-btn-active-bg: var(--brand-dark); --bs-btn-active-border-color: var(--brand-dark);
        }
        .btn-check:checked + .btn-outline-primary { color: #fff; background: var(--brand); border-color: var(--brand); }

        /* Form focus, checkboxes, pagination, progress: all in the brand colour */
        .form-control:focus, .form-select:focus, .form-check-input:focus { border-color: #a5b4fc; box-shadow: var(--focus-ring); }
        .form-check-input:checked, .form-check-input[type=checkbox]:indeterminate { background-color: var(--brand); border-color: var(--brand); }
        .page-link { color: var(--ink-soft); border-color: var(--line); }
        .page-link:hover { color: var(--brand); background: var(--brand-light); border-color: var(--line); }
        .active > .page-link, .page-link.active { background-color: var(--brand); border-color: var(--brand); color: #fff; }
        .progress, .progress-stacked { --bs-progress-bar-bg: var(--brand); --bs-progress-bg: #e2e8f0; }
        .badge { font-weight: 600; letter-spacing: .01em; }

        /* Role of a login (the x-role-badge component, and the users table): a pill with a coloured dot */
        .role-badge {
            display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .7rem .3rem .6rem; border-radius: 999px;
            font-size: .74rem; font-weight: 600; line-height: 1.1; white-space: nowrap; border: 1px solid transparent;
            color: #475569; background: #f1f5f9; border-color: #e2e8f0;
        }
        .role-badge::before { content: ""; width: .45rem; height: .45rem; border-radius: 50%; background: currentColor; flex-shrink: 0; }
        .role-admin { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        .role-hr { color: #b45309; background: #fffbeb; border-color: #fde68a; }
        .role-manager { color: #4338ca; background: #eef2ff; border-color: #c7d2fe; }
        .role-employee { color: #0f766e; background: #f0fdfa; border-color: #99f6e4; }

        /* Status tabs (leave list): the theme colour, not Bootstrap blue */
        .nav-pills { --bs-nav-pills-link-active-bg: var(--brand); --bs-nav-link-color: var(--ink-soft); --bs-nav-link-hover-color: var(--brand); --bs-nav-link-font-weight: 600; }
        .breadcrumb { --bs-breadcrumb-divider-color: #94a3b8; --bs-breadcrumb-item-active-color: var(--muted); font-size: .8rem; }
        .breadcrumb a { color: var(--ink-soft); text-decoration: none; }
        .breadcrumb a:hover { color: var(--brand); }
        .alert { border-radius: var(--radius); border-width: 1px; box-shadow: var(--shadow-sm); }
        .dropdown-menu { border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow-lg); }
        .dropdown-item { border-radius: .45rem; }
        .dropdown-item:active { background: var(--brand-light); color: var(--brand-darker); }

        /* ---- App shell: light sidebar on the left, top bar + content on the right ---- */
        :root { --sidebar-width: 268px; }
        .brand-mark {
            width: 36px; height: 36px; border-radius: 10px; color: #fff;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 55%, #7c3aed 100%);
            box-shadow: 0 4px 12px rgba(79, 70, 229, .35);
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sidebar {
            position: fixed; inset: 0 auto 0 0; width: var(--sidebar-width); z-index: 1040;
            background: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column;
            border-right: 1px solid var(--line); box-shadow: 1px 0 0 rgba(15, 23, 42, .02);
            transition: transform .25s ease;
        }
        .app-main { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left .25s ease; }
        body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
        body.sidebar-collapsed .app-main { margin-left: 0; }

        /* App name at the top of the sidebar */
        .sidebar-brand {
            display: flex; align-items: center; gap: .7rem; padding: 1.25rem 1.25rem 1.1rem; color: var(--ink);
            text-decoration: none; border-bottom: 1px solid var(--line-soft); margin-bottom: .35rem;
        }
        .sidebar-brand:hover { color: var(--brand); }

        /* Menu (in the sidebar) */
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 0 .85rem 1rem; }
        .nav-section {
            font-size: .66rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
            color: var(--sidebar-muted); padding: 1.1rem .75rem .5rem;
        }
        .side-link {
            display: flex; align-items: center; gap: .75rem; width: 100%; padding: .6rem .8rem; margin-bottom: 3px;
            border: 0; background: none; border-radius: .6rem; color: #374151; font-weight: 500; font-size: .85rem;
            text-decoration: none; position: relative; text-align: left; transition: background-color .15s ease, color .15s ease;
        }
        .side-link i { font-size: 1.05rem; color: #64748b; width: 1.2rem; text-align: center; }
        .side-link:hover { background: var(--hover); color: var(--ink); }
        .sidebar .side-link { color: var(--sidebar-text); }
        .sidebar .side-link i { color: #94a3b8; }
        .sidebar .side-link:hover { background: var(--hover); color: var(--ink); }
        .sidebar .side-link:hover i { color: var(--brand); }
        .sidebar .side-link.active { background: var(--brand-light); color: var(--brand-darker); font-weight: 600; }
        .sidebar .side-link.active i { color: var(--brand); }
        .sidebar .side-link.active::before {
            content: ''; position: absolute; left: -.85rem; top: .5rem; bottom: .5rem; width: 3px;
            border-radius: 0 3px 3px 0; background: var(--brand);
        }
        .side-link-danger, .side-link-danger i { color: #dc2626 !important; }
        .side-link-danger:hover { background: #fef2f2 !important; color: #b91c1c !important; }

        /* Profile button on the right of the top bar (after the date) */
        .profile-chip {
            display: flex; align-items: center; gap: .6rem; border: 1px solid var(--line); background: #fff;
            padding: .25rem .75rem .25rem .3rem; border-radius: 999px; text-align: left; box-shadow: var(--shadow-sm);
        }
        .profile-chip:hover, .profile-chip[aria-expanded="true"] { background: var(--hover); border-color: #cbd5e1; }
        .profile-chip:focus { outline: 0; }
        .profile-chip:focus-visible { box-shadow: var(--focus-ring); }
        .profile-avatar { width: 34px; height: 34px; font-size: 13px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; }
        .profile-text { display: flex; flex-direction: column; min-width: 0; max-width: 11rem; line-height: 1.15; }
        .profile-name { font-weight: 700; font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ink); }
        .profile-role { font-size: .7rem; color: var(--brand); font-weight: 600; }
        .profile-chevron, .submenu-chevron { font-size: .75rem; opacity: .7; transition: transform .2s ease; }
        .profile-chip[aria-expanded="true"] .profile-chevron, .submenu-toggle:not(.collapsed) .submenu-chevron { transform: rotate(180deg); }

        /* Profile menu (drops down from the profile button) with the "My account & password" submenu */
        .profile-menu { width: 17rem; padding: .4rem; border-radius: var(--radius); margin-top: .45rem !important; }
        .profile-meta { font-size: .72rem; color: var(--muted); padding: .4rem .75rem .55rem; border-bottom: 1px solid var(--line-soft); margin-bottom: .3rem; }
        .profile-menu .side-link { margin-bottom: 0; font-size: .82rem; gap: .55rem; padding: .55rem .6rem; white-space: nowrap; }
        .profile-menu .side-link.active { background: var(--brand-light); color: var(--brand-darker); }
        .profile-menu .side-link.active::before { display: none; }
        .submenu { margin: .15rem 0 .25rem 1.6rem; padding-left: .75rem; border-left: 2px solid var(--line); }
        .submenu-link {
            display: block; padding: .4rem .6rem; border-radius: .45rem; font-size: .8rem; color: var(--ink-soft); text-decoration: none;
        }
        .submenu-link:hover { background: var(--hover); color: var(--ink); }
        .submenu-link.active { color: var(--brand); font-weight: 600; }

        /* Notifications (bell dropdown and the Notifications page) */
        .notification-menu { width: 23rem; max-width: calc(100vw - 2rem); overflow: hidden; }
        .notification-list { max-height: 24rem; overflow-y: auto; }
        .notification-count { font-size: .62rem; box-shadow: 0 0 0 2px #fff; }
        .notification-icon { width: 2.1rem; height: 2.1rem; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notification-unread { background: #f5f7ff; box-shadow: inset 3px 0 0 var(--brand); }
        .notification-dot { width: .5rem; height: .5rem; border-radius: 50%; background: var(--brand); margin-top: .4rem; }
        .notification-message { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .notification-actions .btn-icon { width: 1.9rem; height: 1.9rem; font-size: .85rem; color: var(--ink-soft); }
        .notification-actions .btn-icon:hover { color: var(--brand); }
        .notification-actions .notification-delete:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .list-group { --bs-list-group-border-color: var(--line-soft); }
        .notification-row { transition: background-color .2s ease, opacity .25s ease, transform .25s ease; }
        .notification-row:hover { background: var(--hover); }
        .notification-row.notification-unread:hover { background: #eef1ff; }
        .notification-row.is-removing { opacity: 0; transform: translateX(12px); }
        .notification-row.is-selected { background: var(--brand-light); }
        .notification-link { color: inherit; }
        .notification-time { font-size: .72rem; color: #94a3b8; margin-top: .2rem; }
        .notification-menu-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; border-bottom: 1px solid var(--line-soft); background: #fbfcfe; }
        .notification-menu-footer { display: flex; align-items: center; justify-content: center; gap: .35rem; padding: .7rem; border-top: 1px solid var(--line-soft); font-size: .82rem; font-weight: 600; text-decoration: none; background: #fbfcfe; }
        .notification-menu-footer:hover { background: var(--brand-light); }
        .notification-empty { text-align: center; padding: 2rem 1rem; }
        .notification-empty-icon { width: 3rem; height: 3rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .6rem; background: #dcfce7; color: #16a34a; font-size: 1.4rem; }
        .notification-group { padding: .55rem 1rem; font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); background: #fbfcfe; border-bottom: 1px solid var(--line-soft); }
        .notification-tabs { gap: .25rem; }
        .notification-tabs .nav-link { color: var(--ink-soft); font-weight: 600; font-size: .85rem; padding: .4rem .8rem; border-radius: .55rem; }
        .notification-tabs .nav-link:hover { background: var(--hover); color: var(--ink); }
        .notification-tabs .nav-link.active { background: var(--brand-light); color: var(--brand-darker); }
        .tab-count { display: inline-block; min-width: 1.4rem; padding: .05rem .4rem; margin-left: .3rem; border-radius: 999px; background: #e2e8f0; color: var(--ink-soft); font-size: .7rem; text-align: center; }
        .tab-count-unread { background: var(--brand); color: #fff; }

        /* Small pop-up confirmations (bottom right), used by the notification script */
        .toast-stack { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 1090; display: flex; flex-direction: column; gap: .5rem; }
        .app-toast { display: flex; align-items: center; gap: .6rem; min-width: 16rem; max-width: 22rem; padding: .75rem 1rem; border-radius: var(--radius);
            background: var(--ink); color: #fff; font-size: .85rem; box-shadow: var(--shadow-lg); animation: toast-in .2s ease; }
        .app-toast i { color: #4ade80; font-size: 1.1rem; }
        .app-toast.is-error i { color: #f87171; }
        .app-toast.is-leaving { opacity: 0; transform: translateY(6px); transition: all .2s ease; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .min-w-0 { min-width: 0; }

        /* Top bar + content */
        .topbar {
            position: sticky; top: 0; z-index: 1030; display: flex; align-items: center; gap: .75rem;
            padding: .7rem 1.75rem; background: rgba(255, 255, 255, .85); backdrop-filter: saturate(180%) blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .app-content { padding: 1.75rem 1.75rem 3rem; max-width: 1600px; }
        .chip {
            display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .85rem; border-radius: 999px;
            background: #fff; border: 1px solid var(--line); font-weight: 600; font-size: .78rem; color: var(--ink-soft);
            box-shadow: var(--shadow-sm);
        }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, .18); }

        /* Small screens: sidebar slides in over the page */
        .sidebar-backdrop { display: none; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .app-main { margin-left: 0 !important; }
            body.sidebar-open .sidebar { transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, .3); }
            body.sidebar-open .sidebar-backdrop { display: block; position: fixed; inset: 0; z-index: 1035; background: rgba(15, 23, 42, .45); }
            .app-content { padding: 1rem 1rem 2.5rem; }
            .topbar { padding: .6rem 1rem; }
        }

        /* Stat cards (dashboards) */
        .stat-card { padding: 1.15rem 1.2rem; display: flex; align-items: center; gap: .95rem; height: 100%; }
        .stat-icon { width: 46px; height: 46px; border-radius: .75rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .stat-value { font-size: 1.45rem; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; color: var(--ink); }
        .stat-label { font-size: .75rem; color: var(--muted); font-weight: 500; }

        /* Cards / panels: every white box of the system */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line-soft); }
        .panel-title { font-size: .95rem; font-weight: 650; letter-spacing: -.005em; text-transform: none; margin: 0; color: var(--ink); }
        .panel-body { padding: 1.25rem; }
        .card { border-color: var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
        .card-header { background: #fff; border-bottom-color: var(--line-soft); }

        /* Tables: light header, quiet separators, soft hover (DataTables, leave list, departments) */
        .table { --bs-table-hover-bg: var(--hover); --bs-table-border-color: var(--line-soft); }
        table.app-table thead th, .leave-table thead th, .departments-table thead th {
            --bs-table-bg: #f8fafc; --bs-table-color: var(--ink-soft);
            background-color: #f8fafc !important; color: var(--ink-soft) !important;
            font-weight: 600; font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
            border: 0; border-bottom: 1px solid var(--line); padding: .8rem .85rem; vertical-align: middle;
        }
        .leave-table td, .departments-table td { padding: .85rem; vertical-align: middle; font-size: .85rem; border-bottom: 1px solid var(--line-soft); }
        .leave-table tbody tr:hover > td, .departments-table tbody tr:hover > td { background: var(--hover); }

        /* Form controls (filters and forms) */
        .form-label { font-weight: 600; font-size: .8rem; color: #1e293b; margin-bottom: .4rem; }
        .form-control, .form-select { border-color: var(--line); border-radius: .6rem; min-height: 2.45rem; font-size: .85rem; color: var(--ink); box-shadow: var(--shadow-sm); }
        .form-control::placeholder { color: #94a3b8; }
        .input-group-text { background: #f8fafc; border-color: var(--line); color: var(--muted); }
        .form-text { color: var(--muted); }
        .form-select.select-divider {
            background-image:
                url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e"),
                linear-gradient(#e2e8f0, #e2e8f0);
            background-size: 12px 12px, 1px 100%;
            background-position: right 12px center, right 36px center;
            padding-right: 3.2rem;
        }

        .btn-icon { width: 2.1rem; height: 2.1rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        /* Eye button after a password field: <button data-password-toggle="field-id"> */
        .password-toggle { border: 1px solid var(--line); border-left: 0; background: #fff; color: var(--muted); border-radius: 0 .6rem .6rem 0; }
        .password-toggle:hover, .password-toggle:focus { background: var(--hover); color: var(--brand); border-color: var(--line); }
        .is-invalid + .password-toggle { border-color: var(--bs-form-invalid-border-color); }
        /* Row actions: one "⋮" button that opens View / Edit / Delete */
        .actions-toggle { color: var(--ink-soft); border-radius: .5rem; }
        .actions-toggle:hover, .actions-toggle[aria-expanded="true"] { background: var(--brand-light); color: var(--brand); }
        .actions-menu .dropdown-menu { min-width: 11rem; padding: .35rem; }
        .actions-menu .dropdown-item { display: flex; align-items: center; gap: .6rem; font-size: .82rem; padding: .45rem .7rem; border-radius: .45rem; }
        .actions-menu .dropdown-item i { width: 1rem; text-align: center; }
        .actions-menu .dropdown-item.text-danger:hover { background: #fef2f2; }
        .actions-menu .dropdown-header { font-size: .7rem; padding: .35rem .7rem; }
        .tabular { font-variant-numeric: tabular-nums; }
        .avatar { display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; flex-shrink: 0; }
        .info-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); font-weight: 500; }
        .form-section-title { font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); font-weight: 600; }

        /* ================= Dark mode: the 🌙 button next to the bell sets <html data-theme="dark"> =================
           Only the colour tokens change; every component reads them. data-bs-theme="dark" (set at the same time)
           switches Bootstrap's own parts (inputs, dropdowns, list groups, subtle badges) to dark too. */
        [data-theme="dark"] {
            --brand-light: rgba(99, 102, 241, .16);
            --brand-border: rgba(129, 140, 248, .45);
            --ink: #e5e7eb;
            --ink-soft: #cbd5e1;
            --muted: #94a3b8;
            --line: #263244;
            --line-soft: #1e293b;
            --surface: #111827;
            --canvas: #0b1120;
            --hover: #1a2436;
            --sidebar-bg: #0f172a;
            --sidebar-text: #cbd5e1;
            --sidebar-muted: #64748b;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3);
            --shadow: 0 1px 3px rgba(0, 0, 0, .35);
            --shadow-lg: 0 16px 40px rgba(0, 0, 0, .5);
            --bs-body-bg: #0b1120;
            --bs-body-color: #e5e7eb;
            --bs-secondary-color: #94a3b8;
            --bs-border-color: #263244;
            --bs-link-color: #a5b4fc;
            --bs-link-color-rgb: 165, 180, 252;
            --bs-link-hover-color: #c7d2fe;
            --bs-primary-text-emphasis: #c7d2fe;
            color-scheme: dark;
        }
        [data-theme="dark"] .text-muted { color: var(--muted) !important; }
        [data-theme="dark"] .text-body, [data-theme="dark"] .stat-value, [data-theme="dark"] .profile-name { color: var(--ink) !important; }
        [data-theme="dark"] .btn-soft { --bs-btn-color: var(--ink); --bs-btn-bg: var(--surface); --bs-btn-hover-bg: var(--hover); --bs-btn-hover-color: #fff; --bs-btn-hover-border-color: #334155; --bs-btn-disabled-bg: var(--surface); --bs-btn-active-color: #c7d2fe; }
        [data-theme="dark"] .btn-outline-danger { --bs-btn-bg: transparent; --bs-btn-border-color: rgba(248, 113, 113, .45); --bs-btn-color: #f87171; }
        [data-theme="dark"] .profile-chip, [data-theme="dark"] .chip, [data-theme="dark"] .password-toggle { background: var(--surface); }
        [data-theme="dark"] .topbar { background: rgba(11, 17, 32, .85); }
        [data-theme="dark"] .sidebar .side-link:hover { color: #fff; }
        [data-theme="dark"] .sidebar .side-link.active { color: #c7d2fe; }
        [data-theme="dark"] .sidebar .side-link.active i { color: #a5b4fc; }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background-color: #0f172a; color: var(--ink); }
        [data-theme="dark"] .input-group-text, [data-theme="dark"] .card-header { background: #0f172a; }
        [data-theme="dark"] .form-label, [data-theme="dark"] h1, [data-theme="dark"] .h4 { color: var(--ink); }
        [data-theme="dark"] .form-select.select-divider {
            background-image:
                url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e"),
                linear-gradient(#263244, #263244);
        }
        [data-theme="dark"] table.app-table thead th, [data-theme="dark"] .leave-table thead th, [data-theme="dark"] .departments-table thead th {
            --bs-table-bg: #0f172a; background-color: #0f172a !important; color: var(--muted) !important;
        }
        [data-theme="dark"] table.app-table tbody td.col-actions { background-color: var(--surface); }
        [data-theme="dark"] .table { --bs-table-bg: transparent; --bs-table-color: var(--ink); }
        [data-theme="dark"] .notification-menu-header, [data-theme="dark"] .notification-menu-footer, [data-theme="dark"] .notification-group { background: #0f172a; }
        [data-theme="dark"] .notification-unread { background: rgba(99, 102, 241, .1); }
        [data-theme="dark"] .notification-row.notification-unread:hover { background: rgba(99, 102, 241, .16); }
        [data-theme="dark"] .notification-tabs .nav-link.active, [data-theme="dark"] .profile-role, [data-theme="dark"] .submenu-link.active { color: #c7d2fe; }
        [data-theme="dark"] .tab-count { background: #1e293b; color: var(--ink-soft); }
        [data-theme="dark"] .tab-count-unread { background: var(--brand); color: #fff; }
        [data-theme="dark"] .notification-empty-icon { background: rgba(34, 197, 94, .15); color: #4ade80; }
        [data-theme="dark"] .app-toast { background: #1e293b; border: 1px solid #334155; }
        [data-theme="dark"] .role-badge { color: #cbd5e1; background: rgba(148, 163, 184, .12); border-color: rgba(148, 163, 184, .3); }
        [data-theme="dark"] .role-admin { color: #fca5a5; background: rgba(239, 68, 68, .14); border-color: rgba(248, 113, 113, .38); }
        [data-theme="dark"] .role-hr { color: #fcd34d; background: rgba(245, 158, 11, .14); border-color: rgba(251, 191, 36, .38); }
        [data-theme="dark"] .role-manager { color: #c7d2fe; background: rgba(99, 102, 241, .2); border-color: rgba(129, 140, 248, .45); }
        [data-theme="dark"] .role-employee { color: #5eead4; background: rgba(20, 184, 166, .14); border-color: rgba(45, 212, 191, .38); }
        /* Bootstrap "subtle" badges (bg-primary-subtle …): my light values must not leak into dark mode */
        [data-theme="dark"] { --bs-primary-bg-subtle: rgba(99, 102, 241, .2); --bs-primary-border-subtle: rgba(129, 140, 248, .45); }
        [data-theme="dark"] .nav-pills { --bs-nav-link-color: var(--ink-soft); --bs-nav-link-hover-color: #c7d2fe; }
        [data-theme="dark"] * { scrollbar-color: #334155 transparent; }

        /* The 🌙 / ☀️ button: the moon shows in light mode, the sun in dark mode */
        .theme-toggle .bi-sun { display: none; }
        [data-theme="dark"] .theme-toggle .bi-moon-stars { display: none; }
        [data-theme="dark"] .theme-toggle .bi-sun { display: inline-block; color: #fbbf24; }

        /* ---- Phones (under 576px): nothing may stick out of the screen ---- */
        @media (max-width: 575.98px) {
            .app-content { padding: 1rem .8rem 2.5rem; }
            .topbar { gap: .5rem; padding: .55rem .8rem; }
            .topbar > .fw-semibold { font-size: .9rem; }
            h1.h4 { font-size: 1.2rem; }
            .panel-header { flex-wrap: wrap; padding: .85rem 1rem; }
            .panel-header > * { min-width: 0; }
            .panel-body { padding: 1rem; }
            .stat-card { padding: .9rem; gap: .7rem; }
            .stat-icon { width: 38px; height: 38px; font-size: 1.05rem; }
            .stat-value { font-size: 1.15rem; }
            .table-panel .dt-search, .table-panel .dt-search input { width: 100%; min-width: 0; }
            .table-panel .dt-container > .row { padding: .85rem 1rem; }
            .table-panel .dt-container > .row > * > * + * { margin-left: 0; }
            .table-panel .dt-container > .row.dt-layout-table { padding: 0 .5rem; }
            .bulk-bar { width: 100%; flex-wrap: wrap; margin-left: 0 !important; }
            .notification-tabs { width: 100%; }
            .notification-row { padding-left: .75rem !important; padding-right: .75rem !important; }
            .notification-menu { position: fixed !important; inset: 3.6rem .5rem auto .5rem !important; transform: none !important; width: auto; max-width: none; }
            .toast-stack { left: .75rem; right: .75rem; bottom: .75rem; }
            .app-toast { min-width: 0; max-width: none; }
            .profile-menu { width: min(17rem, calc(100vw - 1rem)); }
            .breadcrumb { flex-wrap: wrap; }
        }

        /* Thin, quiet scrollbars */
        * { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
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
            {{-- /admin/dashboard, /hr/dashboard, /manager/dashboard, /employee/dashboard: the right one for the role --}}
            <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            {{-- Own employee record: always for the Employee role, for others only when their login is linked --}}
            @if ($me->can('profile.view-own') && ($me->employee_id || $me->cannot('employees.view')))
                <a href="{{ route('profile.show') }}" class="side-link {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                    <i class="bi bi-person-vcard"></i> My Profile
                </a>
            @endif
            @can('viewAny', App\Models\LeaveRequest::class)
                <a href="{{ route('leave-requests.index') }}" class="side-link {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i> Leave Requests
                    @can('leave.approve')
                        {{-- Pending requests this user could review (their own are not counted) --}}
                        @php($toReview = App\Models\LeaveRequest::visibleTo($me)->where('status', App\Enums\LeaveStatus::Pending)->where('employee_id', '!=', $me->employee_id ?? 0)->count())
                        @if ($toReview)
                            <span class="badge rounded-pill text-bg-warning ms-auto">{{ $toReview }}</span>
                        @endif
                    @endcan
                </a>
            @endcan
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

                {{-- Light / dark mode switch --}}
                <button type="button" class="btn btn-soft btn-icon theme-toggle" data-theme-toggle aria-label="Switch to dark mode" title="Dark / light mode">
                    <i class="bi bi-moon-stars fs-5"></i><i class="bi bi-sun fs-5"></i>
                </button>

                {{-- Notifications: the bell. It lists only UNREAD ones (the 5 latest): once read (opened, ✓ or "Mark all as read")
                     they leave the bell; all of them stay on the Notifications page. ✓ and "Mark all as read" work without
                     reloading the page (the notification script at the bottom of this layout). --}}
                @php($unreadCount = $me->unreadNotifications()->count())
                @php($latestNotifications = $me->unreadNotifications()->limit(5)->get())
                <div class="dropdown">
                    <button type="button" class="btn btn-soft btn-icon position-relative bell-button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false" aria-label="Notifications{{ $unreadCount ? " ({$unreadCount} unread)" : '' }}" data-bell-button>
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger notification-count {{ $unreadCount ? '' : 'd-none' }}" data-bell-count>
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 notification-menu">
                        <div class="notification-menu-header">
                            <div>
                                <div class="fw-semibold">Notifications</div>
                                <div class="small text-muted"><span data-unread-count>{{ $unreadCount }}</span> unread</div>
                            </div>
                            <form method="POST" action="{{ route('notifications.read-all') }}" class="js-notification-form {{ $unreadCount ? '' : 'd-none' }}" data-action="read-all" data-show-when-unread>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-soft"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
                            </form>
                        </div>
                        <div class="list-group list-group-flush notification-list" data-notification-list="bell">
                            @foreach ($latestNotifications as $notification)
                                @include('partials.notification-item', ['actions' => 'read'])
                            @endforeach
                            <div class="notification-empty {{ $latestNotifications->isEmpty() ? '' : 'd-none' }}" data-notification-empty>
                                <span class="notification-empty-icon"><i class="bi bi-check2-circle"></i></span>
                                <div class="fw-semibold">You're all caught up.</div>
                                <div class="small text-muted">New leave requests and decisions will appear here.</div>
                            </div>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="notification-menu-footer">View all notifications <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

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
        // Light / dark mode: the 🌙/☀️ button next to the bell.
        (() => {
            const root = document.documentElement;
            const button = document.querySelector("[data-theme-toggle]");
            const label = () => button.setAttribute("aria-label", root.dataset.theme === "dark" ? "Switch to light mode" : "Switch to dark mode");
            label();
            button.addEventListener("click", () => {
                const dark = root.dataset.theme !== "dark";
                if (dark) { root.setAttribute("data-theme", "dark"); root.setAttribute("data-bs-theme", "dark"); }
                else { root.removeAttribute("data-theme"); root.removeAttribute("data-bs-theme"); }
                try { localStorage.setItem("theme", dark ? "dark" : "light"); } catch (e) {}
                label();
            });
        })();

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

        /*
         * Notifications without reloading the page (the bell and the Notifications page).
         * Every notification form has class "js-notification-form" and data-action="read | delete | read-all | delete-read".
         * Its submit is caught here and sent with fetch(); NotificationController answers JSON with the new counts,
         * then the rows, the red bell number, the tab counts and the buttons are updated in place.
         * Without JavaScript the same forms still work as normal forms (POST + redirect).
         */
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            // A small confirmation in the bottom-right corner, gone after 3 seconds.
            const toast = (message, error = false) => {
                const item = document.createElement('div');
                item.className = 'app-toast' + (error ? ' is-error' : '');
                item.setAttribute('role', 'status');
                item.innerHTML = `<i class="bi ${error ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill'}"></i><span></span>`;
                item.querySelector('span').textContent = message;
                document.getElementById('toast-stack').appendChild(item); // the box is placed after this script
                setTimeout(() => { item.classList.add('is-leaving'); setTimeout(() => item.remove(), 250); }, 3000);
            };

            const send = async (url, body) => {
                const response = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body });
                if (!response.ok) throw new Error(response.status);
                return response.json();
            };

            // The same notification can be in the bell AND on the page: act on both.
            const rowsOf = id => document.querySelectorAll(`.notification-row[data-notification-id="${CSS.escape(id)}"]`);

            const refreshLists = () => {
                document.querySelectorAll('[data-notification-list]').forEach(list => {
                    // a day heading ("Today") with no notification under it any more
                    list.querySelectorAll('.notification-group').forEach(heading => {
                        if (!heading.nextElementSibling?.classList.contains('notification-row')) heading.remove();
                    });
                    list.querySelector('[data-notification-empty]')?.classList.toggle('d-none', !!list.querySelector('.notification-row'));
                });
            };

            const removeRow = row => {
                row.classList.add('is-removing');
                setTimeout(() => { row.remove(); refreshLists(); }, 250);
            };

            // Read: it leaves the bell (and the "Unread" tab); on the page it simply stops being highlighted.
            const markRowRead = row => {
                if (row.closest('[data-notification-list="bell"], [data-only-unread]')) return removeRow(row);
                row.classList.remove('notification-unread');
                row.dataset.read = '1';
                row.querySelector('.notification-dot')?.remove();
                row.querySelector('form[data-action="read"]')?.remove();
            };

            // New counts from the server → red bell number, "N unread", tab counts, which buttons are shown.
            const showCounts = ({ unread_count: unread, total_count: total }) => {
                document.querySelectorAll('[data-unread-count]').forEach(el => el.textContent = unread);
                document.querySelectorAll('[data-total-count]').forEach(el => el.textContent = total);
                const badge = document.querySelector('[data-bell-count]');
                if (badge) {
                    badge.textContent = unread > 9 ? '9+' : unread;
                    badge.classList.toggle('d-none', unread === 0);
                }
                document.querySelector('[data-bell-button]')?.setAttribute('aria-label', 'Notifications' + (unread ? ` (${unread} unread)` : ''));
                document.querySelectorAll('[data-show-when-unread]').forEach(el => el.classList.toggle('d-none', unread === 0));
                document.querySelectorAll('[data-show-when-read]').forEach(el => el.classList.toggle('d-none', total - unread === 0));
                refreshLists();
            };

            // What each kind of form changes on the screen after the server said OK.
            const afterAction = {
                'read': id => rowsOf(id).forEach(markRowRead),
                'delete': id => rowsOf(id).forEach(removeRow),
                'read-all': () => document.querySelectorAll('.notification-row').forEach(markRowRead),
                'delete-read': () => document.querySelectorAll('[data-notification-list="page"] .notification-row[data-read="1"]').forEach(removeRow),
            };

            document.addEventListener('submit', async event => {
                const form = event.target.closest('form.js-notification-form');
                // defaultPrevented: the user answered "Cancel" to the confirm() of a delete form.
                if (!form || event.defaultPrevented) return;
                event.preventDefault();

                const id = form.closest('.notification-row')?.dataset.notificationId;
                const button = form.querySelector('button');
                if (button) button.disabled = true;

                try {
                    const data = await send(form.action, new FormData(form));
                    afterAction[form.dataset.action]?.(id);
                    showCounts(data);
                    toast(data.message);
                } catch (error) {
                    toast('Something went wrong. Please refresh the page and try again.', true);
                } finally {
                    if (button && document.contains(button)) button.disabled = false;
                }
            });

            // Notifications page: select one or more with the checkboxes, then "Mark as read" or "Delete".
            const bar = document.querySelector('[data-bulk-bar]');
            if (!bar) return;

            const checks = () => [...document.querySelectorAll('.js-notification-check')];
            const selected = () => checks().filter(check => check.checked);
            const selectAll = bar.querySelector('[data-select-all]');
            const label = bar.querySelector('[data-selected-label]');

            const syncSelection = () => {
                const count = selected().length;
                const all = checks().length;
                bar.querySelectorAll('[data-bulk]').forEach(button => button.disabled = count === 0);
                selectAll.checked = count > 0 && count === all;
                selectAll.indeterminate = count > 0 && count < all;
                label.textContent = `${count} selected`;
                label.classList.toggle('d-none', count === 0);
                checks().forEach(check => check.closest('.notification-row').classList.toggle('is-selected', check.checked));
            };

            document.addEventListener('change', event => {
                if (event.target.matches('.js-notification-check')) syncSelection();
            });
            selectAll.addEventListener('change', () => {
                checks().forEach(check => check.checked = selectAll.checked);
                syncSelection();
            });

            bar.querySelectorAll('[data-bulk]').forEach(button => button.addEventListener('click', async () => {
                const action = button.dataset.bulk;
                const ids = selected().map(check => check.value);
                if (!ids.length) return;
                if (action === 'delete' && !confirm(`Delete ${ids.length} selected ${ids.length === 1 ? 'notification' : 'notifications'}?`)) return;

                const body = new FormData();
                body.append('action', action);
                ids.forEach(id => body.append('ids[]', id));

                try {
                    const data = await send(bar.dataset.url, body);
                    ids.forEach(id => rowsOf(id).forEach(action === 'read' ? markRowRead : removeRow));
                    showCounts(data);
                    toast(data.message);
                } catch (error) {
                    toast('Something went wrong. Please refresh the page and try again.', true);
                }
                checks().forEach(check => check.checked = false);
                syncSelection();
                setTimeout(syncSelection, 300); // after the removed rows are gone
            }));
        })();
    </script>
    <div class="toast-stack" id="toast-stack" aria-live="polite"></div>
    @endauth
    <script>
        /**
         * "⋮" row-actions menu used by the DataTables (employees, users).
         * The menu is positioned "fixed" so it is never cut off by the table's sideways scroll.
         */
        // Show / hide a password: any <button data-password-toggle="field-id"> (login, user form, …).
        document.addEventListener('click', event => {
            const button = event.target.closest('[data-password-toggle]');
            const input = button && document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.innerHTML = `<i class="bi ${show ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });

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
