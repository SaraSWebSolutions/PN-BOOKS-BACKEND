@extends('layouts.app')
@section('title', 'Orders')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Orders</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Bookstore</li>
            <li class="breadcrumb-item">Orders</li>
        </ul>
    </div>
</div>

<div class="main-content">

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total','value'=>$stats['total'],'icon'=>'feather-shopping-bag','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
            ['label'=>'Pending','value'=>$stats['pending'],'icon'=>'feather-clock','bg'=>'rgba(234,179,8,.1)','color'=>'#eab308','fw'=>'text-warning'],
            ['label'=>'Processing','value'=>$stats['processing'],'icon'=>'feather-loader','bg'=>'rgba(59,130,246,.1)','color'=>'#3b82f6','fw'=>'text-primary'],
            ['label'=>'Completed','value'=>$stats['completed'],'icon'=>'feather-check-circle','bg'=>'rgba(16,185,129,.1)','color'=>'#10b981','fw'=>'text-success'],
            ['label'=>'Cancelled/Failed','value'=>$stats['cancelled'],'icon'=>'feather-x-circle','bg'=>'rgba(239,68,68,.1)','color'=>'#ef4444','fw'=>'text-danger'],
        ] as $s)
        <div class="col-6 col-lg-2-4" style="flex:0 0 20%;max-width:20%;">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="hstack justify-content-between">
                        <div>
                            <div class="text-muted fs-12 mb-1">{{ $s['label'] }}</div>
                            <div class="fs-22 fw-bold {{ $s['fw'] ?? '' }}">{{ $s['value'] }}</div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:10px;background:{{ $s['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $s['color'] }};">
                            <i class="{{ $s['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3 flex-wrap">
            <span class="fw-semibold">All Orders</span>

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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search orders…">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order No.</th>
                            <th>Customer</th>
                            <th class="text-center">Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Payment Status</th>
                            <th style="min-width:160px;">Order Status</th>
                            <th>Placed At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orderTableBody">
                        @forelse($orders as $order)
                        <tr class="order-row" id="order-row-{{ $order->id }}" data-status="{{ $order->status }}">
                            <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                            <td class="fw-semibold fs-13 text-dark">{{ $order->order_number }}</td>
                            <td class="fs-12">{{ $order->user->name ?? 'Guest' }}</td>
                            <td class="text-center">{{ $order->items->count() }}</td>
                            <td class="fs-12">{{ number_format($order->total_amount, 2) }} {{ $order->currency->code ?? '' }}</td>
                            <td>
                                @php
                                    // Colour-code the payment method badge so each method is visually distinct at a glance.
                                    $payMethodBadge = match(strtolower($order->payment_method ?? '')) {
                                        'card', 'credit_card', 'debit_card' => 'primary',
                                        'stripe' => 'purple',
                                        'paypal' => 'info',
                                        'bank_transfer', 'bank' => 'secondary',
                                        'cod', 'cash_on_delivery' => 'warning',
                                        'wallet' => 'success',
                                        default => 'dark',
                                    };
                                @endphp
                                <span class="badge bg-{{ $payMethodBadge }}-subtle text-{{ $payMethodBadge }} text-uppercase">
                                    {{ str_replace('_', ' ', $order->payment_method) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $payBadge = match($order->payment_status) {
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $payBadge }}-subtle text-{{ $payBadge }}">{{ ucfirst($order->payment_status) }}</span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select" data-id="{{ $order->id }}">
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>
                                            {{ ucfirst($st) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="fs-12 text-muted">{{ optional($order->placed_at)->format('d M Y') }}</td>
                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="{{ route('orders.show', $order->id) }}" class="avatar-text avatar-md" title="View Detail">
                                        <i class="feather feather-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="feather-shopping-bag fs-30 d-block mb-2 opacity-50"></i>
                                No orders found yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-3" id="paginationFooter">
            <span class="text-muted fs-12" id="entriesInfo">Showing 0 entries</span>
            <div class="custom-pagination" id="paginationContainer"></div>
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
.entries-select{width:auto;border-radius:8px;border:1px solid #e2e5ec;font-size:13px;padding:4px 28px 4px 10px;}
.custom-pagination ul{list-style:none;display:flex;gap:4px;margin:0;padding:0;}
.custom-pagination .page-item .page-link{border:1px solid #e2e5ec;border-radius:8px;color:#4f5b76;font-size:13px;font-weight:500;min-width:32px;height:32px;display:flex;align-items:center;justify-content:center;padding:0 8px;margin:0;cursor:pointer;background:#fff;text-decoration:none;user-select:none;}
.custom-pagination .page-item .page-link:hover:not(.disabled):not(.active){background:#f2eeff;color:#7b5cf0;border-color:#e2e5ec;}
.custom-pagination .page-item.active .page-link{background:#7b5cf0;border-color:#7b5cf0;color:#fff;cursor:default;}
.custom-pagination .page-item.disabled .page-link{color:#c3c9d4;background:#fff;cursor:default;}

/* Extra badge colour for payment methods that don't have a stock Bootstrap "subtle" variant */
.bg-purple-subtle{ background:rgba(147,51,234,.12) !important; }
.text-purple{ color:#9333ea !important; }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Route names resolved server-side by Blade. ":id" is a placeholder swapped
// in on the client before each request — no hardcoded URLs anywhere in JS.
const ROUTES = {
    updateStatus: "{{ route('orders.updateStatus', ':id') }}",
};

/* Front-end pagination + search — same pattern as Categories page */
(function () {
    const rowSelector   = '.order-row';
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
        visibleRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });

        visibleRows.slice(start, end).forEach((r, i) => {
            const idxCell = r.querySelector('.row-index');
            if (idxCell) idxCell.textContent = start + i + 1;
        });

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
            if (!disabled && !active) a.addEventListener('click', () => { currentPage = page; render(); });
            li.appendChild(a);
            ul.appendChild(li);
        }

        addBtn('«', currentPage - 1, { disabled: currentPage === 1 });
        const windowSize = 7;
        let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
        let endPage = Math.min(totalPages, startPage + windowSize - 1);
        startPage = Math.max(1, endPage - windowSize + 1);
        for (let p = startPage; p <= endPage; p++) addBtn(p, p, { active: p === currentPage });
        addBtn('»', currentPage + 1, { disabled: currentPage === totalPages });

        paginationBox.appendChild(ul);
    }

    perPageSelect.addEventListener('change', function () {
        perPage = this.value === 'all' ? 'all' : parseInt(this.value, 10);
        currentPage = 1;
        render();
    });

    searchInput.addEventListener('input', function () { currentPage = 1; render(); });

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

/* Status update dropdown — AJAX PATCH, no page reload */
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
        const id = this.dataset.id;
        const newStatus = this.value;
        const previousValue = this.dataset.prev || this.value;
        const url = ROUTES.updateStatus.replace(':id', id);

        fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: newStatus }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                showToast(d.message, 'success');
                this.dataset.prev = newStatus;
                document.getElementById(`order-row-${id}`).dataset.status = newStatus;
            } else {
                showToast(d.message || 'Failed to update status.', 'error');
                this.value = previousValue;
            }
        })
        .catch(() => {
            showToast('Network error — try again.', 'error');
            this.value = previousValue;
        });
    });
    select.dataset.prev = select.value;
});
</script>
@endpush