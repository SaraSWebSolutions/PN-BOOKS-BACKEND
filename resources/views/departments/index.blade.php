{{-- resources/views/departments/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Departments')

@section('content')

{{-- ══ TOAST ══ --}}
<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- ══ OFFCANVAS — Add / Edit ══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="deptCanvas" style="width:460px;">
    <div class="offcanvas-header border-bottom py-3">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0">
                <i id="canvasIcon" class="feather-plus-circle me-2 text-primary"></i>
                <span id="canvasTitle">Add Department</span>
            </h5>
            <small class="text-muted">Manage your organisation departments.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="deptForm" novalidate>
            @csrf
            <input type="hidden" id="editDeptId">

            {{-- Name --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Department Name <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="feather-briefcase"></i></span>
                    <input type="text" id="dept_name" class="form-control"
                           placeholder="e.g. Sales, Marketing, HR…">
                </div>
                <div class="field-error" id="nameError"></div>
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea id="dept_description" class="form-control" rows="4"
                          placeholder="Brief description of this department…"></textarea>
                <div class="field-error" id="descriptionError"></div>
            </div>

            {{-- Status --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="feather-toggle-right"></i></span>
                    <select id="dept_is_active" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-brand flex-fill"
                        data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="deptSpinner"></span>
                    <i class="feather-save me-2" id="deptBtnIcon"></i>
                    <span id="deptBtnText">Save</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ PAGE HEADER ══ --}}
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Departments</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Masters</li>
            <li class="breadcrumb-item">Departments</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2">

            {{-- Filter --}}
            <div class="dropdown">
                <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown" title="Filter">
                    <i class="feather-filter"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                    <div class="px-3 py-2 text-muted fs-11 fw-semibold text-uppercase">
                        Filter by Status
                    </div>
                    <a href="javascript:void(0);" class="dropdown-item filter-status active"
                       data-status="all">All</a>
                    <a href="javascript:void(0);" class="dropdown-item filter-status"
                       data-status="active">✅ Active</a>
                    <a href="javascript:void(0);" class="dropdown-item filter-status"
                       data-status="inactive">⛔ Inactive</a>
                </div>
            </div>

            {{-- Export --}}
            <div class="dropdown">
                <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown" title="Export">
                    <i class="feather-paperclip"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="javascript:void(0);" class="dropdown-item" onclick="exportCSV()">
                        <i class="bi bi-filetype-csv me-3"></i>CSV
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="javascript:void(0);" class="dropdown-item" onclick="window.print()">
                        <i class="bi bi-printer me-3"></i>Print
                    </a>
                </div>
            </div>

            <button class="btn btn-primary" onclick="openAdd()">
                <i class="feather-plus me-2"></i>Add Department
            </button>
        </div>
    </div>
</div>

<div class="main-content">

    {{-- ══ STATS ══ --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total', 'value'=>$stats['total'],    'icon'=>'feather-briefcase',     'bg'=>'rgba(99,102,241,.1)',  'color'=>'#6366f1'],
            ['label'=>'Active','value'=>$stats['active'],   'icon'=>'feather-check-circle',  'bg'=>'rgba(16,185,129,.1)', 'color'=>'#10b981', 'fw'=>'text-success'],
            ['label'=>'Inactive','value'=>$stats['inactive'],'icon'=>'feather-pause-circle', 'bg'=>'rgba(234,179,8,.1)', 'color'=>'#eab308', 'fw'=>'text-warning'],
        ] as $s)
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="hstack justify-content-between">
                        <div>
                            <div class="text-muted fs-12 mb-1">{{ $s['label'] }}</div>
                            <div class="fs-22 fw-bold {{ $s['fw'] ?? '' }}">{{ $s['value'] }}</div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:10px;
                                    background:{{ $s['bg'] }};
                                    display:flex;align-items:center;justify-content:center;
                                    color:{{ $s['color'] }};">
                            <i class="{{ $s['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ══ TABLE ══ --}}
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-12">Show</span>
                <select id="perPageSelect" class="form-select form-select-sm" style="width:70px;">
                    <option>10</option><option>25</option><option>50</option>
                </select>
                <span class="text-muted fs-12">entries</span>
            </div>
            <div class="input-group" style="max-width:260px;">
                <span class="input-group-text"><i class="feather-search"></i></span>
                <input type="text" id="searchInput" class="form-control form-control-sm"
                       placeholder="Search departments…">
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="wd-30">
                                <div class="custom-control custom-checkbox ms-1">
                                    <input type="checkbox" class="custom-control-input" id="checkAll">
                                    <label class="custom-control-label" for="checkAll"></label>
                                </div>
                            </th>
                            <th>#</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th class="text-center">Status</th>
                            <th class="text-muted fs-11">Updated By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deptTableBody">
                        @forelse($departments as $dept)
                        <tr class="dept-row" id="dept-row-{{ $dept->id }}"
                            data-status="{{ $dept->is_active ? 'active' : 'inactive' }}"
                            data-id="{{ $dept->id }}"
                            data-name="{{ $dept->name }}"
                            data-description="{{ $dept->description }}"
                            data-is_active="{{ $dept->is_active ? '1' : '0' }}">

                            <td>
                                <div class="custom-control custom-checkbox ms-1">
                                    <input type="checkbox" class="custom-control-input checkbox"
                                           id="chk_{{ $dept->id }}">
                                    <label class="custom-control-label" for="chk_{{ $dept->id }}"></label>
                                </div>
                            </td>
                            <td class="text-muted fs-12">{{ $loop->iteration }}</td>

                            {{-- Name --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded-3"
                                         style="width:38px;height:38px;
                                                background:rgba(99,102,241,.1);
                                                border:1.5px solid rgba(99,102,241,.2);">
                                        <i class="feather-briefcase"
                                           style="color:#6366f1;font-size:15px;"></i>
                                    </div>
                                    <span class="fw-semibold fs-13 text-dark">{{ $dept->name }}</span>
                                </div>
                            </td>

                            {{-- Description --}}
                            <td class="text-muted fs-12">
                                <span class="text-truncate d-inline-block" style="max-width:280px;">
                                    {{ $dept->description ?: '—' }}
                                </span>
                            </td>

                            {{-- Toggle --}}
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox"
                                           {{ $dept->is_active ? 'checked' : '' }}
                                           data-id="{{ $dept->id }}">
                                </div>
                            </td>

                            <td class="fs-12 text-muted">
                                {{ $dept->updatedBy?->name ?? ($dept->createdBy?->name ?? '—') }}
                            </td>

                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="javascript:void(0);" class="avatar-text avatar-md"
                                       title="Edit" onclick="openEdit(this)"
                                       data-row="dept-row-{{ $dept->id }}">
                                        <i class="feather feather-edit-3"></i>
                                    </a>
                                    <a href="javascript:void(0);"
                                       class="avatar-text avatar-md text-danger"
                                       title="Delete"
                                       onclick="deleteRow({{ $dept->id }}, '{{ addslashes($dept->name) }}')">
                                        <i class="feather feather-trash-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="feather-briefcase fs-30 d-block mb-2 opacity-50"></i>
                                No departments found. Click <strong>Add Department</strong> to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between py-3">
            <div class="text-muted fs-12">
                Showing <span id="showFrom">1</span>–<span id="showTo">10</span>
                of <span id="totalRows">{{ $departments->count() }}</span> entries
            </div>
            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.ajax-toast{
    position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;
    border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;
    font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);
    transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);
}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error  {background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}
.field-error{font-size:12px;color:#ef4444;margin-top:4px;display:none;}
.field-error.show{display:block;}
.form-control.input-error{border-color:#ef4444!important;}
.dept-row td{vertical-align:middle;}
</style>
@endpush

@push('scripts')
<script>
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
let editMode = false;

/* ── Toast ── */
let _tt;
function showToast(msg, type = 'success') {
    const t = document.getElementById('ajaxToast');
    document.getElementById('toastMsg').textContent  = msg;
    document.getElementById('toastIcon').textContent = type === 'success' ? '✅' : '❌';
    t.className = 'ajax-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
    t.classList.add('show');
    clearTimeout(_tt);
    _tt = setTimeout(() => t.classList.remove('show'), 4000);
}
function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }

/* ── Errors ── */
function clearErrors() {
    document.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('input-error'));
}
function fieldErr(fId, eId, msg) {
    document.getElementById(fId)?.classList.add('input-error');
    const e = document.getElementById(eId);
    if (e) { e.textContent = msg; e.classList.add('show'); }
}

/* ── Reset ── */
function resetForm() {
    document.getElementById('deptForm').reset();
    document.getElementById('editDeptId').value = '';
    clearErrors();
}

/* ── Open Add ── */
function openAdd() {
    editMode = false;
    resetForm();
    document.getElementById('canvasIcon').className    = 'feather-plus-circle me-2 text-primary';
    document.getElementById('canvasTitle').textContent  = 'Add Department';
    document.getElementById('deptBtnText').textContent  = 'Save';
    new bootstrap.Offcanvas(document.getElementById('deptCanvas')).show();
}

/* ── Open Edit ── */
function openEdit(btn) {
    editMode = true;
    resetForm();
    const d = document.getElementById(btn.dataset.row).dataset;
    document.getElementById('canvasIcon').className    = 'feather-edit-3 me-2 text-warning';
    document.getElementById('canvasTitle').textContent  = 'Edit Department';
    document.getElementById('deptBtnText').textContent  = 'Update';
    document.getElementById('editDeptId').value         = d.id;
    document.getElementById('dept_name').value          = d.name;
    document.getElementById('dept_description').value   = d.description || '';
    document.getElementById('dept_is_active').value     = d.is_active;
    new bootstrap.Offcanvas(document.getElementById('deptCanvas')).show();
}

/* ── Submit ── */
document.getElementById('deptForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const id  = document.getElementById('editDeptId').value;
    const btn = document.getElementById('submitBtn');
    const sp  = document.getElementById('deptSpinner');
    const ic  = document.getElementById('deptBtnIcon');
    const tx  = document.getElementById('deptBtnText');

    btn.disabled = true;
    sp.classList.remove('d-none');
    ic.classList.add('d-none');
    tx.textContent = 'Saving…';

    const payload = {
        name        : document.getElementById('dept_name').value,
        description : document.getElementById('dept_description').value,
        is_active   : document.getElementById('dept_is_active').value,
        _token      : CSRF,
    };

    const url    = editMode ? `/departments/${id}` : "{{ route('departments.store') }}";
    const method = editMode ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: {
            'Content-Type' : 'application/json',
            'X-CSRF-TOKEN' : CSRF,
            'Accept'       : 'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';

        if (d.status === 'success') {
            showToast(d.message, 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('deptCanvas'))?.hide();
            setTimeout(() => location.reload(), 1200);
        } else if (d.errors) {
            const map = { name: 'nameError', description: 'descriptionError' };
            Object.keys(d.errors).forEach(f =>
                fieldErr('dept_' + f, map[f] || f + 'Error', d.errors[f][0])
            );
            showToast('Please fix the errors below.', 'error');
        } else {
            showToast(d.message || 'Something went wrong!', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';
        showToast('Network error — try again.', 'error');
    });
});

/* ── Toggle ── */
document.querySelectorAll('.status-toggle').forEach(toggle => {
    toggle.addEventListener('change', function () {
        const id = this.dataset.id;
        fetch(`/departments/${id}/toggle`, {
            method  : 'PATCH',
            headers : { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => {
            showToast(d.message, d.status === 'success' ? 'success' : 'error');
            if (d.status === 'success') {
                const row = document.querySelector(`[data-id="${id}"].dept-row`);
                if (row) row.dataset.status = d.is_active ? 'active' : 'inactive';
                filterAndPaginate();
            } else {
                this.checked = !this.checked;
            }
        })
        .catch(() => { this.checked = !this.checked; showToast('Something went wrong!', 'error'); });
    });
});

/* ── Delete ── */
function deleteRow(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    fetch(`/departments/${id}`, {
        method  : 'DELETE',
        headers : { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            document.getElementById(`dept-row-${id}`)?.remove();
            showToast(d.message, 'success');
            filterAndPaginate();
        } else {
            showToast(d.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}

/* ── Check all ── */
document.getElementById('checkAll').addEventListener('change', function () {
    document.querySelectorAll('.checkbox').forEach(c => c.checked = this.checked);
});

/* ── Filter + Paginate ── */
let currentPage = 1, activeStatus = 'all';

function allRows() { return Array.from(document.querySelectorAll('.dept-row')); }

function filterAndPaginate() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const rows    = allRows();

    const filtered = rows.filter(r => {
        const ms  = !search || r.textContent.toLowerCase().includes(search);
        const mst = activeStatus === 'all' || r.dataset.status === activeStatus;
        return ms && mst;
    });

    rows.forEach(r => r.style.display = 'none');

    const total      = filtered.length;
    const totalPages = Math.ceil(total / perPage) || 1;
    if (currentPage > totalPages) currentPage = 1;

    const start = (currentPage - 1) * perPage;
    const end   = Math.min(start + perPage, total);

    filtered.forEach((r, i) => r.style.display = (i >= start && i < end) ? '' : 'none');

    const no = document.getElementById('noDataRow');
    if (no) no.style.display = total === 0 ? '' : 'none';

    document.getElementById('showFrom').textContent  = total ? start + 1 : 0;
    document.getElementById('showTo').textContent    = end;
    document.getElementById('totalRows').textContent = total;
    renderPagination(totalPages);
}

function renderPagination(tp) {
    const ul = document.getElementById('paginationLinks');
    ul.innerHTML = '';
    const mk = (lbl, pg, dis) => {
        const li = document.createElement('li');
        li.className = `page-item ${dis ? 'disabled' : ''} ${lbl === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${lbl}</a>`;
        li.addEventListener('click', e => {
            e.preventDefault();
            if (!dis) { currentPage = pg; filterAndPaginate(); }
        });
        ul.appendChild(li);
    };
    mk('«', currentPage - 1, currentPage === 1);
    for (let i = 1; i <= tp; i++) mk(i, i, false);
    mk('»', currentPage + 1, currentPage === tp);
}

document.getElementById('searchInput').addEventListener('input',   () => { currentPage = 1; filterAndPaginate(); });
document.getElementById('perPageSelect').addEventListener('change', () => { currentPage = 1; filterAndPaginate(); });
document.querySelectorAll('.filter-status').forEach(el => {
    el.addEventListener('click', function () {
        activeStatus = this.dataset.status;
        currentPage  = 1;
        filterAndPaginate();
    });
});

/* ── CSV Export ── */
function exportCSV() {
    const rows = allRows().filter(r => r.style.display !== 'none');
    let csv = 'Name,Description,Status\n';
    rows.forEach(r => {
        const d = r.dataset;
        csv += `"${d.name}","${d.description || ''}","${d.is_active === '1' ? 'Active' : 'Inactive'}"\n`;
    });
    const a = document.createElement('a');
    a.href     = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    a.download = 'departments.csv';
    a.click();
}

document.addEventListener('DOMContentLoaded', () => filterAndPaginate());
</script>
@endpush