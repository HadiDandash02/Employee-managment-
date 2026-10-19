@extends('layouts.app')

@section('title', 'Manage Users')

@push('styles')
    @include('partials.ncn-datatable-styles')
    <style>
        .password-mask { letter-spacing: .15em; color: #6b7280; font-weight: 700; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Manage Users</h1>
            <div class="text-muted small">Filter, edit and remove user accounts.</div>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-generate">
            <i class="bi bi-person-plus me-1"></i> Add User
        </a>
    </div>

    {{-- Filter panel --}}
    <div class="collapse show" id="filters-panel">
        <form id="filter-form" class="panel mb-4" autocomplete="off">
            <div class="panel-header">
                <h2 class="panel-title">Filters</h2>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label for="f-name" class="form-label">Name</label>
                        <input type="text" id="f-name" name="name" class="form-control" placeholder="Name, username or email">
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="f-role" class="form-label">Select role</label>
                        <select id="f-role" name="role" class="form-select select-divider">
                            <option value="">Select role</option>
                            @foreach ($roles as $key => $role)
                                <option value="{{ $key }}">{{ $role['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-generate">Generate Report</button>
                    <button type="button" id="filter-clear" class="btn btn-clear">Clear</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table panel --}}
    <div class="panel table-panel">
        <div class="panel-header">
            <h2 class="panel-title">Users</h2>
            <button type="button" class="btn btn-soft d-inline-flex align-items-center gap-2"
                    data-bs-toggle="collapse" data-bs-target="#filters-panel" aria-expanded="true" aria-controls="filters-panel">
                <i class="bi bi-funnel"></i>
                <span id="filters-toggle-text">Collapse</span>
                <span id="filter-count" class="badge rounded-pill text-bg-primary filter-count d-none">0</span>
            </button>
        </div>

        <table id="users-table" class="table ncn-table w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>

    <noscript>
        <div class="alert alert-warning mt-3">JavaScript is required to show the users table.</div>
    </noscript>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.2.0/b-colvis-3.2.0/b-html5-3.2.0/b-print-3.2.0/r-3.0.3/datatables.min.js"></script>
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const alerts = document.getElementById('alerts');
    const filterForm = document.getElementById('filter-form');
    const tableEl = document.getElementById('users-table');

    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    function showAlert(message, type = 'success') {
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        const div = document.createElement('div');
        div.className = `alert alert-${type} alert-dismissible fade show`;
        div.role = 'alert';
        div.innerHTML = `<i class="bi ${icon} me-1"></i> <span></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        div.querySelector('span').textContent = message;
        alerts.replaceChildren(div);
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(div).close(), 4000);
    }

    const activeFilters = () => Object.fromEntries(
        [...new FormData(filterForm)].filter(([, value]) => String(value).trim() !== '')
    );

    function updateFilterCount() {
        const count = Object.keys(activeFilters()).length;
        const badge = document.getElementById('filter-count');
        badge.textContent = count;
        badge.classList.toggle('d-none', count === 0);
    }

    const tooltips = action => tableEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el =>
        action === 'init' ? bootstrap.Tooltip.getOrCreateInstance(el) : bootstrap.Tooltip.getInstance(el)?.dispose()
    );

    DataTable.ext.errMode = 'none';

    const table = new DataTable(tableEl, {
        serverSide: true,
        processing: true,
        ajax: {
            url: @json(route('admin.users.data')),
            data: d => { d.filters = activeFilters(); },
        },
        order: [],
        searchDelay: 300,
        pageLength: {{ $pageLengths[0] }},
        lengthMenu: @json($pageLengths),
        autoWidth: false,
        columns: [
            {
                data: null, orderable: false, searchable: false, type: 'string', width: '3rem', className: 'tabular',
                render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1,
            },
            {
                data: 'name', name: 'name',
                render: (name, type, row) => `
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar text-bg-${row.role_color}" style="width:34px;height:34px;font-size:12px" aria-hidden="true">${esc(row.initials)}</span>
                        <div class="lh-sm">
                            <div class="fw-semibold">${esc(name)}${row.is_self ? ' <span class="badge text-bg-light border fw-normal ms-1">You</span>' : ''}</div>
                            <div class="small text-muted">@${esc(row.username)} · ${esc(row.email)}</div>
                        </div>
                    </div>`,
            },
            {
                data: 'role', name: 'role',
                render: (role, type, row) =>
                    `<span class="badge rounded-pill bg-${row.role_color}-subtle text-${row.role_color}-emphasis border border-${row.role_color}-subtle fw-semibold">${esc(row.role_label)}</span>`,
            },
            {
                data: null, orderable: false, searchable: false,
                // Passwords are stored hashed and can never be shown; offer a reset instead.
                render: (data, type, row) => `
                    <span class="password-mask me-2" aria-label="Hidden password">••••••••</span>
                    <a href="${row.urls.password}" class="btn btn-sm btn-soft py-0 px-2" data-bs-toggle="tooltip" title="Set a new password">
                        <i class="bi bi-key me-1"></i>Reset
                    </a>`,
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-end col-actions',
                // One "⋮" button per row; the actions open in a small menu.
                render: (data, type, row) => actionsMenu(row.name, `
                    <li><a class="dropdown-item" href="${row.urls.edit}"><i class="bi bi-pencil-square"></i> Edit</a></li>
                    <li><a class="dropdown-item" href="${row.urls.password}"><i class="bi bi-key"></i> Reset password</a></li>
                    ${row.can_delete ? `<li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item text-danger js-delete" data-url="${row.urls.destroy}" data-name="${esc(row.name)}"><i class="bi bi-trash"></i> Delete</button></li>` : ''}`),
            },
        ],
        layout: {
            topStart: ['search', 'pageLength'],
            topEnd: null,
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
        language: {
            search: '',
            searchPlaceholder: 'Search table',
            lengthMenu: '_MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            infoEmpty: 'No users to show',
            infoFiltered: '(filtered from _MAX_)',
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading…',
            zeroRecords: '<i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>No users match your filters.',
            emptyTable: '<i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>No users yet.',
        },
        preDrawCallback: () => tooltips('dispose'),
        drawCallback: () => tooltips('init'),
    });

    table.on('dt-error', () => showAlert('Could not load users. Please refresh the page and try again.', 'danger'));

    // Filters: "Generate Report" applies them, "Clear" removes them.
    filterForm.addEventListener('submit', event => {
        event.preventDefault();
        updateFilterCount();
        table.ajax.reload();
    });

    document.getElementById('filter-clear').addEventListener('click', () => {
        filterForm.reset();
        updateFilterCount();
        table.ajax.reload();
    });

    const filtersPanel = document.getElementById('filters-panel');
    filtersPanel.addEventListener('hidden.bs.collapse', () => document.getElementById('filters-toggle-text').textContent = 'Filters');
    filtersPanel.addEventListener('shown.bs.collapse', () => document.getElementById('filters-toggle-text').textContent = 'Collapse');

    // Delete from the row's trash button.
    tableEl.addEventListener('click', async event => {
        const button = event.target.closest('.js-delete');
        if (!button) return;

        bootstrap.Tooltip.getInstance(button)?.hide();
        if (!confirm(`Delete user ${button.dataset.name}? This cannot be undone.`)) return;

        const body = new FormData();
        body.append('_method', 'DELETE');

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const data = await response.json();
            showAlert(data.message, response.ok ? 'success' : 'danger');
            if (response.ok) table.ajax.reload(null, false);
        } catch (error) {
            showAlert('Could not delete the user. Please refresh the page and try again.', 'danger');
        }
    });
})();
</script>
@endpush
