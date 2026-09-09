@extends('layouts.app')
@section('title', 'Order Detail')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Order {{ $order->order_number }}</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
            <li class="breadcrumb-item">{{ $order->order_number }}</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto d-flex gap-2">
        <button type="button" class="btn btn-light-brand" onclick="window.print()">
            <i class="feather-printer me-2"></i>Print
        </button>
        <a href="{{ route('orders.index') }}" class="btn btn-light-brand">
            <i class="feather-arrow-left me-2"></i>Back to Orders
        </a>
    </div>
</div>

<div class="main-content order-detail-page">

    {{-- ═══ Order summary strip ═══ --}}
    <div class="card border-0 shadow-sm mb-3 order-summary-card">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3 col-6">
                    <div class="fs-11 text-muted text-uppercase mb-1">Order Number</div>
                    <div class="fw-bold fs-14">{{ $order->order_number }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="fs-11 text-muted text-uppercase mb-1">Placed On</div>
                    <div class="fw-semibold fs-13">{{ optional($order->placed_at)->format('d M Y') }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="fs-11 text-muted text-uppercase mb-1">Items</div>
                    <div class="fw-semibold fs-13">{{ $order->items->count() }} item{{ $order->items->count() === 1 ? '' : 's' }}</div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="fs-11 text-muted text-uppercase mb-1">Order Total</div>
                    <div class="fw-bold fs-16 text-primary">{{ number_format($order->total_amount, 2) }} {{ $order->currency->code ?? '' }}</div>
                </div>
            </div>

            @php
                $flow = ['pending', 'processing', 'shipped', 'completed'];
                $isTerminalFail = in_array($order->status, ['cancelled', 'failed']);
                $currentIdx = array_search($order->status, $flow);
            @endphp
            <div class="order-stepper mt-4 {{ $isTerminalFail ? 'is-failed' : '' }}">
                @if($isTerminalFail)
                    <div class="d-flex align-items-center gap-2 text-danger fw-semibold fs-13">
                        <i class="feather-x-circle"></i>
                        This order was {{ $order->status }}.
                    </div>
                @else
                    @foreach($flow as $i => $step)
                        <div class="stepper-step {{ $i <= $currentIdx ? 'is-done' : '' }} {{ $i === $currentIdx ? 'is-current' : '' }}">
                            <div class="stepper-dot"><i class="feather-check"></i></div>
                            <div class="stepper-label">{{ ucfirst($step) }}</div>
                        </div>
                        @if(!$loop->last)<div class="stepper-line {{ $i < $currentIdx ? 'is-done' : '' }}"></div>@endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- LEFT: Order items + addresses --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="feather-package text-primary"></i>Order Items
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Format</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td class="fw-semibold fs-13">{{ $item->title }}</td>
                                    <td class="fs-12 text-muted">
                                        <span class="badge bg-light-primary text-primary">{{ $item->format_name }}</span>
                                    </td>
                                    <td class="text-center fs-13">{{ $item->quantity }}</td>
                                    <td class="text-end fs-13">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-semibold fs-13">{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-semibold fs-13">Subtotal</td>
                                    <td class="text-end fs-13">{{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                @if($order->shipping_amount)
                                <tr>
                                    <td colspan="4" class="text-end fs-13 text-muted">Shipping</td>
                                    <td class="text-end fs-13">{{ number_format($order->shipping_amount, 2) }}</td>
                                </tr>
                                @endif
                                @if($order->discount_amount)
                                <tr>
                                    <td colspan="4" class="text-end fs-13 text-muted">Discount</td>
                                    <td class="text-end fs-13 text-danger">-{{ number_format($order->discount_amount, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="order-total-row">
                                    <td colspan="4" class="text-end fw-bold fs-14">Total</td>
                                    <td class="text-end fw-bold fs-14 text-primary">{{ number_format($order->total_amount, 2) }} {{ $order->currency->code ?? '' }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header fw-semibold d-flex align-items-center gap-2">
                            <i class="feather-map-pin text-primary"></i>Shipping Address
                        </div>
                        <div class="card-body fs-13">
                            @php $addr = $order->shippingAddress; @endphp
                            @if($addr)
                                <div class="fw-semibold mb-1">{{ $addr->full_name }}</div>
                                <div class="text-muted mb-2">{{ $addr->phone }}</div>
                                <div>{{ $addr->address_line1 }}</div>
                                @if($addr->address_line2)<div>{{ $addr->address_line2 }}</div>@endif
                                <div>{{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}</div>
                                <div class="fw-semibold mt-1">{{ $addr->country->name ?? '' }}</div>
                            @else
                                <span class="text-muted fst-italic">No shipping address recorded.</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header fw-semibold d-flex align-items-center gap-2">
                            <i class="feather-file-text text-primary"></i>Billing Address
                        </div>
                        <div class="card-body fs-13">
                            @php $baddr = $order->billingAddress; @endphp
                            @if($baddr)
                                <div class="fw-semibold mb-1">{{ $baddr->full_name }}</div>
                                <div class="text-muted mb-2">{{ $baddr->phone }}</div>
                                <div>{{ $baddr->address_line1 }}</div>
                                @if($baddr->address_line2)<div>{{ $baddr->address_line2 }}</div>@endif
                                <div>{{ $baddr->city }}, {{ $baddr->state }} {{ $baddr->postal_code }}</div>
                                <div class="fw-semibold mt-1">{{ $baddr->country->name ?? '' }}</div>
                            @else
                                <span class="text-muted fst-italic">Same as shipping.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT: Status, customer, payment --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="feather-refresh-cw text-primary"></i>Order Status
                </div>
                <div class="card-body">
                    <label class="form-label fs-12 text-muted">Current Status</label>
                    <select id="orderStatusSelect" class="form-select mb-3" data-id="{{ $order->id }}">
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary w-100" id="updateStatusBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="statusSpinner"></span>
                        <i class="feather-save me-2" id="statusBtnIcon"></i>
                        Update Status
                    </button>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-12 text-muted">Payment Status</span>
                        @php
                            $payBadge = match($order->payment_status) {
                                'paid' => 'success',
                                'failed' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <span class="badge bg-{{ $payBadge }}-subtle text-{{ $payBadge }} fs-12">{{ ucfirst($order->payment_status) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-12 text-muted">Payment Method</span>
                        @php
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
                        <span class="badge bg-{{ $payMethodBadge }}-subtle text-{{ $payMethodBadge }} fs-12 text-uppercase">
                            {{ str_replace('_', ' ', $order->payment_method) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="feather-user text-primary"></i>Customer
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="customer-avatar">
                            {{ strtoupper(substr($order->user->name ?? 'G', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold fs-13">{{ $order->user->name ?? 'Guest' }}</div>
                            <div class="text-muted fs-12">{{ $order->user->email ?? '—' }}</div>
                            <div class="text-muted fs-12">{{ $order->user->phone ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="feather-credit-card text-primary"></i>Transactions
                </div>
                <div class="card-body p-0">
                    @forelse($order->transactions as $tx)
                    <div class="p-3 border-bottom transaction-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-uppercase fs-12">{{ $tx->gateway }}</span>
                            <span class="badge bg-secondary-subtle text-secondary fs-11">{{ ucfirst($tx->status) }}</span>
                        </div>
                        <div class="fs-13 fw-semibold mt-1">{{ number_format($tx->amount, 2) }} {{ $tx->currency }}</div>
                        @if($tx->gateway_intent_id)
                            <div class="fs-11 text-muted mt-1">Intent: {{ $tx->gateway_intent_id }}</div>
                        @endif
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted fs-12">
                        <i class="feather-inbox fs-24 d-block mb-2 opacity-50"></i>
                        No transactions recorded.
                    </div>
                    @endforelse
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

/* Extra badge colour for methods without a stock Bootstrap "subtle" variant */
.bg-purple-subtle{ background:rgba(147,51,234,.12) !important; }
.text-purple{ color:#9333ea !important; }

.order-detail-page .card{ border-radius:14px; }
.order-detail-page .card-header{ background:#fff; border-bottom:1px solid #eef0f4; border-radius:14px 14px 0 0 !important; padding:14px 18px; }

.order-summary-card{ border-radius:14px; background:linear-gradient(180deg,#fbfbff 0%,#ffffff 100%); }

/* ── Status stepper ── */
.order-stepper{ display:flex; align-items:flex-start; }
.order-stepper.is-failed{ display:block; }
.stepper-step{ display:flex; flex-direction:column; align-items:center; gap:6px; min-width:70px; }
.stepper-dot{
    width:28px; height:28px; border-radius:50%; background:#eef0f4; color:#9aa0ac;
    display:flex; align-items:center; justify-content:center; font-size:13px;
    border:2px solid #eef0f4; transition:all .2s ease;
}
.stepper-step.is-done .stepper-dot{ background:#7b5cf0; border-color:#7b5cf0; color:#fff; }
.stepper-step.is-current .stepper-dot{ background:#fff; border-color:#7b5cf0; color:#7b5cf0; box-shadow:0 0 0 4px rgba(123,92,240,.12); }
.stepper-label{ font-size:11.5px; font-weight:600; color:#9aa0ac; text-transform:uppercase; letter-spacing:.02em; }
.stepper-step.is-done .stepper-label,
.stepper-step.is-current .stepper-label{ color:#374151; }
.stepper-line{ flex:1; height:2px; background:#eef0f4; margin-top:13px; }
.stepper-line.is-done{ background:#7b5cf0; }

.order-total-row td{ border-top:2px solid #eef0f4; }

.customer-avatar{
    width:46px; height:46px; border-radius:50%; background:#7b5cf0; color:#fff;
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; flex-shrink:0;
}

.transaction-item:last-child{ border-bottom:none !important; }

@media print{
    .page-header, .ajax-toast, #updateStatusBtn, #orderStatusSelect, .card-header i{ display:none !important; }
}
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

document.getElementById('updateStatusBtn').addEventListener('click', function () {
    const select = document.getElementById('orderStatusSelect');
    const id = select.dataset.id;
    const status = select.value;
    const btn = this;
    const sp = document.getElementById('statusSpinner');
    const ic = document.getElementById('statusBtnIcon');

    btn.disabled = true;
    sp.classList.remove('d-none');
    ic.classList.add('d-none');

    const url = ROUTES.updateStatus.replace(':id', id);

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status }),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        showToast(d.message, d.status === 'success' ? 'success' : 'error');
        if (d.status === 'success') {
            setTimeout(() => location.reload(), 900);
        }
    })
    .catch(() => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        showToast('Network error — try again.', 'error');
    });
});
</script>
@endpush