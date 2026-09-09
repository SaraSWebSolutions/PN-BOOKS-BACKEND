{{-- resources/views/contacts/supplier-ledger.blade.php --}}
@extends('layouts.app')
@section('title', 'Supplier Ledger — '.$contact->name)

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
            <h5 class="m-b-10">Supplier Ledger</h5>
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

    {{-- ── SUPPLIER INFO STRIP ── --}}
    <div class="card stretch stretch-full mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="d-flex align-items-center justify-content-center text-white fw-bold"
                     style="width:52px;height:52px;border-radius:50%;font-size:22px;flex-shrink:0;background:#7c3aed;">
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
                        @if($contact->gstin)
                        <span class="fs-12 text-muted"><i class="feather-credit-card me-1"></i>GSTIN: {{ $contact->gstin }}</span>
                        @endif
                        @if($contact->city)
                        <span class="fs-12 text-muted"><i class="feather-map-pin me-1"></i>{{ $contact->city }}</span>
                        @endif
                        @if($contact->code)
                        <span class="badge" style="background:rgba(124,58,237,.15);color:#7c3aed;">{{ $contact->code }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="card stretch stretch-full mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('contacts.supplier-ledger', $contact) }}"
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
                    <select name="status" class="form-select form-select-sm" style="min-width:140px;">
                        <option value="">All</option>
                        <option value="pending"          {{ $statusFilter === 'pending'          ? 'selected' : '' }}>Pending</option>
                        <option value="partially_split"  {{ $statusFilter === 'partially_split'  ? 'selected' : '' }}>Partially Split</option>
                        <option value="fully_split"      {{ $statusFilter === 'fully_split'      ? 'selected' : '' }}>Fully Split</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="feather-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('contacts.supplier-ledger', $contact) }}"
                       class="btn btn-light-brand btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── SUMMARY CARDS ── --}}
    <div class="row g-3 mb-3">

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #7c3aed;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Total Purchases</div>
                    <div class="fw-bold fs-4">{{ $summary['total_purchases'] }}</div>
                    <div class="fs-12 text-muted">{{ $fromDate->format('d M') }} – {{ $toDate->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #f59e0b;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Net Payable</div>
                    <div class="fw-bold fs-5">₹{{ number_format($summary['total_amount'], 2) }}</div>
                    <div class="fs-12 text-muted">Taxable: ₹{{ number_format($summary['total_taxable'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #10b981;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Total Pieces</div>
                    <div class="fw-bold fs-4">{{ $summary['total_pieces'] }}</div>
                    <div class="fs-12 text-muted">Tax: ₹{{ number_format($summary['total_tax'], 2) }}</div>
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
                    <div class="fs-12 text-muted">gross weight</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #94a3b8;background:#f8fafc;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Silver Purchased</div>
                    <div class="fw-bold">{{ number_format($summary['silver_gross_weight'], 3) }} g</div>
                    <div class="fs-12 text-muted">gross weight</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stretch stretch-full" style="border-left:3px solid #ef4444;background:#fff5f5;">
                <div class="card-body py-3">
                    <div class="text-muted fs-12 mb-1">Discount / TDS / TCS</div>
                    <div class="fw-bold fs-12">
                        Disc: ₹{{ number_format($summary['total_discount'], 2) }}<br>
                        TDS : ₹{{ number_format($summary['total_tds'],      2) }}<br>
                        TCS : ₹{{ number_format($summary['total_tcs'],      2) }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── PURCHASES TABLE ── --}}
    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h6 class="fw-semibold mb-0">
                <i class="feather-shopping-cart me-2"></i>Purchase Transactions
                <span class="badge bg-soft-secondary text-secondary ms-1">{{ $summary['total_purchases'] }}</span>
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
                            <th>Purchase No</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Metal / Purity</th>
                            <th class="text-center">Pcs</th>
                            <th class="text-center">
                                Gold (g)<br>
                                <span style="font-size:10px;font-weight:400;color:#888;">Gross</span>
                            </th>
                            <th class="text-center">
                                Silver (g)<br>
                                <span style="font-size:10px;font-weight:400;color:#888;">Gross</span>
                            </th>
                            <th class="text-end">Taxable</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Disc/TDS/TCS</th>
                            <th class="text-end">Net Payable</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sno = 1; @endphp
                        @forelse($purchases as $purchase)
                        @php
                            $goldGw = $silverGw = $pieces = 0;
                            $metalLines = [];
                            foreach ($purchase->items as $item) {
                                $metal = strtolower($item->metal_type ?? '');
                                $gw    = (float)($item->gross_weight ?? 0);
                                $pieces += (int)($item->pieces ?? 0);
                                $label  = ucfirst($metal) . ($item->purity ? ' ('.$item->purity.')' : '');
                                if (!in_array($label, $metalLines)) $metalLines[] = $label;
                                if ($metal === 'gold')   $goldGw   += $gw;
                                if ($metal === 'silver') $silverGw += $gw;
                            }

                            $hasDiscount = ($purchase->discount_amount ?? 0) > 0;
                            $hasTds      = ($purchase->tds_amount ?? 0) > 0;
                            $hasTcs      = ($purchase->tcs_amount ?? 0) > 0;
                            $netPayable  = $purchase->net_payable ?? $purchase->grand_total ?? 0;

                            $stMap = [
                                'pending'          => ['Pending',          'bg-soft-warning text-warning'],
                                'partially_split'  => ['Partially Split',  'bg-soft-info text-info'],
                                'fully_split'      => ['Fully Split',      'bg-soft-success text-success'],
                            ];
                            $st = $stMap[$purchase->status ?? 'pending'] ?? ['Pending', 'bg-soft-warning text-warning'];
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted fs-12">{{ $sno++ }}</td>

                            <td>
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="fw-semibold text-dark text-decoration-none">
                                    {{ $purchase->purchase_no }}
                                </a>
                            </td>

                            <td class="text-nowrap fs-12">{{ $purchase->purchase_date->format('d M Y') }}</td>

                            <td class="fs-12 text-muted">{{ $purchase->invoice_no ?? '—' }}</td>

                            <td style="max-width:150px;">
                                @foreach($purchase->items as $item)
                                <div class="fs-12" style="line-height:1.8;">
                                    @if(strtolower($item->metal_type) === 'gold')
                                        <span class="badge bg-soft-warning text-warning">
                                    @else
                                        <span class="badge bg-soft-secondary text-secondary">
                                    @endif
                                        {{ ucfirst($item->metal_type) }}
                                        @if($item->purity) · {{ $item->purity }} @endif
                                    </span>
                                    <span class="text-muted fs-12">{{ number_format($item->gross_weight,3) }}g</span>
                                </div>
                                @endforeach
                            </td>

                            <td class="text-center">{{ $pieces ?: '—' }}</td>

                            <td class="text-center">
                                @if($goldGw > 0)
                                    <span class="fw-bold" style="color:#d97706;">{{ number_format($goldGw,3) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($silverGw > 0)
                                    <span class="fw-bold">{{ number_format($silverGw,3) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end fs-12">₹{{ number_format($purchase->grand_taxable ?? 0, 2) }}</td>

                            <td class="text-end fs-12">₹{{ number_format($purchase->grand_tax ?? 0, 2) }}</td>

                            <td class="text-end" style="min-width:90px;">
                                @if($hasDiscount)
                                <div class="fs-12 text-success">Disc −₹{{ number_format($purchase->discount_amount,2) }}</div>
                                @endif
                                @if($hasTds)
                                <div class="fs-12 text-danger">TDS −₹{{ number_format($purchase->tds_amount,2) }}</div>
                                @endif
                                @if($hasTcs)
                                <div class="fs-12" style="color:#7c3aed;">TCS +₹{{ number_format($purchase->tcs_amount,2) }}</div>
                                @endif
                                @if(!$hasDiscount && !$hasTds && !$hasTcs)
                                <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end fw-bold" style="color:#7c3aed;">
                                ₹{{ number_format($netPayable, 2) }}
                            </td>

                            <td class="text-center">
                                <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>

                            <td class="text-end pe-3">
                                <div class="hstack gap-1 justify-content-end">
                                    <a href="{{ route('purchases.show', $purchase) }}"
                                       class="avatar-text avatar-sm" title="View">
                                        <i class="feather feather-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="feather-shopping-cart fs-30 d-block mb-2"></i>
                                No purchase transactions found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    {{-- ── FOOTER TOTALS ── --}}
                    @if($purchases->count() > 0)
                    <tfoot>
                        <tr style="background:#f5f5f5;font-weight:bold;border-top:2px solid #dee2e6;">
                            <td colspan="5" class="ps-3 py-2">TOTAL</td>
                            <td class="text-center py-2">{{ $summary['total_pieces'] }}</td>
                            <td class="text-center py-2" style="color:#d97706;">
                                {{ number_format($summary['gold_gross_weight'],3) }}
                            </td>
                            <td class="text-center py-2">
                                {{ number_format($summary['silver_gross_weight'],3) }}
                            </td>
                            <td class="text-end py-2">₹{{ number_format($summary['total_taxable'],2) }}</td>
                            <td class="text-end py-2">₹{{ number_format($summary['total_tax'],2) }}</td>
                            <td class="text-end py-2">
                                @if($summary['total_discount'] > 0)
                                <div class="fs-12 text-success">₹{{ number_format($summary['total_discount'],2) }}</div>
                                @endif
                                @if($summary['total_tds'] > 0)
                                <div class="fs-12 text-danger">₹{{ number_format($summary['total_tds'],2) }}</div>
                                @endif
                                @if($summary['total_tcs'] > 0)
                                <div class="fs-12" style="color:#7c3aed;">₹{{ number_format($summary['total_tcs'],2) }}</div>
                                @endif
                            </td>
                            <td class="text-end py-2 fw-bold" style="color:#7c3aed;">
                                ₹{{ number_format($summary['total_amount'],2) }}
                            </td>
                            <td colspan="2"></td>
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