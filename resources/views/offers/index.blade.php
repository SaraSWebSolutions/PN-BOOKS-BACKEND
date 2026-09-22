{{-- resources/views/offers/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Promotions')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- ═══ Add / Edit Offcanvas ═══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offerCanvas" style="width:460px;">
    <div class="offcanvas-header border-bottom py-3">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0">
                <i id="canvasIcon" class="feather-plus-circle me-2 text-primary"></i>
                <span id="canvasTitle">Add Promotion</span>
            </h5>
            <small class="text-muted">Create a discount for a book, category, format, or the whole store.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="offerForm" novalidate>
            @csrf
            <input type="hidden" id="editOfferId">

            <div class="mb-3">
                <label class="form-label fw-semibold">Promotion Title <span class="text-danger">*</span></label>
                <input type="text" id="offer_title" class="form-control" placeholder="e.g. Hari Raya Sale">
                <div class="field-error" id="titleError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea id="offer_description" class="form-control" rows="2" placeholder="Optional note about this promotion"></textarea>
                <div class="field-error" id="descriptionError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Applies To <span class="text-danger">*</span></label>
                <select id="offer_target_type" class="form-control select2-field" data-placeholder="Select scope…">
                    <option value="all">All Active Books (site-wide)</option>
                    <option value="book">Specific Book</option>
                    <option value="category">Specific Category</option>
                    <option value="format">Specific Format (eBook / Physical / Audiobook)</option>
                </select>
                <div class="field-error" id="target_typeError"></div>
            </div>

            {{-- Specific Book picker (typeahead, shown only when target_type = book) --}}
            <div class="mb-3 target-field" id="bookTargetBox" style="display:none;">
                <label class="form-label fw-semibold">Search Book <span class="text-danger">*</span></label>
                <input type="text" id="bookSearchInput" class="form-control" placeholder="Type a book title…" autocomplete="off">
                <div id="bookSearchResults" class="list-group mt-1" style="max-height:200px;overflow-y:auto;"></div>
                <div class="alert alert-light py-2 px-3 mt-2 mb-0" id="selectedBookBox" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="selectedBookName" class="fw-semibold fs-13"></span>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" id="clearSelectedBook">Change</button>
                    </div>
                </div>
                <input type="hidden" id="offer_book_id">
                <div class="field-error" id="book_idError"></div>
            </div>

            {{-- Category picker (shown only when target_type = category) --}}
            <div class="mb-3 target-field" id="categoryTargetBox" style="display:none;">
                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select id="offer_category_id" class="form-control select2-field" data-placeholder="Select category…">
                    <option value=""></option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <div class="field-error" id="category_idError"></div>
            </div>

            {{-- Format picker (shown only when target_type = format) --}}
            <div class="mb-3 target-field" id="formatTargetBox" style="display:none;">
                <label class="form-label fw-semibold">Book Format <span class="text-danger">*</span></label>
                <select id="offer_book_format_id" class="form-control select2-field" data-placeholder="Select format…">
                    <option value=""></option>
                    @foreach($formats as $fmt)
                        <option value="{{ $fmt->id }}">{{ $fmt->name }}</option>
                    @endforeach
                </select>
                <div class="field-error" id="book_format_idError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Country</label>
                <select id="offer_country_id" class="form-control select2-field" data-placeholder="All Countries">
                    <option value="">All Countries (global promotion)</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Leave as "All Countries" if this promotion should apply everywhere, including Singapore.</small>
                <div class="field-error" id="country_idError"></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                    <select id="offer_discount_type" class="form-control select2-field" data-minimum-results-for-search="Infinity">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Value <span class="text-danger">*</span></label>
                    <input type="number" id="offer_discount_value" class="form-control" step="0.01" min="0.01" placeholder="e.g. 20">
                    <div class="field-error" id="discount_valueError"></div>
                </div>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold">Starts On</label>
                    <input type="date" id="offer_starts_at" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Ends On</label>
                    <input type="date" id="offer_ends_at" class="form-control">
                    <div class="field-error" id="ends_atError"></div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select id="offer_status" class="form-control select2-field" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="offerSpinner"></span>
                    <i class="feather-save me-2" id="offerBtnIcon"></i>
                    <span id="offerBtnText">Save</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Promotions</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Bookstore</li>
            <li class="breadcrumb-item">Promotions</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <button class="btn btn-primary" onclick="openAdd()"><i class="feather-plus me-2"></i>Add Promotion</button>
    </div>
</div>

<div class="main-content">
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total Promotions','value'=>$stats['total'],'icon'=>'feather-tag','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
            ['label'=>'Active','value'=>$stats['active'],'icon'=>'feather-check-circle','bg'=>'rgba(16,185,129,.1)','color'=>'#10b981','fw'=>'text-success'],
            ['label'=>'Inactive','value'=>$stats['inactive'],'icon'=>'feather-pause-circle','bg'=>'rgba(234,179,8,.1)','color'=>'#eab308','fw'=>'text-warning'],
        ] as $s)
        <div class="col-6 col-lg-4">
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
            <span class="fw-semibold">All Promotions</span>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted fs-12">Show</span>
                    <select id="perPageSelect" class="form-select form-select-sm entries-select">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                    </select>
                    <span class="text-muted fs-12">entries</span>
                </div>

                {{-- ✅ NEW: Status filter --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted fs-12">Status</span>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                        <option value="all">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                {{-- ✅ NEW: Date filter — shows promotions running on the picked date --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted fs-12">On date</span>
                    <input type="date" id="dateFilter" class="form-control form-control-sm" style="width:auto;">
                    <button type="button" class="btn btn-sm btn-light-brand" id="clearDateFilter" title="Clear date filter">
                        <i class="feather-x"></i>
                    </button>
                </div>

                <div class="input-group input-group-sm" style="max-width:220px;">
                    <span class="input-group-text"><i class="feather-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search…">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th><th>Title</th><th>Applies To</th><th>Country</th>
                            <th>Discount</th><th>Period</th>
                            <th class="text-center">Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="offerTableBody">
                        @forelse($offers as $offer)
                        <tr class="offer-row" id="offer-row-{{ $offer->id }}"
                            data-id="{{ $offer->id }}"
                            data-title="{{ $offer->title }}"
                            data-description="{{ $offer->description }}"
                            data-target_type="{{ $offer->target_type }}"
                            data-book_id="{{ $offer->book_id }}"
                            data-book_title="{{ $offer->book->title ?? '' }}"
                            data-category_id="{{ $offer->category_id }}"
                            data-book_format_id="{{ $offer->book_format_id }}"
                            data-country_id="{{ $offer->country_id }}"
                            data-discount_type="{{ $offer->discount_type }}"
                            data-discount_value="{{ $offer->discount_value }}"
                            data-starts_at="{{ optional($offer->starts_at)->format('Y-m-d') }}"
                            data-ends_at="{{ optional($offer->ends_at)->format('Y-m-d') }}"
                            data-is_active="{{ $offer->status ? '1' : '0' }}">
                            <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                            <td class="fw-semibold fs-13 text-dark">{{ $offer->title }}</td>
                            <td>
                                <span class="badge bg-light-primary text-primary text-capitalize">{{ str_replace('_',' ', $offer->target_type) }}</span>
                                <div class="fs-11 text-muted mt-1">{{ $offer->target_label }}</div>
                            </td>
                            <td class="fs-13">{{ $offer->country->name ?? 'All Countries' }}</td>
                            <td class="fs-13 fw-semibold">
                                {{ $offer->discount_type === 'percentage' ? $offer->discount_value . '%' : number_format($offer->discount_value, 2) }}
                            </td>
                            <td class="fs-12 text-muted">
                                @if($offer->starts_at || $offer->ends_at)
                                    {{ optional($offer->starts_at)->format('d M Y') ?? '—' }} → {{ optional($offer->ends_at)->format('d M Y') ?? '—' }}
                                @else
                                    <span class="fst-italic">No end date</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox"
                                           {{ $offer->status ? 'checked' : '' }} data-id="{{ $offer->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="javascript:void(0);" class="avatar-text avatar-md" title="Edit"
                                       onclick="openEdit(this)" data-row="offer-row-{{ $offer->id }}">
                                        <i class="feather feather-edit-3"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="avatar-text avatar-md text-danger" title="Delete"
                                       onclick="deleteRow({{ $offer->id }}, '{{ addslashes($offer->title) }}')">
                                        <i class="feather feather-trash-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="feather-tag fs-30 d-block mb-2 opacity-50"></i>
                                No promotions found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
            <span class="text-muted fs-12" id="entriesInfo">Showing 0 entries</span>
            <div class="custom-pagination" id="paginationContainer"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />

<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}
.field-error{font-size:12px;color:#ef4444;margin-top:4px;display:none;}
.field-error.show{display:block;}
.form-control.input-error{border-color:#ef4444!important;}

:root{ --select2-accent:#7b5cf0; --select2-accent-soft:#f2eeff; }
.select2-container{ width:100% !important; }
.select2-container--default .select2-selection--single{
    height:44px; border:1px solid #e2e5ec; border-radius:10px;
    display:flex; align-items:center; padding:0 14px; background:#fff; box-shadow:none;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--select2-accent); box-shadow:0 0 0 3px rgba(123,92,240,.12);
}
.select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:normal; padding:0; color:#1f2937; font-size:14px; }
.select2-container--default .select2-selection--single .select2-selection__arrow{ height:44px; right:10px; }
.select2-dropdown{ border:1px solid #e2e5ec; border-radius:12px; box-shadow:0 12px 32px rgba(17,24,39,.12); overflow:hidden; padding:6px; }
.select2-container--default .select2-results__option{ border-radius:8px; padding:9px 12px; font-size:14px; color:#374151; }
.select2-container--default .select2-results__option--highlighted[aria-selected]{ background:var(--select2-accent-soft); color:var(--select2-accent); }
.select2-container--default .select2-results__option[aria-selected=true]{ background:var(--select2-accent); color:#fff; font-weight:600; }

.entries-select{ width:auto; border-radius:8px; border:1px solid #e2e5ec; font-size:13px; padding:4px 28px 4px 10px; }
.custom-pagination ul{ list-style:none; display:flex; gap:4px; margin:0; padding:0; }
.custom-pagination .page-item .page-link{
    border:1px solid #e2e5ec; border-radius:8px; color:#4f5b76; font-size:13px; font-weight:500;
    min-width:32px; height:32px; display:flex; align-items:center; justify-content:center;
    padding:0 8px; margin:0; cursor:pointer; background:#fff; text-decoration:none; user-select:none;
}
.custom-pagination .page-item .page-link:hover:not(.disabled):not(.active){ background:#f2eeff; color:#7b5cf0; border-color:#e2e5ec; }
.custom-pagination .page-item.active .page-link{ background:#7b5cf0; border-color:#7b5cf0; color:#fff; cursor:default; }
.custom-pagination .page-item.disabled .page-link{ color:#c3c9d4; background:#fff; cursor:default; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let editMode = false;
let selectedBook = null;

const ROUTES = {
    store:       "{{ route('offers.store') }}",
    update:      "{{ route('offers.update', ':id') }}",
    toggle:      "{{ route('offers.toggle', ':id') }}",
    destroy:     "{{ route('offers.destroy', ':id') }}",
    searchBooks: "{{ route('offers.books.search') }}",
};

$(function(){
    $('.select2-field').select2({ dropdownParent: $('#offerCanvas'), width: '100%' });
});

/* ── Toast helpers ── */
let _tt;
function showToast(msg, type='success'){
    const t=document.getElementById('ajaxToast');
    document.getElementById('toastMsg').textContent=msg;
    document.getElementById('toastIcon').textContent= type==='success'?'✅':'❌';
    t.className='ajax-toast '+(type==='success'?'toast-success':'toast-error');
    t.classList.add('show'); clearTimeout(_tt); _tt=setTimeout(()=>t.classList.remove('show'),4000);
}
function hideToast(){ document.getElementById('ajaxToast').classList.remove('show'); }
function clearErrors(){
    document.querySelectorAll('.field-error').forEach(e=>{e.textContent='';e.classList.remove('show');});
    document.querySelectorAll('.form-control').forEach(e=>e.classList.remove('input-error'));
}
function fieldErr(fId,eId,msg){
    document.getElementById(fId)?.classList.add('input-error');
    const e=document.getElementById(eId); if(e){e.textContent=msg;e.classList.add('show');}
}

/* ── Target type toggling (show/hide book / category / format fields) ── */
function toggleTargetFields(type){
    document.getElementById('bookTargetBox').style.display     = type === 'book' ? '' : 'none';
    document.getElementById('categoryTargetBox').style.display = type === 'category' ? '' : 'none';
    document.getElementById('formatTargetBox').style.display   = type === 'format' ? '' : 'none';
}
$('#offer_target_type').on('change', function(){ toggleTargetFields(this.value); });

/* ── Book typeahead ── */
let searchDebounce;
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
    document.getElementById('offer_book_id').value = selectedBook.id;
    document.getElementById('bookSearchInput').value = '';
    document.getElementById('bookSearchResults').innerHTML = '';
    document.getElementById('selectedBookName').textContent = selectedBook.title;
    document.getElementById('selectedBookBox').style.display = '';
});
document.getElementById('clearSelectedBook').addEventListener('click', function () {
    selectedBook = null;
    document.getElementById('offer_book_id').value = '';
    document.getElementById('selectedBookBox').style.display = 'none';
});

/* ── List: search + status filter + date filter + pagination ── */
(function () {
    const rowSelector   = '.offer-row';
    const perPageSelect = document.getElementById('perPageSelect');
    const searchInput   = document.getElementById('searchInput');
    const statusFilter  = document.getElementById('statusFilter');
    const dateFilter     = document.getElementById('dateFilter');
    const clearDateBtn   = document.getElementById('clearDateFilter');
    const entriesInfo   = document.getElementById('entriesInfo');
    const paginationBox = document.getElementById('paginationContainer');

    let currentPage = 1;
    let perPage = 10;

    function matchesSearch(row, q) {
        return !q || row.textContent.toLowerCase().includes(q);
    }

    function matchesStatus(row, status) {
        if (status === 'all') return true;
        return row.dataset.is_active === status;
    }

    // Shows only promotions that are actually running on the picked date
    // (starts_at empty or <= date) AND (ends_at empty or >= date) — plain
    // string comparison works fine since both sides are 'YYYY-MM-DD'.
    function matchesDate(row, date) {
        if (!date) return true;
        const starts = row.dataset.starts_at || '';
        const ends   = row.dataset.ends_at || '';
        const afterStart = !starts || starts <= date;
        const beforeEnd  = !ends || ends >= date;
        return afterStart && beforeEnd;
    }

    function render() {
        const q      = (searchInput.value || '').toLowerCase().trim();
        const status = statusFilter.value;
        const date   = dateFilter.value;

        const allRows = Array.from(document.querySelectorAll(rowSelector));
        const visibleRows = allRows.filter(r =>
            matchesSearch(r, q) && matchesStatus(r, status) && matchesDate(r, date)
        );
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

        entriesInfo.textContent = total === 0 ? 'No entries found' : `Showing ${start + 1}–${Math.min(end, total)} of ${total} entries`;
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
            a.className = 'page-link'; a.textContent = label;
            if (!disabled && !active) a.addEventListener('click', () => { currentPage = page; render(); });
            li.appendChild(a); ul.appendChild(li);
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
        currentPage = 1; render();
    });
    searchInput.addEventListener('input', function () { currentPage = 1; render(); });
    statusFilter.addEventListener('change', function () { currentPage = 1; render(); });
    dateFilter.addEventListener('change', function () { currentPage = 1; render(); });
    clearDateBtn.addEventListener('click', function () { dateFilter.value = ''; currentPage = 1; render(); });

    window.__offerPaginationRender = render;
    render();
})();

/* ── Offcanvas: add / edit ── */
function resetForm(){
    document.getElementById('offerForm').reset();
    document.getElementById('editOfferId').value = '';
    document.getElementById('offer_book_id').value = '';
    selectedBook = null;
    document.getElementById('selectedBookBox').style.display = 'none';
    document.getElementById('bookSearchInput').value = '';
    document.getElementById('bookSearchResults').innerHTML = '';
    $('#offer_target_type').val('all').trigger('change');
    $('#offer_category_id').val('').trigger('change');
    $('#offer_book_format_id').val('').trigger('change');
    $('#offer_country_id').val('').trigger('change');
    $('#offer_discount_type').val('percentage').trigger('change');
    $('#offer_status').val('1').trigger('change');
    toggleTargetFields('all');
    clearErrors();
}

function openAdd(){
    editMode = false; resetForm();
    document.getElementById('canvasIcon').className = 'feather-plus-circle me-2 text-primary';
    document.getElementById('canvasTitle').textContent = 'Add Promotion';
    document.getElementById('offerBtnText').textContent = 'Save';
    new bootstrap.Offcanvas(document.getElementById('offerCanvas')).show();
}

function openEdit(btn){
    editMode = true; resetForm();
    const d = document.getElementById(btn.dataset.row).dataset;

    document.getElementById('canvasIcon').className = 'feather-edit-3 me-2 text-warning';
    document.getElementById('canvasTitle').textContent = 'Edit Promotion';
    document.getElementById('offerBtnText').textContent = 'Update';

    document.getElementById('editOfferId').value = d.id;
    document.getElementById('offer_title').value = d.title;
    document.getElementById('offer_description').value = d.description || '';

    $('#offer_target_type').val(d.target_type).trigger('change');
    toggleTargetFields(d.target_type);

    if (d.target_type === 'book' && d.book_id) {
        selectedBook = { id: d.book_id, title: d.book_title };
        document.getElementById('offer_book_id').value = d.book_id;
        document.getElementById('selectedBookName').textContent = d.book_title;
        document.getElementById('selectedBookBox').style.display = '';
    }
    if (d.target_type === 'category') $('#offer_category_id').val(d.category_id).trigger('change');
    if (d.target_type === 'format') $('#offer_book_format_id').val(d.book_format_id).trigger('change');

    $('#offer_country_id').val(d.country_id || '').trigger('change');
    $('#offer_discount_type').val(d.discount_type).trigger('change');
    document.getElementById('offer_discount_value').value = d.discount_value;
    document.getElementById('offer_starts_at').value = d.starts_at || '';
    document.getElementById('offer_ends_at').value = d.ends_at || '';
    $('#offer_status').val(d.is_active).trigger('change');

    new bootstrap.Offcanvas(document.getElementById('offerCanvas')).show();
}

document.getElementById('offerForm').addEventListener('submit', function(e){
    e.preventDefault(); clearErrors();
    const id = document.getElementById('editOfferId').value;
    const btn = document.getElementById('submitBtn'), sp = document.getElementById('offerSpinner'),
          ic = document.getElementById('offerBtnIcon'), tx = document.getElementById('offerBtnText');
    btn.disabled = true; sp.classList.remove('d-none'); ic.classList.add('d-none'); tx.textContent = 'Saving…';

    const payload = {
        title:           document.getElementById('offer_title').value,
        description:     document.getElementById('offer_description').value,
        target_type:     $('#offer_target_type').val(),
        book_id:         document.getElementById('offer_book_id').value || null,
        category_id:     $('#offer_category_id').val() || null,
        book_format_id:  $('#offer_book_format_id').val() || null,
        country_id:      $('#offer_country_id').val() || null,
        discount_type:   $('#offer_discount_type').val(),
        discount_value:  document.getElementById('offer_discount_value').value,
        starts_at:       document.getElementById('offer_starts_at').value || null,
        ends_at:         document.getElementById('offer_ends_at').value || null,
        status:          $('#offer_status').val(),
        _token: CSRF,
    };

    const url = editMode ? ROUTES.update.replace(':id', id) : ROUTES.store;
    const method = editMode ? 'PUT' : 'POST';

    fetch(url, {
        method,
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify(payload),
    })
    .then(r=>r.json().then(d=>({ok:r.ok,d})))
    .then(({d})=>{
        btn.disabled=false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';
        if(d.status==='success'){
            showToast(d.message,'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('offerCanvas'))?.hide();
            setTimeout(()=>location.reload(),1200);
        } else if(d.errors){
            Object.keys(d.errors).forEach(f=>fieldErr('offer_'+f, f+'Error', d.errors[f][0]));
            showToast('Please fix the errors below.','error');
        } else {
            showToast(d.message||'Something went wrong!','error');
        }
    })
    .catch(()=>{
        btn.disabled=false; sp.classList.add('d-none'); ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';
        showToast('Network error — try again.','error');
    });
});

/* ── Status toggle ── */
document.querySelectorAll('.status-toggle').forEach(toggle=>{
    toggle.addEventListener('change', function(){
        const id=this.dataset.id;
        fetch(ROUTES.toggle.replace(':id', id), {method:'PATCH', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json())
        .then(d=>{
            showToast(d.message, d.status==='success'?'success':'error');
            if (d.status === 'success') {
                const row = document.getElementById(`offer-row-${id}`);
                if (row) row.dataset.is_active = d.is_active ? '1' : '0';
                window.__offerPaginationRender && window.__offerPaginationRender();
            }
        })
        .catch(()=>{ this.checked=!this.checked; showToast('Something went wrong!','error'); });
    });
});

/* ── Delete ── */
function deleteRow(id,title){
    if(!confirm(`Delete "${title}"? This cannot be undone.`)) return;
    fetch(ROUTES.destroy.replace(':id', id), {method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='success'){
            document.getElementById(`offer-row-${id}`)?.remove();
            showToast(d.message,'success');
            window.__offerPaginationRender && window.__offerPaginationRender();
        } else { showToast(d.message,'error'); }
    })
    .catch(()=>showToast('Something went wrong!','error'));
}
</script>
@endpush