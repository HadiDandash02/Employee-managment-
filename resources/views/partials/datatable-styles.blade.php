{{-- DataTables + the table look, shared by the Manage Employees and Manage Users pages. --}}
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
        border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: .35rem 0; min-width: 9rem;
    }
    .table-panel div.dt-button-collection .dropdown-item { font-size: .8rem; font-weight: 500; padding: .45rem 1rem; }
    .table-panel .dt-info { font-size: .8rem; color: var(--muted); }
    .table-panel .dt-paging .pagination { margin: 0; }
    .table-panel div.dt-processing { z-index: 10; }

    /* All columns stay visible: the table scrolls sideways when it is wider than the page ... */
    .table-panel .dt-layout-table > div { display: block !important; overflow-x: auto; min-width: 0; max-width: 100%; } /* the box around the table scrolls, not the page */
    table.app-table td, table.app-table th { white-space: nowrap; }

    /* Light header: colours come from the shared table style in layouts/app.blade.php */
    table.app-table { margin: 0 !important; font-size: .82rem; }
    table.app-table thead th:first-child { border-top-left-radius: .5rem; }
    table.app-table thead th:last-child { border-top-right-radius: .5rem; }
    table.app-table thead th.dt-orderable-asc:hover, table.app-table thead th.dt-orderable-desc:hover { outline: none; background-color: #eef2f7 !important; color: var(--ink) !important; }
    table.app-table thead span.dt-column-order { color: #94a3b8; }
    table.app-table thead .form-check-input { cursor: pointer; }
    table.app-table tbody .form-check-input { cursor: pointer; }
    table.app-table tbody td { padding: .85rem; border-bottom: 1px solid var(--line-soft); color: var(--ink); vertical-align: middle; }

    /* ... and the Actions column stays pinned on the right edge while scrolling */
    table.app-table th.col-actions, table.app-table td.col-actions { position: sticky; right: 0; width: 1%; }
    table.app-table thead th.col-actions { z-index: 3; }
    /* While its ⋮ menu is open, lift the pinned cell above everything (e.g. the active page button, z-index 3) */
    table.app-table td.col-actions:has(.dropdown-menu.show) { z-index: 1000; }
    table.app-table tbody td.col-actions {
        z-index: 2; background-color: #fff;
        box-shadow: inset 1px 0 0 var(--line-soft), -8px 0 10px -8px rgba(15, 23, 42, .15);
    }

    table.app-table tbody tr:hover > td, table.app-table tbody tr:hover > td.col-actions { background-color: var(--hover); }
    /* Selected rows: light brand background with a brand bar on the left */
    table.app-table tbody tr.is-selected > td { background-color: var(--brand-light) !important; color: inherit; }
    table.app-table tbody tr.is-selected > td:first-child { box-shadow: inset 3px 0 0 var(--brand) !important; }
    table.app-table .col-check { width: 2.5rem; text-align: center; }
    table.app-table td.dt-empty { padding: 2.5rem 1rem; color: var(--muted); }

    .filter-count { font-size: .65rem; }
</style>
