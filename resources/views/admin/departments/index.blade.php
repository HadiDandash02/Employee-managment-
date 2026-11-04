@extends('layouts.app')

@section('title', 'Manage Departments')

@push('styles')
    @include('partials.datatable-styles')
    <style>
        .department-desc { max-width: 24rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Manage Departments</h1>
            <div class="text-muted small">Departments, their codes, branches and employees.</div>
        </div>
        <x-button icon="bi-plus-lg" href="{{ route('admin.departments.create') }}">Add Department</x-button>
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
                        <label for="f-company" class="form-label">Select company</label>
                        <select id="f-company" name="company_id" class="form-select select-divider">
                            <option value="">Select company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="f-branch" class="form-label">Select branch</label>
                        <select id="f-branch" name="branch_id" class="form-select select-divider">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->company?->name }} · {{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="f-staffed" class="form-label">Employees</label>
                        <select id="f-staffed" name="staffed" class="form-select select-divider">
                            <option value="">All departments</option>
                            <option value="yes">With employees</option>
                            <option value="no">Without employees</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <x-button type="submit">Generate Report</x-button>
                    <x-button variant="secondary" id="filter-clear">Clear</x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table panel --}}
    <div class="panel table-panel">
        <div class="panel-header">
            <h2 class="panel-title">Departments</h2>
            <x-button variant="secondary" icon="bi-funnel" data-bs-toggle="collapse" data-bs-target="#filters-panel" aria-expanded="true" aria-controls="filters-panel"><span id="filters-toggle-text">Collapse</span>
                <span id="filter-count" class="badge rounded-pill text-bg-primary filter-count d-none">0</span></x-button>
        </div>

        <table id="departments-table" class="table app-table w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department</th>
                    <th>Code</th>
                    <th>Branch</th>
                    <th>Employees</th>
                    <th>Added on</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>

    <noscript>
        <div class="alert alert-warning mt-3">JavaScript is required to show the departments table.</div>
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
    const tableEl = document.getElementById('departments-table');

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
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(div).close(), 5000);
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

    DataTable.ext.errMode = 'none';

    // Server-side: Admin\DepartmentController@data does the search (every column), sorting and paging.
    const table = new DataTable(tableEl, {
        serverSide: true,
        processing: true,
        ajax: {
            url: @json(route('admin.departments.data')),
            data: d => { d.filters = activeFilters(); },
        },
        order: [[1, 'asc']],
        searchDelay: 300,
        pageLength: {{ $pageLengths[0] }},
        lengthMenu: @json($pageLengths),
        autoWidth: false,
        responsive: false, // scrolls sideways; the Actions column stays pinned on the right
        columns: [
            {
                data: null, orderable: false, searchable: false, type: 'string', width: '3rem', className: 'tabular',
                render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1,
            },
            {
                data: 'name', name: 'name',
                render: (name, type, row) => `
                    <div class="fw-semibold">${esc(name)}</div>
                    ${row.description ? `<div class="small text-muted text-truncate department-desc" title="${esc(row.description)}">${esc(row.description)}</div>` : ''}`,
            },
            {
                data: 'code', name: 'code',
                render: code => `<span class="badge text-bg-light border font-monospace">${esc(code ?? '—')}</span>`,
            },
            {
                data: 'branch', name: 'branch',
                render: (branch, type, row) => `<div>${esc(branch ?? '—')}${row.city ? ` <span class="text-muted small">(${esc(row.city)})</span>` : ''}</div>
                    <div class="small text-muted">${esc(row.company ?? '')}</div>`,
            },
            {
                data: 'employees_count', name: 'employees_count', className: 'text-center',
                render: (count, type, row) => `<a href="${row.urls.employees}" title="Show these employees"
                    class="badge rounded-pill ${count ? 'bg-primary-subtle text-primary-emphasis border border-primary-subtle' : 'text-bg-light border text-muted'} text-decoration-none">${count}</a>`,
            },
            { data: 'created_at', name: 'created_at', className: 'tabular text-nowrap' },
            {
                data: null, orderable: false, searchable: false, className: 'text-end col-actions',
                render: (data, type, row) => actionsMenu(row.name, `
                    <li><a class="dropdown-item" href="${row.urls.edit}"><i class="bi bi-pencil-square"></i> Edit</a></li>
                    <li><a class="dropdown-item" href="${row.urls.employees}"><i class="bi bi-people"></i> View employees</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item text-danger js-delete" data-url="${row.urls.destroy}" data-name="${esc(row.name)}" data-count="${row.employees_count}"><i class="bi bi-trash"></i> Delete</button></li>`),
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
            searchPlaceholder: 'Search every column',
            lengthMenu: '_MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ departments',
            infoEmpty: 'No departments to show',
            infoFiltered: '(filtered from _MAX_)',
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading…',
            zeroRecords: '<i class="bi bi-diagram-3 fs-2 d-block mb-1 opacity-50"></i>No departments match your search or filters.',
            emptyTable: '<i class="bi bi-diagram-3 fs-2 d-block mb-1 opacity-50"></i>No departments yet.',
        },
    });

    table.on('dt-error', () => showAlert('Could not load the departments. Please refresh the page and try again.', 'danger'));

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

    // Delete from the row's ⋮ menu (refused by the server while the department still has employees).
    tableEl.addEventListener('click', async event => {
        const button = event.target.closest('.js-delete');
        if (!button) return;
        if (!confirm(`Delete department ${button.dataset.name}?`)) return;

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
            showAlert('Could not delete the department. Please refresh the page and try again.', 'danger');
        }
    });
})();
</script>
@endpush
