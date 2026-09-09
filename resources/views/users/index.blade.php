@extends('layouts.app')

@section('title', 'User Management')

@section('content')

{{-- Toast --}}
<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">User Management</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Users</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex d-md-none">
                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">

                {{-- Filter Dropdown --}}
                <div class="dropdown">
                    <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0,10" data-bs-auto-close="outside">
                        <i class="feather-filter"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="all">
                            <span class="wd-7 ht-7 bg-secondary rounded-circle d-inline-block me-3"></span> All Users
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="active">
                            <span class="wd-7 ht-7 bg-success rounded-circle d-inline-block me-3"></span> Active
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item filter-status" data-status="inactive">
                            <span class="wd-7 ht-7 bg-danger rounded-circle d-inline-block me-3"></span> Inactive
                        </a>

                        @if($roles->count())
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0);" class="dropdown-item filter-role" data-role="all">
                            <span class="wd-7 ht-7 bg-secondary rounded-circle d-inline-block me-3"></span> All Roles
                        </a>
                        @foreach($roles as $role)
                        <a href="javascript:void(0);" class="dropdown-item filter-role" data-role="{{ $role->name }}">
                            <span class="wd-7 ht-7 bg-primary rounded-circle d-inline-block me-3"></span> {{ ucfirst($role->name) }}
                        </a>
                        @endforeach
                        @endif
                    </div>
                </div>

                {{-- Export Dropdown --}}
                <div class="dropdown">
                    <a class="btn btn-icon btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0,10">
                        <i class="feather-paperclip"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item" onclick="exportTable('csv')">
                            <i class="bi bi-filetype-csv me-3"></i> CSV
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item" onclick="exportTable('excel')">
                            <i class="bi bi-filetype-exe me-3"></i> Excel
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0);" class="dropdown-item" onclick="window.print()">
                            <i class="bi bi-printer me-3"></i> Print
                        </a>
                    </div>
                </div>

                @can('create users')
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="feather-user-plus me-2"></i>
                    <span>Create User</span>
                </a>
                @endcan

            </div>
        </div>
        <div class="d-md-none d-flex align-items-center">
            <a href="javascript:void(0)" class="page-header-right-open-toggle">
                <i class="feather-align-right fs-20"></i>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">

                {{-- Search Bar --}}
                <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-12">Show</span>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width:70px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted fs-12">entries</span>
                    </div>
                    <div class="input-group" style="max-width: 250px;">
                        <span class="input-group-text"><i class="feather-search"></i></span>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search users...">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="userTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">
                                        <div class="custom-control custom-checkbox ms-1">
                                            <input type="checkbox" class="custom-control-input" id="checkAll">
                                            <label class="custom-control-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th class="sortable" data-col="0">User <i class="feather-chevrons-up-down ms-1 fs-11"></i></th>
                                    <th class="sortable" data-col="1">Email <i class="feather-chevrons-up-down ms-1 fs-11"></i></th>
                                    <th>Mobile No</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="sortable" data-col="5">Created <i class="feather-chevrons-up-down ms-1 fs-11"></i></th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                @forelse($users as $user)
                                <tr class="single-item user-row"
                                    id="user-row-{{ $user->id }}"
                                    data-status="{{ $user->status }}"
                                    data-role="{{ $user->roles->pluck('name')->implode(',') }}">
                                    <td>
                                        <div class="custom-control custom-checkbox ms-1">
                                            <input type="checkbox" class="custom-control-input checkbox" id="check_{{ $user->id }}">
                                            <label class="custom-control-label" for="check_{{ $user->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('users.show', $user) }}" class="hstack gap-3">
                                            <div class="avatar-image avatar-md bg-primary text-white d-flex align-items-center justify-content-center"
                                                 style="width:38px;height:38px;border-radius:50%;font-weight:bold;font-size:16px;flex-shrink:0;overflow:hidden;">
                                                @if($user->photo)
                                                    <img src="{{ $user->photo_url }}" style="width:100%;height:100%;object-fit:cover;">
                                                @else
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-truncate-1-line fw-semibold d-block">{{ $user->name }}</span>
                                                @if($user->employee_id)
                                                    <span class="text-muted fs-11">
                                                        <i class="feather-hash fs-10"></i>{{ $user->employee_id }}
                                                    </span>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone ?? '—' }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-soft-primary text-primary text-capitalize">{{ $role->name }}</span>
                                        @endforeach
                                        @if($user->roles->isEmpty())
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->status === 'active')
                                            <span class="badge bg-soft-success text-success">Active</span>
                                        @else
                                            <span class="badge bg-soft-danger text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('users.show', $user) }}" class="avatar-text avatar-md" title="View">
                                                <i class="feather feather-eye"></i>
                                            </a>
                                            @can('edit users')
                                            <a href="{{ route('users.edit', $user) }}" class="avatar-text avatar-md" title="Edit">
                                                <i class="feather feather-edit-3"></i>
                                            </a>
                                            @endcan
                                            @can('delete users')
                                            @if($user->id !== auth()->id())
                                            <a href="javascript:void(0);" class="avatar-text avatar-md text-danger"
                                               onclick="deleteUser({{ $user->id }})" title="Delete">
                                                <i class="feather feather-trash-2"></i>
                                            </a>
                                            @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noDataRow">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="feather-users fs-30 d-block mb-2"></i>
                                        No users found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between py-3">
                    <div class="text-muted fs-12" id="paginationInfo">
                        Showing <span id="showFrom">1</span> to <span id="showTo">10</span>
                        of <span id="totalRows">{{ $users->count() }}</span> entries
                    </div>
                    <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.ajax-toast {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    min-width: 320px; max-width: 400px; border-radius: 12px;
    padding: 14px 18px; display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-weight: 500; box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ajax-toast.show { transform: translateX(0); }
.ajax-toast.toast-success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
.ajax-toast.toast-error   { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
.ajax-toast .toast-close  { margin-left: auto; background: none; border: none; cursor: pointer; font-size: 16px; color: inherit; opacity: 0.6; }
.sortable { cursor: pointer; user-select: none; }
.sortable:hover { background: rgba(0,0,0,0.03); }
</style>
@endpush

@push('scripts')
<script>
let toastTimer = null;
function showToast(message, type = 'success') {
    const toast  = document.getElementById('ajaxToast');
    const msgEl  = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    msgEl.textContent = message;
    toast.className   = 'ajax-toast';
    toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
    iconEl.textContent = type === 'success' ? '✅' : '❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
}
function hideToast() {
    document.getElementById('ajaxToast').classList.remove('show');
}

function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    fetch(`/users/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`user-row-${id}`)?.remove();
            showToast(data.message, 'success');
            filterAndPaginate();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}

document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.checkbox').forEach(cb => cb.checked = this.checked);
});

let currentPage   = 1;
let activeStatus  = 'all';
let activeRole    = 'all';

function getAllRows() {
    return Array.from(document.querySelectorAll('.user-row'));
}

function filterAndPaginate() {
    const search   = document.getElementById('searchInput').value.toLowerCase();
    const perPage  = parseInt(document.getElementById('perPageSelect').value);
    const allRows  = getAllRows();

    const filtered = allRows.filter(row => {
        const text        = row.textContent.toLowerCase();
        const rowStatus    = row.dataset.status;
        // data-role can hold multiple comma-separated roles per user
        const rowRoles     = (row.dataset.role || '').split(',').filter(Boolean);
        const matchSearch  = !search || text.includes(search);
        const matchStatus  = activeStatus === 'all' || rowStatus === activeStatus;
        const matchRole    = activeRole === 'all' || rowRoles.includes(activeRole);
        return matchSearch && matchStatus && matchRole;
    });

    allRows.forEach(row => row.style.display = 'none');

    const total     = filtered.length;
    const totalPages = Math.ceil(total / perPage) || 1;
    if (currentPage > totalPages) currentPage = 1;

    const start = (currentPage - 1) * perPage;
    const end   = Math.min(start + perPage, total);

    filtered.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    const noDataRow = document.getElementById('noDataRow');
    if (noDataRow) noDataRow.style.display = total === 0 ? '' : 'none';

    document.getElementById('showFrom').textContent  = total === 0 ? 0 : start + 1;
    document.getElementById('showTo').textContent    = end;
    document.getElementById('totalRows').textContent = total;

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const ul = document.getElementById('paginationLinks');
    ul.innerHTML = '';

    const prev = document.createElement('li');
    prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prev.innerHTML = `<a class="page-link" href="javascript:void(0);">«</a>`;
    prev.addEventListener('click', () => { if (currentPage > 1) { currentPage--; filterAndPaginate(); }});
    ul.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="javascript:void(0);">${i}</a>`;
        li.addEventListener('click', () => { currentPage = i; filterAndPaginate(); });
        ul.appendChild(li);
    }

    const next = document.createElement('li');
    next.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    next.innerHTML = `<a class="page-link" href="javascript:void(0);">»</a>`;
    next.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; filterAndPaginate(); }});
    ul.appendChild(next);
}

