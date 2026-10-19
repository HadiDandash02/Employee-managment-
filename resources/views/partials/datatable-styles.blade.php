{{-- DataTables + the teal table look, shared by the Manage Employees and Manage Users pages. --}}
<link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.2.0/b-colvis-3.2.0/b-html5-3.2.0/b-print-3.2.0/r-3.0.3/datatables.min.css" rel="stylesheet">
<style>
    /* Toolbar above / footer below the table */
    .table-panel .dt-container > .row { margin: 0 !important; padding: 1rem 1.1rem; row-gap: .6rem; }
    .table-panel .dt-container > .row > * { padding: 0; }
    .table-panel .dt-container > .row > * > * + * { margin-left: .6rem; }
    .table-panel .dt-container > .row.dt-layout-table { padding: 0 1.1rem; }
    .table-panel .dt-search input { min-width: 16rem; margin-left: 0 !important; }
    .table-panel .dt-length select { min-width: 4.5rem; }
    .table-panel .dt-buttons { gap: .5rem; display: inline-flex; }
    .table-panel .dt-buttons .btn { border-radius: .45rem !important; }
    .table-panel .dt-button-collection .dropdown-menu, .table-panel div.dt-button-collection {
        border: 1px solid #e3e8e8; border-radius: .5rem; box-shadow: 0 8px 24px rgba(16, 24, 40, .12); padding: .35rem 0; min-width: 9rem;
    }
    .table-panel div.dt-button-collection .dropdown-item { font-size: .8rem; font-weight: 500; padding: .45rem 1rem; }
    .table-panel .dt-info { font-size: .8rem; color: #6b7280; }
    .table-panel .dt-paging .pagination { margin: 0; }
    .table-panel div.dt-processing { z-index: 10; }

    /* All columns stay visible: the table scrolls sideways when it is wider than the page ... */
    .table-panel .dt-layout-table > div { display: block !important; overflow-x: auto; min-width: 0; max-width: 100%; } /* the box around the table scrolls, not the page */
    table.app-table td, table.app-table th { white-space: nowrap; }

    /* Teal header */
    table.app-table { margin: 0 !important; font-size: .8rem; }
    table.app-table thead th {
        --bs-table-bg: var(--brand); --bs-table-color: #fff;
        background-color: var(--brand) !important; color: #fff !important; font-weight: 700; font-size: .78rem; white-space: nowrap;
        border: 0; border-right: 1px solid rgba(255, 255, 255, .18); padding: .85rem .75rem; vertical-align: middle;
    }
    table.app-table thead th:last-child { border-right: 0; }
    table.app-table thead th:first-child { border-top-left-radius: .35rem; }
    table.app-table thead th:last-child { border-top-right-radius: .35rem; }
    table.app-table thead th.dt-orderable-asc:hover, table.app-table thead th.dt-orderable-desc:hover { outline: none; background: var(--brand-dark); }
    table.app-table thead span.dt-column-order { color: #fff; }
    /* Header "select all" box: white box, teal tick */
    table.app-table thead .form-check-input { background-color: #fff; border-color: #fff; cursor: pointer; }
    table.app-table thead .form-check-input:checked {
        background-color: #fff; border-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%230f7b6c' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
    }
    table.app-table thead .form-check-input:indeterminate {
        background-color: #fff; border-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%230f7b6c' stroke-linecap='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
    }
    table.app-table tbody .form-check-input { cursor: pointer; }
    table.app-table tbody td { padding: .9rem .75rem; border-bottom: 1px solid #eef1f1; color: #111; vertical-align: middle; }

    /* ... and the Actions column stays pinned on the right edge while scrolling */
    table.app-table th.col-actions, table.app-table td.col-actions { position: sticky; right: 0; width: 1%; }
    table.app-table thead th.col-actions { z-index: 3; }
    /* While its ⋮ menu is open, lift the pinned cell above everything (e.g. the active page button, z-index 3) */
    table.app-table td.col-actions:has(.dropdown-menu.show) { z-index: 1000; }
    table.app-table tbody td.col-actions {
        z-index: 2; background-color: #fff;
        box-shadow: inset 1px 0 0 #eef1f1, -8px 0 10px -8px rgba(16, 24, 40, .18);
    }

    table.app-table tbody tr:hover > td, table.app-table tbody tr:hover > td.col-actions { background-color: #f7fbfa; }
    /* Selected rows: light teal background with a teal bar on the left */
    table.app-table tbody tr.is-selected > td { background-color: #e3f2ef !important; color: inherit; }
    table.app-table tbody tr.is-selected > td:first-child { box-shadow: inset 3px 0 0 var(--brand) !important; }
    table.app-table .col-check { width: 2.5rem; text-align: center; }
    table.app-table td.dt-empty { padding: 2.5rem 1rem; color: #6b7280; }

    .filter-count { font-size: .65rem; }
</style>
