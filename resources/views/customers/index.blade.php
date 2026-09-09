@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Customer Management</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Customers</li>
        </ul>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">

                <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3 flex-wrap">
                    <span class="fw-semibold">All Customers</span>

                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted fs-12">Show</span>
                            <select id="perPageSelect" class="form-select form-select-sm entries-select">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all">All</option>
                            </select>
                            <span class="text-muted fs-12">entries</span>
                        </div>

                        <div class="input-group input-group-sm" style="max-width:220px;">
                            <span class="input-group-text"><i class="feather-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search customers…">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>City</th>
                                    <th>Loyalty Points</th>
                                    <th class="text-center">Status</th>
                                    <th>Joined</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                @forelse($customers as $customer)
                                <tr class="customer-row"
                                    id="customer-row-{{ $customer->id }}"
                                    data-status="{{ $customer->status }}">
                                    <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('customers.show', $customer) }}" class="hstack gap-3">
                                            <div class="avatar-image avatar-md bg-primary text-white d-flex align-items-center justify-content-center"
                                                 style="width:38px;height:38px;border-radius:50%;font-weight:bold;font-size:16px;flex-shrink:0;overflow:hidden;">
                                                @if($customer->customerProfile?->profile_photo)
                                                    <img src="{{ $customer->customerProfile->profile_photo_url }}" style="width:100%;height:100%;object-fit:cover;">
                                                @else
                                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                                @endif
                                            </div>
                                            <span class="text-truncate-1-line fw-semibold d-block">{{ $customer->name }}</span>
                                        </a>
                                    </td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->phone ?? '—' }}</td>
                                    <td>{{ $customer->customerProfile->city ?? '—' }}</td>
                                    <td>{{ $customer->customerProfile->loyalty_points ?? 0 }}</td>
                                    <td class="text-center">
                                        @can('edit users')
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input type="checkbox"
                                                   class="form-check-input status-toggle"
                                                   role="switch"
                                                   data-id="{{ $customer->id }}"
                                                   {{ $customer->status === 'active' ? 'checked' : '' }}>
                                        </div>
                                        @else
                                            @if($customer->status === 'active')
                                                <span class="badge bg-soft-success text-success">Active</span>
                                            @else
                                                <span class="badge bg-soft-danger text-danger">Inactive</span>
                                            @endif
                                        @endcan
                                    </td>
                                    <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('customers.show', $customer) }}" class="avatar-text avatar-md" title="View">
                                                <i class="feather feather-eye"></i>
                                            </a>
                                            @can('edit users')
                                            <a href="{{ route('customers.edit', $customer) }}" class="avatar-text avatar-md" title="Edit">
                                                <i class="feather feather-edit-3"></i>
                                            </a>
                                            @endcan
                                            @can('delete users')
                                            <a href="javascript:void(0);" class="avatar-text avatar-md text-danger"
                                               onclick="deleteCustomer({{ $customer->id }})" title="Delete">
                                                <i class="feather feather-trash-2"></i>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noDataRow">
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="feather-users fs-30 d-block mb-2 opacity-50"></i>
                                        No customers found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination footer — built entirely by JS, same as categories --}}
                <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-3" id="paginationFooter">
                    <span class="text-muted fs-12" id="entriesInfo">Showing 0 entries</span>
                    <div class="custom-pagination" id="paginationContainer"></div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}

.entries-select{
    width:auto;
    border-radius:8px;
    border:1px solid #e2e5ec;
    font-size:13px;
    padding:4px 28px 4px 10px;
}
.entries-select:focus{
    border-color:#7b5cf0;
    box-shadow:0 0 0 3px rgba(123,92,240,.12);
}

.custom-pagination ul{
    list-style:none;
    display:flex;
    gap:4px;
    margin:0;
    padding:0;
}
.custom-pagination .page-item .page-link{
    border:1px solid #e2e5ec;
    border-radius:8px;
    color:#4f5b76;
    font-size:13px;
    font-weight:500;
    min-width:32px;
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0 8px;
    margin:0;
    cursor:pointer;
    background:#fff;
    text-decoration:none;
    user-select:none;
}
.custom-pagination .page-item .page-link:hover:not(.disabled):not(.active){
    background:#f2eeff;
    color:#7b5cf0;
    border-color:#e2e5ec;
}
.custom-pagination .page-item.active .page-link{
    background:#7b5cf0;
    border-color:#7b5cf0;
    color:#fff;
    cursor:default;
}
.custom-pagination .page-item.disabled .page-link{
    color:#c3c9d4;
    background:#fff;
    cursor:default;
}
.form-check.form-switch .form-check-input{cursor:pointer;}
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ========================================================
   Front-end pagination — identical pattern to categories index.
   Works on rows already rendered by Blade, no reload needed.
   ======================================================== */
