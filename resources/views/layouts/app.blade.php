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
        /* ---- Theme (teal, same look as the NCN system) ---- */
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
        .active > .page-link, .page-link.active { background-color: var(--brand); border-color: var(--brand); }

        /* Top bar */
        .topbar { background: #fff; border-bottom: 1px solid #e6ebeb; box-shadow: 0 1px 3px rgba(16, 24, 40, .04); }
        .topbar .brand { color: #111; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: .6rem; }
        .topbar .brand-mark {
            width: 34px; height: 34px; border-radius: 10px; background: var(--brand); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .chip {
            display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem; border-radius: 999px;
            background: #fff; border: 1px solid #e3e8e8; font-weight: 600; font-size: .8rem;
        }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--brand); }

        /* Cards / panels */
        .panel { background: #fff; border: 1px solid #e3e8e8; border-radius: .6rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1.1rem; border-bottom: 1px solid #e9eded; }
        .panel-title { font-size: .78rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; margin: 0; color: #111; }
        .panel-body { padding: 1.1rem; }

        /* Form controls like the NCN filters */
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
        .tabular { font-variant-numeric: tabular-nums; }
        .avatar { display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; flex-shrink: 0; }
        .info-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); }
        .form-section-title { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); font-weight: 600; }
    </style>
</head>
<body>
    <header class="topbar mb-4">
        <div class="container-xxl d-flex align-items-center gap-3 py-2">
            <a class="brand" href="{{ route('employees.index') }}">
                <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                Employee Management
            </a>
            <div class="ms-auto d-none d-sm-flex align-items-center gap-2">
                <span class="chip"><span class="dot"></span> {{ now()->format('l, d M Y') }}</span>
            </div>
        </div>
    </header>

    <main class="container-xxl pb-5">
        <div id="alerts">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
