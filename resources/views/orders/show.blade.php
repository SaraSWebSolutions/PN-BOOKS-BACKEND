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
                    <div class="fw-bold fs-16 text-primary" id="summaryOrderTotal">
                        {{ number_format($order->total_amount, 2) }} {{ $order->currency->code ?? '' }}
                    </div>
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

            {{-- ═══ Order Items (editable: add / qty / remove) ═══ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="feather-package text-primary me-2"></i>Order Items</span>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addItemOffcanvas" aria-controls="addItemOffcanvas">
                        <i class="feather-plus me-1"></i> Add Book
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Format</th>
                                    <th class="text-center" style="width:100px;">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end" style="width:60px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orderItemsBody">
                                @foreach($order->items as $item)
                                <tr id="item-row-{{ $item->id }}">
                                    <td class="fw-semibold fs-13">{{ $item->title }}</td>
                                    <td class="fs-12 text-muted">
                                        <span class="badge bg-light-primary text-primary">{{ $item->format_name }}</span>
                                    </td>
                                    <td class="text-center">
                                        <input type="number" min="1" max="999"
                                               class="form-control form-control-sm item-qty-input"
                                               data-item-id="{{ $item->id }}"
                                               value="{{ $item->quantity }}">
                                    </td>
                                    <td class="text-end fs-13">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-semibold fs-13" id="item-total-{{ $item->id }}">
                                        {{ number_format($item->total_price, 2) }}
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-icon text-danger remove-item-btn"
                                                data-item-id="{{ $item->id }}" title="Remove">
                                            <i class="feather-trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-semibold fs-13">Subtotal</td>
                                    <td class="text-end fs-13" id="order-subtotal">{{ number_format($order->subtotal, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fs-13 text-muted">Shipping</td>
                                    <td class="text-end fs-13" id="order-shipping-display">{{ number_format($order->shipping_amount ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fs-13 text-muted">Discount</td>
                                    <td class="text-end fs-13 text-danger" id="order-discount-display">-{{ number_format($order->discount_amount ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr class="order-total-row">
                                    <td colspan="4" class="text-end fw-bold fs-14">Total</td>
                                    <td class="text-end fw-bold fs-14 text-primary" id="order-total">
                                        {{ number_format($order->total_amount, 2) }} {{ $order->currency->code ?? '' }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══ Shipping / Discount / Notes (editable) ═══ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="feather-edit-3 text-primary"></i>Order Charges &amp; Notes
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fs-12 text-muted">Shipping Amount</label>
                            <input type="number" step="0.01" min="0" id="metaShippingInput" class="form-control"
                                   value="{{ $order->shipping_amount ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 text-muted">Discount Amount</label>
                            <input type="number" step="0.01" min="0" id="metaDiscountInput" class="form-control"
                                   value="{{ $order->discount_amount ?? 0 }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="saveMetaBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="metaSpinner"></span>
                                <i class="feather-save me-2" id="metaBtnIcon"></i>Save
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-12 text-muted">Admin Notes</label>
                            <textarea id="metaNotesInput" class="form-control" rows="2" maxlength="2000">{{ $order->notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══ Addresses (editable) ═══ --}}
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                            <span><i class="feather-map-pin text-primary me-2"></i>Shipping Address</span>
                            <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="offcanvas"
                                    data-bs-target="#addressOffcanvas" data-type="shipping" title="Edit">
                                <i class="feather-edit-2"></i>
                            </button>
                        </div>
                        <div class="card-body fs-13" id="shippingAddressBody">
                            @php $addr = $order->shippingAddress; @endphp
                            @if($addr)
                                <div class="fw-semibold mb-1">{{ $addr->full_name }}</div>
                                <div class="text-muted mb-2">{{ $addr->phone }}</div>
                                <div>{{ $addr->address_line1 }}</div>
                                @if($addr->address_line2)<div>{{ $addr->address_line2 }}</div>@endif
                                <div>{{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}</div>
                                <div class="fw-semibold mt-1">{{ $addr->country->name ?? '' }}</div>
                            @else
                                <span class="text-muted fst-italic">No shipping address recorded. Click edit to add one.</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                            <span><i class="feather-file-text text-primary me-2"></i>Billing Address</span>
                            <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="offcanvas"
                                    data-bs-target="#addressOffcanvas" data-type="billing" title="Edit">
                                <i class="feather-edit-2"></i>
                            </button>
                        </div>
                        <div class="card-body fs-13" id="billingAddressBody">
                            @php $baddr = $order->billingAddress; @endphp
                            @if($baddr)
                                <div class="fw-semibold mb-1">{{ $baddr->full_name }}</div>
                                <div class="text-muted mb-2">{{ $baddr->phone }}</div>
                                <div>{{ $baddr->address_line1 }}</div>
                                @if($baddr->address_line2)<div>{{ $baddr->address_line2 }}</div>@endif
                                <div>{{ $baddr->city }}, {{ $baddr->state }} {{ $baddr->postal_code }}</div>
                                <div class="fw-semibold mt-1">{{ $baddr->country->name ?? '' }}</div>
                            @else
                                <span class="text-muted fst-italic">Same as shipping. Click edit to set a different billing address.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT: Status, customer, payment, transactions --}}
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

                    {{-- NEW: Tracking number fields — shown only when status is / becomes "shipped" --}}
                    <div id="trackingFieldsBox" class="mb-3" style="{{ $order->status === 'shipped' ? '' : 'display:none;' }}">
                        <label class="form-label fs-12 text-muted">Tracking Number <span class="text-danger">*</span></label>
                        <input type="text" id="trackingNumberInput" class="form-control mb-2"
                               placeholder="e.g. SG1234567890"
                               value="{{ $order->tracking_number }}">

                        <label class="form-label fs-12 text-muted">Shipping Carrier (optional)</label>
                        <input type="text" id="shippingCarrierInput" class="form-control"
                               placeholder="e.g. SingPost, DHL"
                               value="{{ $order->shipping_carrier }}">
                    </div>

                    <button type="button" class="btn btn-primary w-100" id="updateStatusBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="statusSpinner"></span>
                        <i class="feather-save me-2" id="statusBtnIcon"></i>
                        Update Status
                    </button>

                    {{-- NEW: show existing tracking info if already shipped --}}
                    @if($order->tracking_number)
                    <div class="alert alert-light py-2 px-3 mt-3 mb-0 fs-12">
                        <div class="fw-semibold mb-1"><i class="feather-truck me-1"></i>Shipment Info</div>
                        <div>Tracking: <strong>{{ $order->tracking_number }}</strong></div>
                        @if($order->shipping_carrier)
                            <div>Carrier: <strong>{{ $order->shipping_carrier }}</strong></div>
                        @endif
                        @if($order->shipped_at)
                            <div class="text-muted">Shipped on {{ $order->shipped_at->format('d M Y, h:i A') }}</div>
                        @endif
                    </div>
                    @endif

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

{{-- ═══ Offcanvas: Add Book to Order (right side) ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="addItemOffcanvas" aria-labelledby="addItemOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title" id="addItemOffcanvasLabel">Add Book to Order</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="mb-3">
            <label class="form-label fs-12">Search Book</label>
            <input type="text" id="bookSearchInput" class="form-control" placeholder="Type a book title…" autocomplete="off">
            <div id="bookSearchResults" class="list-group mt-1" style="max-height:260px;overflow-y:auto;"></div>
        </div>
        <div class="mb-3" id="selectedBookBox" style="display:none;">
            <div class="alert alert-light py-2 px-3 d-flex justify-content-between align-items-center">
                <span id="selectedBookName" class="fw-semibold fs-13"></span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="clearSelectedBook">Change</button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fs-12">Format</label>
            <select id="formatSelect" class="form-select" disabled>
                <option value="">Select a book first</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label fs-12">
                Unit Price
                <span class="text-danger" id="noPriceWarning" style="display:none;">— no price found for this order's currency, please enter one</span>
            </label>
            <input type="number" id="unitPriceInput" class="form-control" step="0.01" min="0.01" placeholder="0.00">
        </div>
        <div class="mb-3">
            <label class="form-label fs-12">Quantity</label>
            <input type="number" id="itemQtyInput" class="form-control" value="1" min="1" max="999">
        </div>
        <div class="fs-12 text-muted mb-3 d-flex justify-content-between border-top pt-2">
            <span>Line Total</span>
            <span class="fw-semibold" id="lineTotalPreview">0.00</span>
        </div>

        <div class="mt-auto pt-3 border-top d-flex gap-2">
            <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="button" class="btn btn-primary flex-fill" id="confirmAddItemBtn" disabled>
                <span class="spinner-border spinner-border-sm me-2 d-none" id="addItemSpinner"></span>
                Add to Order
            </button>
        </div>
    </div>
</div>

{{-- ═══ Offcanvas: Edit Address (shared shipping / billing, right side) ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="addressOffcanvas" aria-labelledby="addressOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title" id="addressOffcanvasLabel">Edit Address</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <input type="hidden" id="addressType">
        <div class="row g-2 flex-grow-1">
            <div class="col-12"><label class="form-label fs-12">Full Name</label>
                <input class="form-control" id="addr_full_name"></div>
            <div class="col-12"><label class="form-label fs-12">Phone</label>
                <input class="form-control" id="addr_phone"></div>
            <div class="col-12"><label class="form-label fs-12">Address Line 1</label>
                <input class="form-control" id="addr_address_line1"></div>
            <div class="col-12"><label class="form-label fs-12">Address Line 2</label>
                <input class="form-control" id="addr_address_line2"></div>
            <div class="col-6"><label class="form-label fs-12">City</label>
                <input class="form-control" id="addr_city"></div>
            <div class="col-6"><label class="form-label fs-12">State</label>
                <input class="form-control" id="addr_state"></div>
            <div class="col-6"><label class="form-label fs-12">Postal Code</label>
                <input class="form-control" id="addr_postal_code"></div>
            <div class="col-6"><label class="form-label fs-12">Country ID</label>
                <input class="form-control" id="addr_country_id" type="number"></div>
        </div>

        <div class="mt-auto pt-3 border-top d-flex gap-2">
            <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="button" class="btn btn-primary flex-fill" id="saveAddressBtn">
                <span class="spinner-border spinner-border-sm me-2 d-none" id="addrSpinner"></span>
                Save
            </button>
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

.item-qty-input{ text-align:center; }

#addItemOffcanvas{ width:420px; }
#addressOffcanvas{ width:420px; }
@media (max-width:480px){ #addItemOffcanvas, #addressOffcanvas{ width:100%; } }

@media print{
    .page-header, .ajax-toast, #updateStatusBtn, #orderStatusSelect, .card-header i,
    .card-header button, .item-qty-input, .remove-item-btn, #saveMetaBtn, #trackingFieldsBox{ display:none !important; }
}
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const ORDER_ID = {{ $order->id }};
const CURRENCY_CODE = "{{ $order->currency->code ?? '' }}";

// Route names resolved server-side by Blade. Placeholders are swapped in
// on the client before each request — no hardcoded URLs anywhere in JS.
const ROUTES = {
    updateStatus:  "{{ route('orders.updateStatus', $order->id) }}",
    updateMeta:    "{{ route('orders.updateMeta', $order->id) }}",
    updateAddress: "{{ route('orders.updateAddress', [$order->id, ':type']) }}",
    addItem:       "{{ route('orders.items.add', $order->id) }}",
    updateItemQty: "{{ route('orders.items.update', [$order->id, ':item']) }}",
    removeItem:    "{{ route('orders.items.remove', [$order->id, ':item']) }}",
    searchBooks:   "{{ route('orders.books.search') }}",
    bookFormats:   "{{ route('orders.books.formats', ':book') }}",
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

function refreshTotals(order) {
    if (order.subtotal !== undefined) document.getElementById('order-subtotal').textContent = order.subtotal;
    if (order.shipping_amount !== undefined) document.getElementById('order-shipping-display').textContent = order.shipping_amount;
    if (order.discount_amount !== undefined) document.getElementById('order-discount-display').textContent = '-' + order.discount_amount;
    if (order.total_amount !== undefined) {
        document.getElementById('order-total').textContent = order.total_amount + ' ' + CURRENCY_CODE;
        document.getElementById('summaryOrderTotal').textContent = order.total_amount + ' ' + CURRENCY_CODE;
    }
}

/* ── NEW: show/hide tracking fields live as status dropdown changes ── */
document.getElementById('orderStatusSelect').addEventListener('change', function () {
    document.getElementById('trackingFieldsBox').style.display = this.value === 'shipped' ? '' : 'none';
});

/* ── Order status update ── */
document.getElementById('updateStatusBtn').addEventListener('click', function () {
    const select = document.getElementById('orderStatusSelect');
    const status = select.value;
    const btn = this, sp = document.getElementById('statusSpinner'), ic = document.getElementById('statusBtnIcon');

    // NEW: build payload with tracking fields when status is "shipped"
    const payload = { status };
    if (status === 'shipped') {
        payload.tracking_number  = document.getElementById('trackingNumberInput').value;
        payload.shipping_carrier = document.getElementById('shippingCarrierInput').value;
    }

    btn.disabled = true; sp.classList.remove('d-none'); ic.classList.add('d-none');

    fetch(ROUTES.updateStatus, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        showToast(d.message, d.status === 'success' ? 'success' : 'error');
        if (d.status === 'success') setTimeout(() => location.reload(), 700);
    })
    .catch(() => {
        btn.disabled = false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        showToast('Network error — try again.', 'error');
    });
});

/* ── Order meta (shipping / discount / notes) ── */
document.getElementById('saveMetaBtn').addEventListener('click', function () {
    const btn = this, sp = document.getElementById('metaSpinner'), ic = document.getElementById('metaBtnIcon');
    const payload = {
        shipping_amount: document.getElementById('metaShippingInput').value || 0,
        discount_amount: document.getElementById('metaDiscountInput').value || 0,
        notes: document.getElementById('metaNotesInput').value || null,
    };

    btn.disabled = true; sp.classList.remove('d-none'); ic.classList.add('d-none');

    fetch(ROUTES.updateMeta, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        showToast(d.message, d.status);
        if (d.status === 'success') refreshTotals(d.order);
    })
    .catch(() => {
        btn.disabled = false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        showToast('Network error — try again.', 'error');
    });
});

/* ── Address edit ── */
@php
    $addressFields = ['full_name','phone','address_line1','address_line2','city','state','postal_code','country_id'];
    $shippingAddrData = $order->shippingAddress ? $order->shippingAddress->only($addressFields) : [];
    $billingAddrData  = $order->billingAddress ? $order->billingAddress->only($addressFields) : [];
@endphp
const addressData = {
    shipping: @json($shippingAddrData),
    billing:  @json($billingAddrData),
};

document.getElementById('addressOffcanvas').addEventListener('show.bs.offcanvas', function (e) {
    const type = e.relatedTarget.dataset.type;
    document.getElementById('addressType').value = type;
    document.getElementById('addressOffcanvasLabel').textContent = 'Edit ' + (type === 'shipping' ? 'Shipping' : 'Billing') + ' Address';
    const d = addressData[type] || {};
    ['full_name','phone','address_line1','address_line2','city','state','postal_code','country_id'].forEach(f => {
        document.getElementById('addr_' + f).value = d[f] ?? '';
    });
});

document.getElementById('saveAddressBtn').addEventListener('click', function () {
    const type = document.getElementById('addressType').value;
    const btn = this, sp = document.getElementById('addrSpinner');
    const payload = {};
    ['full_name','phone','address_line1','address_line2','city','state','postal_code','country_id'].forEach(f => {
        payload[f] = document.getElementById('addr_' + f).value || null;
    });

    btn.disabled = true; sp.classList.remove('d-none');

    fetch(ROUTES.updateAddress.replace(':type', type), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; sp.classList.add('d-none');
        showToast(d.message || (d.status === 'success' ? 'Saved.' : 'Failed to save.'), d.status);
        if (d.status === 'success') setTimeout(() => location.reload(), 700);
    })
    .catch(() => { btn.disabled = false; sp.classList.add('d-none'); showToast('Network error — try again.', 'error'); });
});

/* ── Add item: book search ── */
let selectedBook = null;
let searchDebounce;

document.getElementById('addItemOffcanvas').addEventListener('hidden.bs.offcanvas', function () {
    selectedBook = null;
    document.getElementById('bookSearchInput').value = '';
    document.getElementById('bookSearchResults').innerHTML = '';
    document.getElementById('selectedBookBox').style.display = 'none';
    document.getElementById('formatSelect').innerHTML = '<option value="">Select a book first</option>';
    document.getElementById('formatSelect').disabled = true;
    document.getElementById('unitPriceInput').value = '';
    document.getElementById('noPriceWarning').style.display = 'none';
    document.getElementById('itemQtyInput').value = 1;
    document.getElementById('lineTotalPreview').textContent = '0.00';
    document.getElementById('confirmAddItemBtn').disabled = true;
});

document.getElementById('bookSearchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    const q = this.value.trim();
    const box = document.getElementById('bookSearchResults');
    if (q.length < 2) { box.innerHTML = ''; return; }

    searchDebounce = setTimeout(() => {
        fetch(`${ROUTES.searchBooks}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(books => {
                box.innerHTML = books.map(b =>
                    `<button type="button" class="list-group-item list-group-item-action fs-13 book-result" data-id="${b.id}" data-title="${b.title.replace(/"/g,'&quot;')}">${b.title}</button>`
                ).join('') || '<div class="fs-12 text-muted p-2">No books found.</div>';
            });
    }, 300);
});

document.getElementById('bookSearchResults').addEventListener('click', function (e) {
    const btn = e.target.closest('.book-result');
    if (!btn) return;
    selectedBook = { id: btn.dataset.id, title: btn.dataset.title };

    document.getElementById('bookSearchInput').value = '';
    document.getElementById('bookSearchResults').innerHTML = '';
    document.getElementById('selectedBookName').textContent = selectedBook.title;
    document.getElementById('selectedBookBox').style.display = '';

    const formatSelect = document.getElementById('formatSelect');
    formatSelect.disabled = true;
    formatSelect.innerHTML = '<option value="">Loading formats…</option>';
    document.getElementById('confirmAddItemBtn').disabled = true;

    fetch(`${ROUTES.bookFormats.replace(':book', selectedBook.id)}?order_id=${ORDER_ID}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(formats => {
            window._formatData = {};
            formats.forEach(f => window._formatData[f.id] = f);

            formatSelect.disabled = false;
            formatSelect.innerHTML = formats.length
                ? formats.map(f => `<option value="${f.id}">${f.name}${f.has_price ? ' — ' + f.price.toFixed(2) : ' — no price set'}</option>`).join('')
                : '<option value="">No formats available</option>';

            if (formats.length) applyFormatPrice(formats[0]);
        });
});

function applyFormatPrice(format) {
    const priceInput = document.getElementById('unitPriceInput');
    const warning = document.getElementById('noPriceWarning');

    priceInput.value = format.has_price ? format.price.toFixed(2) : '';
    warning.style.display = format.has_price ? 'none' : '';
    updateLineTotalPreview();
    validateAddItemForm();
}

document.getElementById('formatSelect').addEventListener('change', function () {
    const fmt = window._formatData && window._formatData[this.value];
    if (fmt) applyFormatPrice(fmt);
});

function updateLineTotalPreview() {
    const price = parseFloat(document.getElementById('unitPriceInput').value) || 0;
    const qty = parseInt(document.getElementById('itemQtyInput').value, 10) || 0;
    document.getElementById('lineTotalPreview').textContent = (price * qty).toFixed(2);
}

function validateAddItemForm() {
    const price = parseFloat(document.getElementById('unitPriceInput').value) || 0;
    const qty = parseInt(document.getElementById('itemQtyInput').value, 10) || 0;
    const formatChosen = document.getElementById('formatSelect').value !== '';
    document.getElementById('confirmAddItemBtn').disabled = !(selectedBook && formatChosen && price > 0 && qty >= 1);
}

document.getElementById('unitPriceInput').addEventListener('input', function () {
    updateLineTotalPreview();
    validateAddItemForm();
});
document.getElementById('itemQtyInput').addEventListener('input', function () {
    updateLineTotalPreview();
    validateAddItemForm();
});

document.getElementById('clearSelectedBook').addEventListener('click', function () {
    selectedBook = null;
    document.getElementById('selectedBookBox').style.display = 'none';
    document.getElementById('formatSelect').innerHTML = '<option value="">Select a book first</option>';
    document.getElementById('formatSelect').disabled = true;
    document.getElementById('unitPriceInput').value = '';
    document.getElementById('noPriceWarning').style.display = 'none';
    document.getElementById('lineTotalPreview').textContent = '0.00';
    document.getElementById('confirmAddItemBtn').disabled = true;
});

document.getElementById('confirmAddItemBtn').addEventListener('click', function () {
    if (!selectedBook) return;
    const formatId = document.getElementById('formatSelect').value;
    const unitPrice = parseFloat(document.getElementById('unitPriceInput').value);
    const qty = parseInt(document.getElementById('itemQtyInput').value, 10) || 1;

    if (!formatId) { showToast('Choose a format.', 'error'); return; }
    if (!unitPrice || unitPrice <= 0) { showToast('Enter a valid unit price.', 'error'); return; }

    const btn = this, sp = document.getElementById('addItemSpinner');
    btn.disabled = true; sp.classList.remove('d-none');

    fetch(ROUTES.addItem, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id: selectedBook.id, book_format_id: formatId, quantity: qty, unit_price: unitPrice }),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; sp.classList.add('d-none');
        showToast(d.message, d.status);
        if (d.status === 'success') setTimeout(() => location.reload(), 700);
    })
    .catch(() => { btn.disabled = false; sp.classList.add('d-none'); showToast('Network error — try again.', 'error'); });
});

/* ── Item quantity change ── */
document.getElementById('orderItemsBody').addEventListener('change', function (e) {
    const input = e.target.closest('.item-qty-input');
    if (!input) return;
    const itemId = input.dataset.itemId;
    const qty = parseInt(input.value, 10);
    if (!qty || qty < 1) { input.value = 1; return; }

    fetch(ROUTES.updateItemQty.replace(':item', itemId), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ quantity: qty }),
    })
    .then(r => r.json())
    .then(d => {
        showToast(d.message, d.status);
        if (d.status === 'success') {
            document.getElementById(`item-total-${itemId}`).textContent = d.item.total_price;
            refreshTotals(d.order);
        }
    })
    .catch(() => showToast('Network error — try again.', 'error'));
});

/* ── Remove item ── */
document.getElementById('orderItemsBody').addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-item-btn');
    if (!btn) return;
    if (!confirm('Remove this book from the order?')) return;

    const itemId = btn.dataset.itemId;

    fetch(ROUTES.removeItem.replace(':item', itemId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        showToast(d.message, d.status);
        if (d.status === 'success') {
            document.getElementById(`item-row-${itemId}`).remove();
            refreshTotals(d.order);
        }
    })
    .catch(() => showToast('Network error — try again.', 'error'));
});
</script>
@endpush