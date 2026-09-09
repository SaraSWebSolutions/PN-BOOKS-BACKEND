{{-- resources/views/contacts/customer-ledger.blade.php --}}
@extends('layouts.app')
@section('title', 'Ledger — '.$contact->name)

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- ── PAGE HEADER ── --}}
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Customer Ledger</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Contacts</a></li>
            <li class="breadcrumb-item">{{ $contact->name }}</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
               class="btn btn-light-brand">
                <i class="feather-download me-2"></i> Export CSV
            </a>
            @can('edit contacts')
            <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-light-brand">
                <i class="feather-edit-3 me-2"></i> Edit
            </a>
            @endcan
            <a href="{{ route('contacts.index') }}" class="btn btn-light-brand">
                <i class="feather-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="main-content">

    {{-- ── CUSTOMER INFO STRIP ── --}}
    <div class="card stretch stretch-full mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="d-flex align-items-center justify-content-center text-white fw-bold"
                     style="width:52px;height:52px;border-radius:50%;font-size:22px;flex-shrink:0;background:#3b82f6;">
                    {{ strtoupper(substr($contact->name, 0, 1)) }}
                </div>
                <div>
                    <div class="fw-bold fs-5">{{ $contact->name }}</div>
                    <div class="d-flex gap-3 flex-wrap mt-1">
                        @if($contact->phone)
                        <span class="fs-12 text-muted"><i class="feather-phone me-1"></i>{{ $contact->phone }}</span>
                        @endif
                        @if($contact->email)
                        <span class="fs-12 text-muted"><i class="feather-mail me-1"></i>{{ $contact->email }}</span>
                        @endif
                        @if($contact->city)
                        <span class="fs-12 text-muted"><i class="feather-map-pin me-1"></i>{{ $contact->city }}</span>
                        @endif
                        @if($contact->code)
                        <span class="badge bg-soft-info text-info">{{ $contact->code }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="card stretch stretch-full mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('contacts.customer-ledger', $contact) }}"
                  class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label fs-12 fw-bold mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm"
                           value="{{ $fromDate->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label fs-12 fw-bold mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm"
                           value="{{ $toDate->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label fs-12 fw-bold mb-1">Metal</label>
                    <select name="metal" class="form-select form-select-sm" style="min-width:100px;">
                        <option value="">All</option>
                        <option value="gold"   {{ $metalFilter  === 'gold'   ? 'selected' : '' }}>Gold</option>
                        <option value="silver" {{ $metalFilter  === 'silver' ? 'selected' : '' }}>Silver</option>
                    </select>
                </div>
                <div>
                    <label class="form-label fs-12 fw-bold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" style="min-width:120px;">
                        <option value="">All</option>
                        <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="credit"    {{ $statusFilter === 'credit'    ? 'selected' : '' }}>Credit</option>
                        <option value="partial"   {{ $statusFilter === 'partial'   ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="feather-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('contacts.customer-ledger', $contact) }}"
                       class="btn btn-light-brand btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── SUMMARY CARDS ── --}}
    <div class="row g-3 mb-3">

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #6366f1;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Total Bills</div>
                    <div class="fw-bold fs-4">{{ $summary['total_bills'] }}</div>
                    <div class="fs-12 text-muted">{{ $fromDate->format('d M') }} – {{ $toDate->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #f59e0b;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Total Purchase</div>
                    <div class="fw-bold fs-5">₹{{ number_format($summary['total_amount'], 2) }}</div>
                    <div class="fs-12 text-muted">Paid: ₹{{ number_format($summary['total_paid'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #10b981;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Total Pieces</div>
                    <div class="fw-bold fs-4">{{ $summary['total_pieces'] }}</div>
                    <div class="fs-12 text-muted">items purchased</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #eab308;background:#fffdf0;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">
                        <i class="feather-star me-1 text-warning"></i>Gold Purchased
                    </div>
                    <div class="fw-bold">{{ number_format($summary['gold_gross_weight'], 3) }} g</div>
                    <div class="fs-12 text-muted">Net: {{ number_format($summary['gold_net_weight'], 3) }} g</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #94a3b8;background:#f8fafc;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Silver Purchased</div>
                    <div class="fw-bold">{{ number_format($summary['silver_gross_weight'], 3) }} g</div>
                    <div class="fs-12 text-muted">Net: {{ number_format($summary['silver_net_weight'], 3) }} g</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #8b5cf6;background:#faf5ff;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">OJ / Advance</div>
                    <div class="fw-bold fs-12">
                        OJ &nbsp;: ₹{{ number_format($summary['total_oj'],           2) }}<br>
                        Adv: ₹{{ number_format($summary['total_advance_used'], 2) }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── TRANSACTIONS TABLE ── --}}
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h6 class="fw-semibold mb-0">
                <i class="feather-list me-2"></i>Transactions
                <span class="badge bg-soft-secondary text-secondary ms-1">{{ $summary['total_bills'] }}</span>
            </h6>
            <span class="text-muted fs-12">
                {{ $fromDate->format('d M Y') }} — {{ $toDate->format('d M Y') }}
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th class="text-center">Pcs</th>
                            <th class="text-center">
                                Gold (g)<br>
                                <span style="font-size:10px;font-weight:400;color:#888;">Gross / Net</span>
                            </th>
                            <th class="text-center">
                                Silver (g)<br>
                                <span style="font-size:10px;font-weight:400;color:#888;">Gross / Net</span>
                            </th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">OJ / Adv</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Payment</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sno = 1; @endphp
                        @forelse($sales as $sale)
                        @php
                            $goldGw = $goldNw = $silverGw = $silverNw = $pieces = 0;
                            $itemNames = [];
                            foreach ($sale->items as $item) {
                                $metal = strtolower($item->metal_type ?? '');
                                $gw    = (float)($item->gross_weight ?? 0);
                                $nw    = (float)($item->net_weight ?: $item->gross_weight ?? 0);
                                $pieces += (int)($item->quantity ?? 1);
                                $itemNames[] = $item->product_name;
                                if ($metal === 'gold')   { $goldGw += $gw; $goldNw += $nw; }
                                if ($metal === 'silver') { $silverGw += $gw; $silverNw += $nw; }
                            }
                            $hasOj  = ($sale->old_jewellery_exchange ?? 0) > 0;
                            $hasAdv = ($sale->advance_applied ?? 0) > 0;
                            $tax    = round(($sale->cgst_amount ?? 0) + ($sale->sgst_amount ?? 0), 2);

                            $ptMap = [
                                'cash'     => ['Cash',   'bg-soft-success text-success'],
                                'card'     => ['Card',   'bg-soft-info text-info'],
                                'gpay'     => ['GPay',   'bg-soft-primary text-primary'],
                                'multiple' => ['Multi',  'bg-soft-warning text-warning'],
                                'credit'   => ['Credit', 'bg-soft-danger text-danger'],
                            ];
                            $pt = $ptMap[strtolower($sale->payment_type ?? 'cash')] ?? ['Cash', 'bg-soft-success text-success'];

                            $stMap = [
                                'completed' => ['Paid',    'bg-soft-success text-success'],
                                'credit'    => ['Credit',  'bg-soft-danger text-danger'],
                                'partial'   => ['Partial', 'bg-soft-warning text-warning'],
                            ];
                            $st = $stMap[strtolower($sale->status ?? 'completed')] ?? [ucfirst($sale->status ?? ''), 'bg-soft-secondary text-secondary'];
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted fs-12">{{ $sno++ }}</td>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="fw-semibold text-dark text-decoration-none">
                                    {{ $sale->invoice_no }}
                                </a>
                            </td>
                            <td class="text-nowrap fs-12">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td style="max-width:160px;">
                                <div class="fs-12" style="line-height:1.6;">
                                    {{ implode(', ', array_slice($itemNames, 0, 2)) }}
                                    @if(count($itemNames) > 2)
                                    <span class="badge bg-soft-secondary text-secondary">+{{ count($itemNames) - 2 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $pieces ?: '—' }}</td>
                            <td class="text-center">
                                @if($goldGw > 0)
                                    <span class="fw-bold" style="color:#d97706;">{{ number_format($goldGw,3) }}</span>
                                    <span class="text-muted fs-12"> / {{ number_format($goldNw,3) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($silverGw > 0)
                                    <span class="fw-bold">{{ number_format($silverGw,3) }}</span>
                                    <span class="text-muted fs-12"> / {{ number_format($silverNw,3) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end fs-12">₹{{ number_format($sale->subtotal, 2) }}</td>
                            <td class="text-end fs-12">
                                @if($tax > 0) ₹{{ number_format($tax, 2) }}
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end" style="min-width:90px;">
                                @if($hasOj)
                                <div class="fs-12 text-success">OJ −₹{{ number_format($sale->old_jewellery_exchange,2) }}</div>
                                @endif
                                @if($hasAdv)
                                <div class="fs-12" style="color:#7c3aed;">Adv −₹{{ number_format($sale->advance_applied,2) }}</div>
                                @endif
                                @if(!$hasOj && !$hasAdv)<span class="text-muted">—</span>@endif
                            </td>
                            <td class="text-end fw-bold" style="color:#d97706;">
                                ₹{{ number_format($sale->total_amount, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $pt[1] }}">{{ $pt[0] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="hstack gap-1 justify-content-end">
                                    <a href="{{ route('sales.show', $sale) }}"
                                       class="avatar-text avatar-sm" title="View">
                                        <i class="feather feather-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.invoice', $sale) }}"
                                       class="avatar-text avatar-sm" title="Invoice" target="_blank">
                                        <i class="feather feather-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="feather-file-text fs-30 d-block mb-2"></i>
                                No transactions found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    @if($sales->count() > 0)
                    <tfoot>
                        <tr style="background:#f5f5f5;font-weight:bold;border-top:2px solid #dee2e6;">
                            <td colspan="4" class="ps-3 py-2">TOTAL</td>
                            <td class="text-center py-2">{{ $summary['total_pieces'] }}</td>
                            <td class="text-center py-2" style="color:#d97706;">
                                {{ number_format($summary['gold_gross_weight'],3) }}
                                <span class="text-muted fw-normal fs-12"> / {{ number_format($summary['gold_net_weight'],3) }}</span>
                            </td>
                            <td class="text-center py-2">
                                {{ number_format($summary['silver_gross_weight'],3) }}
                                <span class="text-muted fw-normal fs-12"> / {{ number_format($summary['silver_net_weight'],3) }}</span>
                            </td>
                            <td class="text-end py-2">₹{{ number_format($sales->sum('subtotal'),2) }}</td>
                            <td class="text-end py-2">
                                ₹{{ number_format($sales->sum(fn($s)=>($s->cgst_amount??0)+($s->sgst_amount??0)),2) }}
                            </td>
                            <td class="text-end py-2">
                                <div class="fs-12 text-success">₹{{ number_format($summary['total_oj'],2) }}</div>
                                <div class="fs-12" style="color:#7c3aed;">₹{{ number_format($summary['total_advance_used'],2) }}</div>
                            </td>
                            <td class="text-end py-2 fw-bold" style="color:#d97706;">
                                ₹{{ number_format($summary['total_amount'],2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif

                </table>
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
    font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);
    transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);
}
.ajax-toast.show { transform:translateX(0); }
.ajax-toast.toast-success { background:#d1fae5;border-left:4px solid #10b981;color:#065f46; }
.ajax-toast.toast-error   { background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b; }
.ajax-toast .toast-close  { margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6; }
</style>
@endpush

@push('scripts')
<script>
let toastTimer = null;
function showToast(message, type = 'success') {
    const toast  = document.getElementById('ajaxToast');
    const msgEl  = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    msgEl.textContent  = message;
    toast.className    = 'ajax-toast';
    toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
    iconEl.textContent = type === 'success' ? '✅' : '❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
}
function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }
</script>
@endpush