(function () {
    const rowSelector   = '.customer-row';
    const perPageSelect = document.getElementById('perPageSelect');
    const searchInput   = document.getElementById('searchInput');
    const entriesInfo   = document.getElementById('entriesInfo');
    const paginationBox = document.getElementById('paginationContainer');

    let currentPage = 1;
    let perPage = 10;

    function matchesSearch(row, q) {
        if (!q) return true;
        return row.textContent.toLowerCase().includes(q);
    }

    function render() {
        const q = (searchInput.value || '').toLowerCase().trim();
        const allRows = Array.from(document.querySelectorAll(rowSelector));
        const visibleRows = allRows.filter(r => matchesSearch(r, q));
        const total = visibleRows.length;
        const effectivePerPage = perPage === 'all' ? Math.max(total, 1) : perPage;
        const totalPages = Math.max(1, Math.ceil(total / effectivePerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * effectivePerPage;
        const end = start + effectivePerPage;

        allRows.forEach(r => r.style.display = 'none');
        visibleRows.forEach((r, i) => {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        visibleRows.slice(start, end).forEach((r, i) => {
            const idxCell = r.querySelector('.row-index');
            if (idxCell) idxCell.textContent = start + i + 1;
        });

        const noDataRow = document.getElementById('noDataRow');
        if (noDataRow) noDataRow.style.display = total === 0 ? '' : 'none';

        entriesInfo.textContent = total === 0
            ? 'No entries found'
            : `Showing ${start + 1}–${Math.min(end, total)} of ${total} entries`;

        renderPaginationButtons(totalPages);
    }

    function renderPaginationButtons(totalPages) {
        paginationBox.innerHTML = '';
        if (totalPages <= 1) return;

        const ul = document.createElement('ul');
        ul.className = 'pagination';

        function addBtn(label, page, { disabled = false, active = false } = {}) {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.textContent = label;
            if (!disabled && !active) {
                a.addEventListener('click', () => { currentPage = page; render(); });
            }
            li.appendChild(a);
            ul.appendChild(li);
        }

        addBtn('«', currentPage - 1, { disabled: currentPage === 1 });

        const windowSize = 7;
        let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
        let endPage = Math.min(totalPages, startPage + windowSize - 1);
        startPage = Math.max(1, endPage - windowSize + 1);

        for (let p = startPage; p <= endPage; p++) {
            addBtn(p, p, { active: p === currentPage });
        }

        addBtn('»', currentPage + 1, { disabled: currentPage === totalPages });

        paginationBox.appendChild(ul);
    }

    perPageSelect.addEventListener('change', function () {
        perPage = this.value === 'all' ? 'all' : parseInt(this.value, 10);
        currentPage = 1;
        render();
    });

    searchInput.addEventListener('input', function () {
        currentPage = 1;
        render();
    });

    window.__custPaginationRender = render;

    render();
})();

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

function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    fetch(`/customers/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`customer-row-${id}`)?.remove();
            showToast(data.message, 'success');
            window.__custPaginationRender && window.__custPaginationRender();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}

// Active/Inactive toggle
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('status-toggle')) return;

    const checkbox = e.target;
    const id       = checkbox.dataset.id;
    const row      = document.getElementById(`customer-row-${id}`);
    const prevChecked = !checkbox.checked;

    checkbox.disabled = true;

    fetch(`/customers/${id}/toggle-status`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            checkbox.checked = data.new_status === 'active';
            row.dataset.status = data.new_status;
            showToast(data.message, 'success');
        } else {
            checkbox.checked = prevChecked;
            showToast(data.message || 'Could not update status.', 'error');
        }
    })
    .catch(() => {
        checkbox.checked = prevChecked;
        showToast('Something went wrong!', 'error');
    })
    .finally(() => { checkbox.disabled = false; });
});
</script>
@endpush