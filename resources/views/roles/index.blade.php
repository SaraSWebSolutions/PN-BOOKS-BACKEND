@extends('layouts.app')
@section('title', 'Roles Management')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Roles Management</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Roles</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                @can('create roles')
                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                    <i class="feather-plus me-2"></i> Add Role
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">

                <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-12">Show</span>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width:70px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-muted fs-12">entries</span>
                    </div>
                    <div class="input-group" style="max-width:250px;">
                        <span class="input-group-text"><i class="feather-search"></i></span>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search roles...">
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="roleTableBody">
                                @forelse($roles as $role)
                                <tr class="role-row" id="role-row-{{ $role->id }}">
                                    <td>
                                        <div class="hstack gap-3">
                                            <div class="avatar-text avatar-md
                                                @if($role->name === 'admin') bg-primary
                                                @elseif($role->name === 'owner') bg-indigo
                                                @elseif($role->name === 'manager') bg-warning
                                                @elseif($role->name === 'cashier') bg-teal
                                                @else bg-secondary @endif
                                                text-white"
                                                style="width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                                                {{ strtoupper(substr($role->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="fw-semibold text-capitalize d-block">{{ $role->name }}</span>
                                                <span class="fs-12 text-muted">{{ $role->guard_name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary">
                                            {{ $role->permissions->count() }} Permissions
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-info text-info">
                                            {{ $role->users()->count() }} Users
                                        </span>
                                    </td>
                                    <td>{{ $role->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('roles.show', $role) }}" class="avatar-text avatar-md" title="View">
                                                <i class="feather feather-eye"></i>
                                            </a>
                                            @can('edit roles')
                                            <a href="{{ route('roles.edit', $role) }}" class="avatar-text avatar-md" title="Edit">
                                                <i class="feather feather-edit-3"></i>
                                            </a>
                                            @endcan
                                            @can('delete roles')
                                            @if(!in_array($role->name, ['admin','owner','manager','cashier']))
                                            <a href="javascript:void(0);" class="avatar-text avatar-md text-danger"
                                               onclick="deleteRole({{ $role->id }})" title="Delete">
                                                <i class="feather feather-trash-2"></i>
                                            </a>
                                            @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No roles found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between py-3">
                    <div class="text-muted fs-12">
                        Showing <span id="showFrom">1</span> to <span id="showTo">10</span>
                        of <span id="totalRows">{{ $roles->count() }}</span> entries
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
    position:fixed;top:20px;right:20px;z-index:9999;min-width:320px;max-width:400px;
    border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;
    font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.15);
    transform:translateX(120%);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:0.6;}
</style>
@endpush

@push('scripts')
<script>
let toastTimer = null;
function showToast(message, type='success') {
    const toast=document.getElementById('ajaxToast');
    const msgEl=document.getElementById('toastMessage');
    const iconEl=document.getElementById('toastIcon');
    msgEl.textContent=message;
    toast.className='ajax-toast';
    toast.classList.add(type==='success'?'toast-success':'toast-error');
    iconEl.textContent=type==='success'?'✅':'❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>toast.classList.remove('show'),4000);
}
function hideToast(){document.getElementById('ajaxToast').classList.remove('show');}

function deleteRole(id) {
    if (!confirm('Are you sure you want to delete this role?')) return;
    fetch(`/roles/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`role-row-${id}`)?.remove();
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}

// Search + Pagination
let currentPage = 1;
function getAllRows() { return Array.from(document.querySelectorAll('.role-row')); }

function filterAndPaginate() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const perPage = parseInt(document.getElementById('perPageSelect').value);
    const allRows = getAllRows();
    const filtered = allRows.filter(row => !search || row.textContent.toLowerCase().includes(search));
    allRows.forEach(row => row.style.display = 'none');
    const total = filtered.length;
    const totalPages = Math.ceil(total / perPage) || 1;
    if (currentPage > totalPages) currentPage = 1;
    const start = (currentPage - 1) * perPage;
    const end = Math.min(start + perPage, total);
    filtered.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
    document.getElementById('showFrom').textContent = total === 0 ? 0 : start + 1;
    document.getElementById('showTo').textContent = end;
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

document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
document.getElementById('perPageSelect').addEventListener('change', () => { currentPage = 1; filterAndPaginate(); });
document.addEventListener('DOMContentLoaded', () => filterAndPaginate());
</script>
@endpush