document.getElementById('searchInput').addEventListener('input', function() {
    currentPage = 1;
    filterAndPaginate();
});

document.getElementById('perPageSelect').addEventListener('change', function() {
    currentPage = 1;
    filterAndPaginate();
});

document.querySelectorAll('.filter-status').forEach(el => {
    el.addEventListener('click', function() {
        activeStatus = this.dataset.status;
        currentPage  = 1;
        filterAndPaginate();
    });
});

document.querySelectorAll('.filter-role').forEach(el => {
    el.addEventListener('click', function() {
        activeRole  = this.dataset.role;
        currentPage = 1;
        filterAndPaginate();
    });
});

function exportTable(type) {
    const rows  = Array.from(document.querySelectorAll('.user-row')).filter(r => r.style.display !== 'none');
    const heads = ['Name', 'Email', 'Phone', 'Role', 'Status', 'Created'];

    if (type === 'csv') {
        let csv = heads.join(',') + '\n';
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            csv += [
                cells[1]?.textContent.trim(),
                cells[2]?.textContent.trim(),
                cells[3]?.textContent.trim(),
                cells[4]?.textContent.trim(),
                cells[5]?.textContent.trim(),
                cells[6]?.textContent.trim(),
            ].map(v => `"${v}"`).join(',') + '\n';
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'users.csv';
        a.click();
    } else if (type === 'excel') {
        showToast('Excel export — integrate with Laravel Excel for server-side export.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => filterAndPaginate());
</script>
@endpush