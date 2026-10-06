@extends('layouts.app')

@section('title', 'Employees')

@push('styles')
    @include('partials.ncn-datatable-styles')
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Employees</h1>
            <div class="text-muted small">Filter, search, export and manage your employees.</div>
        </div>
        @can('employees.create')
            <a href="{{ route('employees.create') }}" class="btn btn-generate">
                <i class="bi bi-person-plus me-1"></i> Add Employee
            </a>
        @endcan
    </div>

    {{-- Filter panel --}}
    <div class="collapse show" id="filters-panel">
        <form id="filter-form" class="panel mb-4" autocomplete="off">
            <div class="panel-header">
                <h2 class="panel-title">Filters</h2>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="f-department" class="form-label">Select department</label>
                        <select id="f-department" name="department" class="form-select select-divider">
                            <option value="">Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department }}">{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="f-salary-min" class="form-label">Salary from</label>
                        <input type="number" id="f-salary-min" name="salary_min" class="form-control" min="0" step="0.01" placeholder="Minimum salary">
                    </div>
                    <div class="col-md-4">
                        <label for="f-salary-max" class="form-label">Salary to</label>
                        <input type="number" id="f-salary-max" name="salary_max" class="form-control" min="0" step="0.01" placeholder="Maximum salary">
                    </div>
                    <div class="col-md-4">
                        <label for="f-born-from" class="form-label">Born from</label>
                        <input type="date" id="f-born-from" name="born_from" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="f-born-to" class="form-label">Born to</label>
                        <input type="date" id="f-born-to" name="born_to" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="f-added-from" class="form-label">Added from</label>
                        <input type="date" id="f-added-from" name="added_from" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="f-added-to" class="form-label">Added to</label>
                        <input type="date" id="f-added-to" name="added_to" class="form-control">
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
            <h2 class="panel-title">Employees</h2>
            <button type="button" class="btn btn-soft d-inline-flex align-items-center gap-2" id="filters-toggle"
                    data-bs-toggle="collapse" data-bs-target="#filters-panel" aria-expanded="true" aria-controls="filters-panel">
                <i class="bi bi-funnel"></i>
                <span id="filters-toggle-text">Collapse</span>
                <span id="filter-count" class="badge rounded-pill text-bg-primary filter-count d-none">0</span>
            </button>
        </div>

        <div id="bulk-bar" class="d-none align-items-center gap-3 px-3 py-2 border-bottom" style="background: var(--brand-light)">
            <i class="bi bi-check2-square text-primary"></i>
            <span class="small"><strong id="selected-count">0</strong> selected</span>
            <button type="button" id="bulk-delete" class="btn btn-sm btn-danger">
                <i class="bi bi-trash me-1"></i> Delete selected
            </button>
            <button type="button" id="bulk-clear" class="btn btn-sm btn-link text-decoration-none">Clear selection</button>
        </div>

        <table id="employees-table" class="table ncn-table w-100">
            <thead>
                <tr>
                    <th class="col-check"><input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all on this page"></th>
                    <th>#</th>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Salary</th>
                    <th>Date of Birth</th>
                    <th>Added On</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>

    <noscript>
        <div class="alert alert-warning mt-3">JavaScript is required to show the employee table.</div>
    </noscript>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.2.0/b-colvis-3.2.0/b-html5-3.2.0/b-print-3.2.0/r-3.0.3/datatables.min.js"></script>
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const alerts = document.getElementById('alerts');
    const bulkBar = document.getElementById('bulk-bar');
    const selectAllBox = () => document.getElementById('select-all');
    const filterForm = document.getElementById('filter-form');
    const initialSearch = @json($search);
    // What the logged-in user may do; the server checks this again on every request.
    const can = @json([
        'update' => auth()->user()->can('employees.update'),
        'delete' => auth()->user()->can('employees.delete'),
    ]);

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

    // Current filter values, without the empty ones.
    const activeFilters = () => Object.fromEntries(
        [...new FormData(filterForm)].filter(([, value]) => String(value).trim() !== '')
    );

    function updateFilterCount() {
        const count = Object.keys(activeFilters()).length;
        const badge = document.getElementById('filter-count');
        badge.textContent = count;
        badge.classList.toggle('d-none', count === 0);
    }

    const tableEl = document.getElementById('employees-table');
    const tooltips = action => tableEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el =>
        action === 'init' ? bootstrap.Tooltip.getOrCreateInstance(el) : bootstrap.Tooltip.getInstance(el)?.dispose()
    );

    // HTML for the screen, plain text for export / print.
    const display = (html, plain) => (data, type, row, meta) => type === 'display' ? html(data, row, meta) : plain(data, row, meta);
    const exportOptions = { columns: '.export', orthogonal: 'export' };

    DataTable.ext.errMode = 'none';

    const table = new DataTable(tableEl, {
        serverSide: true,
        processing: true,
        ajax: {
            url: @json(route('employees.data')),
            data: d => { d.filters = activeFilters(); },
        },
        order: [],
        searchDelay: 300,
        pageLength: {{ $pageLengths[0] }},
        lengthMenu: [
            @foreach ($pageLengths as $length)
                {!! $length === -1 ? "{ label: 'All', value: -1 }" : $length !!},
            @endforeach
        ],
        search: { search: initialSearch },
        stateSave: true,
        stateDuration: -1,
        stateLoadParams: (settings, data) => {
            // The selection column follows the current user's permission, not the saved state.
            if (data.columns?.[0]) data.columns[0].visible = can.delete;

            // A ?search=… link (e.g. "View all colleagues") wins over the saved state.
            if (initialSearch) {
                data.search.search = initialSearch;
                data.start = 0;
            }
        },
        responsive: false, // all columns stay visible; the table scrolls sideways when there is no room
        autoWidth: false,
        columns: [
            {
                data: 'id', orderable: false, searchable: false, className: 'col-check', visible: can.delete,
                render: display(
                    (id, row) => `<input type="checkbox" class="form-check-input row-check" value="${id}" aria-label="Select ${esc(row.full_name)}">`,
                    () => ''
                ),
            },
            {
                data: null, orderable: false, searchable: false, type: 'string', className: 'export tabular', width: '3rem',
                render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1,
            },
            { data: 'id', name: 'id', type: 'string', className: 'export tabular', visible: false },
            {
                data: 'full_name', name: 'name', className: 'export',
                render: display((name, row) => `
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar text-bg-${row.avatar_color}" style="width:32px;height:32px;font-size:12px" aria-hidden="true">${esc(row.initials)}</span>
                        <a href="${row.urls.show}" class="fw-semibold text-body text-decoration-none">${esc(name)}</a>
                    </div>`, name => name),
            },
            { data: 'email', name: 'email', className: 'export', render: display(email => esc(email), email => email) },
            {
                data: 'department', name: 'department', className: 'export',
                render: display((department, row) => {
                    const c = row.department_color;
                    return `<span class="badge rounded-pill bg-${c}-subtle text-${c}-emphasis border border-${c}-subtle fw-semibold">${esc(department)}</span>`;
                }, department => department),
            },
            {
                data: 'salary', name: 'salary', type: 'string', className: 'export tabular text-nowrap',
                render: display((salary, row) => row.salary_formatted, salary => salary),
            },
            {
                data: 'date_of_birth', name: 'date_of_birth', type: 'string', className: 'export tabular text-nowrap',
                render: display((dob, row) => `${row.date_of_birth} <span class="text-muted">(${row.age})</span>`, dob => dob),
            },
            { data: 'added_on', name: 'added_on', type: 'string', className: 'export tabular text-nowrap' },
            {
                data: null, orderable: false, searchable: false, className: 'text-end text-nowrap col-actions',
                render: display((data, row) => `
                    <a href="${row.urls.show}" class="btn btn-sm btn-light btn-icon" data-bs-toggle="tooltip" title="View" aria-label="View ${esc(row.full_name)}"><i class="bi bi-eye"></i></a>
                    ${can.update ? `<a href="${row.urls.edit}" class="btn btn-sm btn-light btn-icon text-primary" data-bs-toggle="tooltip" title="Edit" aria-label="Edit ${esc(row.full_name)}"><i class="bi bi-pencil-square"></i></a>` : ''}
                    ${can.delete ? `<button type="button" class="btn btn-sm btn-light btn-icon text-danger js-delete" data-url="${row.urls.destroy}" data-name="${esc(row.full_name)}" data-bs-toggle="tooltip" title="Delete" aria-label="Delete ${esc(row.full_name)}"><i class="bi bi-trash"></i></button>` : ''}`,
                    () => ''),
            },
        ],
        layout: {
            topStart: ['search', 'pageLength'],
            topEnd: {
                buttons: {
                    dom: { button: { className: 'btn btn-sm btn-soft' } },
                    buttons: [
                        {
                            extend: 'colvis',
                            text: '<i class="bi bi-layout-three-columns me-1"></i> Columns',
                            columns: ':not(.col-check):not(.col-actions)',
                        },
                        {
                            extend: 'collection',
                            text: '<i class="bi bi-download me-1"></i> Export',
                            buttons: [
                                { extend: 'pdf', text: 'PDF', title: 'Employees', orientation: 'landscape', exportOptions },
                                { extend: 'excel', text: 'Excel', title: 'Employees', exportOptions },
                                { extend: 'csv', text: 'CSV', title: 'Employees', exportOptions },
                                { extend: 'print', text: 'Print', title: 'Employees', exportOptions },
                            ],
                        },
                    ],
                },
            },
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
        language: {
            search: '',
            searchPlaceholder: 'Search table',
            lengthMenu: '_MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ employees',
            infoEmpty: 'No employees to show',
            infoFiltered: '(filtered from _MAX_)',
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading…',
            zeroRecords: '<i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>No employees match your filters.',
            emptyTable: '<i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>No employees yet.',
        },
        preDrawCallback: () => tooltips('dispose'),
        drawCallback: function () {
            const api = this.api();
            const info = api.page.info();

            // The current page became empty (e.g. after deleting its last rows): go back one page.
            if (info.recordsDisplay > 0 && api.rows().count() === 0 && info.page > 0) {
                api.page('previous').draw('page');
                return;
            }

            tooltips('init');
            updateSelection();
        },
    });

    table.on('dt-error', () => showAlert('Could not load employees. Please refresh the page and try again.', 'danger'));

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

    // Selection and bulk delete.
    const rowChecks = () => [...tableEl.querySelectorAll('tbody .row-check')];
    const selectedIds = () => rowChecks().filter(box => box.checked).map(box => Number(box.value));

    function updateSelection() {
        const all = rowChecks();
        const ids = selectedIds();

        all.forEach(box => box.closest('tr').classList.toggle('is-selected', box.checked));
        const selectAll = selectAllBox();
        if (selectAll) {
            selectAll.checked = all.length > 0 && ids.length === all.length;
            selectAll.indeterminate = ids.length > 0 && ids.length < all.length;
            selectAll.disabled = all.length === 0;
        }
        document.getElementById('selected-count').textContent = ids.length;
        bulkBar.classList.toggle('d-none', ids.length === 0);
        bulkBar.classList.toggle('d-flex', ids.length > 0);
    }

    // DataTables rebuilds the header, so the live "select all" box is handled through event delegation.
    tableEl.addEventListener('change', event => {
        if (event.target.id === 'select-all') {
            rowChecks().forEach(box => box.checked = event.target.checked);
            updateSelection();
        }
        if (event.target.classList.contains('row-check')) updateSelection();
    });

    document.getElementById('bulk-clear').addEventListener('click', () => {
        rowChecks().forEach(box => box.checked = false);
        updateSelection();
    });

    async function send(url, options, failMessage) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
            });
            if (!response.ok) throw new Error(response.statusText);
            showAlert((await response.json()).message);
            table.ajax.reload(null, false);
        } catch (error) {
            showAlert(failMessage, 'danger');
        }
    }

    document.getElementById('bulk-delete').addEventListener('click', () => {
        const ids = selectedIds();
        const label = ids.length === 1 ? 'employee' : 'employees';
        if (!ids.length || !confirm(`Delete ${ids.length} selected ${label}? This cannot be undone.`)) return;

        send(@json(route('employees.bulk-destroy')), {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids }),
        }, 'Could not delete the selected employees. Please refresh the page and try again.');
    });

    // Single delete from the row's trash button.
    tableEl.addEventListener('click', event => {
        const button = event.target.closest('.js-delete');
        if (!button) return;

        bootstrap.Tooltip.getInstance(button)?.hide();
        if (!confirm(`Delete ${button.dataset.name}? This cannot be undone.`)) return;

        const body = new FormData();
        body.append('_method', 'DELETE');
        send(button.dataset.url, { method: 'POST', body }, 'Could not delete the employee. Please refresh the page and try again.');
    });
})();
</script>
@endpush
