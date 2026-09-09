{{-- resources/views/taxes/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Taxes')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="taxCanvas" style="width:420px;">
    <div class="offcanvas-header border-bottom py-3">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0">
                <i id="canvasIcon" class="feather-plus-circle me-2 text-primary"></i>
                <span id="canvasTitle">Add Tax</span>
            </h5>
            <small class="text-muted">Tax rates per country (GST, SST, VAT…).</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="taxForm" novalidate>
            @csrf
            <input type="hidden" id="editTaxId">

            <div class="mb-3">
                <label class="form-label fw-semibold">Country <span class="text-danger">*</span></label>
                <select id="tax_country_id" class="form-control select2-field" data-placeholder="Select country…">
                    <option value=""></option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                    @endforeach
                </select>
                <div class="field-error" id="country_idError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tax Name <span class="text-danger">*</span></label>
                <input type="text" id="tax_name" class="form-control" placeholder="e.g. GST, SST, VAT">
                <div class="field-error" id="tax_nameError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tax Code <span class="text-danger">*</span></label>
                <input type="text" id="tax_code" class="form-control text-uppercase" maxlength="20" placeholder="e.g. GST-SG">
                <div class="field-error" id="tax_codeError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tax Type <span class="text-danger">*</span></label>
                <select id="tax_type" class="form-control select2-field" data-placeholder="Select type…" data-minimum-results-for-search="Infinity">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
                <div class="field-error" id="tax_typeError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tax Rate <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.001" min="0" id="tax_rate" class="form-control" placeholder="e.g. 9.000">
                    <span class="input-group-text" id="taxRateSuffix">%</span>
                </div>
                <div class="field-error" id="tax_rateError"></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select id="tax_status" class="form-control select2-field" data-placeholder="Select status…" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="taxSpinner"></span>
                    <i class="feather-save me-2" id="taxBtnIcon"></i>
                    <span id="taxBtnText">Save</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Taxes</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Bookstore</li>
            <li class="breadcrumb-item">Taxes</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <button class="btn btn-primary" onclick="openAdd()"><i class="feather-plus me-2"></i>Add Tax</button>
    </div>
</div>

<div class="main-content">
    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total','value'=>$stats['total'],'icon'=>'feather-percent','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
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
            <span class="fw-semibold">All Taxes</span>

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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search…">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th><th>Country</th><th>Tax Name</th><th>Tax Code</th>
                            <th>Type</th><th class="text-end">Rate</th>
                            <th class="text-center">Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="taxTableBody">
                        @forelse($taxes as $tax)
                        <tr class="tax-row" id="tax-row-{{ $tax->id }}"
                            data-status="{{ $tax->status ? 'active' : 'inactive' }}"
                            data-id="{{ $tax->id }}"
                            data-country-id="{{ $tax->country_id }}"
                            data-tax-name="{{ $tax->tax_name }}"
                            data-tax-code="{{ $tax->tax_code }}"
                            data-tax-type="{{ $tax->tax_type }}"
                            data-tax-rate="{{ $tax->tax_rate }}"
                            data-is-active="{{ $tax->status ? '1' : '0' }}">
                            <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                            <td class="fs-13">{{ $tax->country->name ?? '—' }}</td>
                            <td class="fw-semibold fs-13 text-dark">{{ $tax->tax_name }}</td>
                            <td><span class="badge bg-light-primary text-primary">{{ $tax->tax_code }}</span></td>
                            <td class="fs-13 text-capitalize">{{ $tax->tax_type }}</td>
                            <td class="text-end fs-13">
                                {{ $tax->tax_type === 'percentage' ? rtrim(rtrim(number_format($tax->tax_rate, 3), '0'), '.') . '%' : number_format($tax->tax_rate, 2) }}
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox"
                                           {{ $tax->status ? 'checked' : '' }} data-id="{{ $tax->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="javascript:void(0);" class="avatar-text avatar-md" title="Edit"
                                       onclick="openEdit(this)" data-row="tax-row-{{ $tax->id }}">
                                        <i class="feather feather-edit-3"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="avatar-text avatar-md text-danger" title="Delete"
                                       onclick="deleteRow({{ $tax->id }}, '{{ addslashes($tax->tax_name) }}')">
                                        <i class="feather feather-trash-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="feather-percent fs-30 d-block mb-2 opacity-50"></i>
                                No taxes found.
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
.form-control.input-error + .select2-container .select2-selection{border-color:#ef4444!important;}

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
.select2-container--default .select2-selection--single .select2-selection__placeholder{ color:#9aa0ac; }
.select2-container--default .select2-selection--single .select2-selection__arrow{ height:44px; right:10px; }
.select2-dropdown{ border:1px solid #e2e5ec; border-radius:12px; box-shadow:0 12px 32px rgba(17,24,39,.12); overflow:hidden; padding:6px; }
.select2-container--default .select2-search--dropdown{ padding:6px 6px 10px 6px; }
.select2-container--default .select2-search--dropdown .select2-search__field{ border:1px solid #e2e5ec; border-radius:8px; padding:8px 12px; font-size:13px; outline:none; }
.select2-container--default .select2-search--dropdown .select2-search__field:focus{ border-color:var(--select2-accent); }
.select2-container--default .select2-results__option{ border-radius:8px; padding:9px 12px; font-size:14px; color:#374151; }
.select2-container--default .select2-results__option--highlighted[aria-selected]{ background:var(--select2-accent-soft); color:var(--select2-accent); }
.select2-container--default .select2-results__option[aria-selected=true]{ background:var(--select2-accent); color:#fff; font-weight:600; }
.select2-results__option{ margin-bottom:2px; }
.select2-container--default .select2-results > .select2-results__options{ max-height:240px; }

.entries-select{ width:auto; border-radius:8px; border:1px solid #e2e5ec; font-size:13px; padding:4px 28px 4px 10px; }
.entries-select:focus{ border-color:#7b5cf0; box-shadow:0 0 0 3px rgba(123,92,240,.12); }
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

// Route names resolved server-side by Blade. ":id" is a placeholder swapped
// in on the client before each request — no hardcoded URLs anywhere in JS.
const ROUTES = {
    store:   "{{ route('taxes.store') }}",
    update:  "{{ route('taxes.update', ':id') }}",
    toggle:  "{{ route('taxes.toggle', ':id') }}",
    destroy: "{{ route('taxes.destroy', ':id') }}",
};

$(function(){
    $('#tax_country_id').select2({
        dropdownParent: $('#taxCanvas'),
        width: '100%',
        placeholder: $('#tax_country_id').data('placeholder'),
        allowClear: true,
    });
    $('#tax_type').select2({
        dropdownParent: $('#taxCanvas'),
        width: '100%',
        minimumResultsForSearch: Infinity,
    });
    $('#tax_status').select2({
        dropdownParent: $('#taxCanvas'),
        width: '100%',
        minimumResultsForSearch: Infinity,
    });

    // toggle rate suffix between % and currency-neutral fixed label
    $('#tax_type').on('change', function () {
        document.getElementById('taxRateSuffix').textContent = this.value === 'fixed' ? 'Amt' : '%';
    });
});

/* ===== Front-end pagination (no reload, no controller changes) ===== */
(function () {
    const rowSelector   = '.tax-row';
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

    window.__taxPaginationRender = render;
    render();
})();

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
function resetForm(){
    document.getElementById('taxForm').reset();
    document.getElementById('editTaxId').value='';
    $('#tax_country_id').val(null).trigger('change');
    $('#tax_type').val('percentage').trigger('change');
    $('#tax_status').val('1').trigger('change');
    clearErrors();
}
function openAdd(){
    editMode=false; resetForm();
    document.getElementById('canvasIcon').className='feather-plus-circle me-2 text-primary';
    document.getElementById('canvasTitle').textContent='Add Tax';
    document.getElementById('taxBtnText').textContent='Save';
    new bootstrap.Offcanvas(document.getElementById('taxCanvas')).show();
}
function openEdit(btn){
    editMode=true; resetForm();
    const d=document.getElementById(btn.dataset.row).dataset;
    document.getElementById('canvasIcon').className='feather-edit-3 me-2 text-warning';
    document.getElementById('canvasTitle').textContent='Edit Tax';
    document.getElementById('taxBtnText').textContent='Update';
    document.getElementById('editTaxId').value=d.id;
    document.getElementById('tax_name').value=d.taxName;
    document.getElementById('tax_code').value=d.taxCode;
    document.getElementById('tax_rate').value=d.taxRate;
    $('#tax_country_id').val(d.countryId).trigger('change');
    $('#tax_type').val(d.taxType).trigger('change');
    $('#tax_status').val(d.isActive).trigger('change');
    new bootstrap.Offcanvas(document.getElementById('taxCanvas')).show();
}

document.getElementById('taxForm').addEventListener('submit', function(e){
    e.preventDefault(); clearErrors();
    const id=document.getElementById('editTaxId').value;
    const btn=document.getElementById('submitBtn'), sp=document.getElementById('taxSpinner'),
          ic=document.getElementById('taxBtnIcon'), tx=document.getElementById('taxBtnText');
    btn.disabled=true; sp.classList.remove('d-none'); ic.classList.add('d-none'); tx.textContent='Saving…';

    const payload = {
        country_id: $('#tax_country_id').val(),
        tax_name: document.getElementById('tax_name').value,
        tax_code: document.getElementById('tax_code').value,
        tax_type: $('#tax_type').val(),
        tax_rate: document.getElementById('tax_rate').value,
        status: $('#tax_status').val(),
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
            bootstrap.Offcanvas.getInstance(document.getElementById('taxCanvas'))?.hide();
            setTimeout(()=>location.reload(),1200);
        } else if(d.errors){
            const map = { country_id: 'country_idError' };
            Object.keys(d.errors).forEach(f=>fieldErr('tax_'+f, map[f] || f+'Error', d.errors[f][0]));
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

document.querySelectorAll('.status-toggle').forEach(toggle=>{
    toggle.addEventListener('change', function(){
        const id=this.dataset.id;
        fetch(ROUTES.toggle.replace(':id', id), {method:'PATCH', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json())
        .then(d=>showToast(d.message, d.status==='success'?'success':'error'))
        .catch(()=>{ this.checked=!this.checked; showToast('Something went wrong!','error'); });
    });
});

function deleteRow(id,name){
    if(!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    fetch(ROUTES.destroy.replace(':id', id), {method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='success'){
            document.getElementById(`tax-row-${id}`)?.remove();
            showToast(d.message,'success');
            window.__taxPaginationRender && window.__taxPaginationRender();
        } else { showToast(d.message,'error'); }
    })
    .catch(()=>showToast('Something went wrong!','error'));
}
</script>
@endpush