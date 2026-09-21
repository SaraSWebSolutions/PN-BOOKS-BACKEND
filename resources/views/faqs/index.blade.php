{{-- resources/views/faqs/index.blade.php --}}
@extends('layouts.app')
@section('title', 'FAQs')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- ══ OFFCANVAS — Add / Edit ══ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="faqCanvas" style="width:460px;">
    <div class="offcanvas-header border-bottom py-3">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0">
                <i id="canvasIcon" class="feather-plus-circle me-2 text-primary"></i>
                <span id="canvasTitle">Add FAQ</span>
            </h5>
            <small class="text-muted">Website Help &amp; Support / FAQ page content.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="faqForm" novalidate>
            @csrf
            <input type="hidden" id="editFaqId">

            <div class="mb-3">
                <label class="form-label fw-semibold">Show On <span class="text-danger">*</span></label>
                <select id="faq_type" class="form-control">
                    @foreach(\App\Models\Faq::TYPES as $value => $label)
                        <option value="{{ $value }}" {{ $value === 'both' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">Help &amp; Support page, FAQ page, or both.</div>
                <div class="field-error" id="typeError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
                <input type="text" id="faq_question" class="form-control" placeholder="e.g. How do I reset my password?">
                <div class="field-error" id="questionError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Answer <span class="text-danger">*</span></label>
                <textarea id="faq_answer" class="form-control" rows="4"></textarea>
                <div class="field-error" id="answerError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Sort Order</label>
                <input type="number" id="faq_sort_order" class="form-control" min="0" value="0">
                <div class="field-error" id="sort_orderError"></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select id="faq_is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="faqSpinner"></span>
                    <i class="feather-save me-2" id="faqBtnIcon"></i>
                    <span id="faqBtnText">Save</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">FAQs</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Website</li>
            <li class="breadcrumb-item">FAQs</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <button class="btn btn-primary" onclick="openAdd()">
            <i class="feather-plus me-2"></i>Add FAQ
        </button>
    </div>
</div>

<div class="main-content">

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total','value'=>$stats['total'],'icon'=>'feather-help-circle','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
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
            <span class="fw-semibold">All FAQs</span>
            <div class="d-flex gap-2 flex-wrap">
                <select id="typeFilter" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All pages</option>
                    <option value="help_support">Shown on Help &amp; Support</option>
                    <option value="faq">Shown on FAQ page</option>
                </select>
                <div class="input-group input-group-sm" style="max-width:220px;">
                    <span class="input-group-text"><i class="feather-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search FAQs…">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Show On</th>
                            <th class="text-center">Order</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="faqTableBody">
                        @forelse($faqs as $faq)
                        <tr class="faq-row" id="faq-row-{{ $faq->id }}"
                            data-id="{{ $faq->id }}"
                            data-question="{{ $faq->question }}"
                            data-answer="{{ $faq->answer }}"
                            data-type="{{ $faq->type }}"
                            data-sort-order="{{ $faq->sort_order }}"
                            data-is-active="{{ $faq->is_active ? '1' : '0' }}">
                            <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                            <td class="fw-semibold fs-13 text-dark">{{ $faq->question }}</td>
                            <td class="text-muted fs-12">
                                <span class="text-truncate d-inline-block" style="max-width:320px;">{{ $faq->answer }}</span>
                            </td>
                            <td>
                                @php
                                    $badge = match($faq->type) {
                                        'help_support' => 'bg-soft-primary text-primary',
                                        'faq'          => 'bg-soft-success text-success',
                                        default        => 'bg-soft-warning text-warning',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $faq->type_label }}</span>
                            </td>
                            <td class="text-center">{{ $faq->sort_order }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox"
                                           {{ $faq->is_active ? 'checked' : '' }} data-id="{{ $faq->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="javascript:void(0);" class="avatar-text avatar-md" title="Edit"
                                       onclick="openEdit(this)" data-row="faq-row-{{ $faq->id }}">
                                        <i class="feather feather-edit-3"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="avatar-text avatar-md text-danger" title="Delete"
                                       onclick="deleteRow({{ $faq->id }}, '{{ addslashes($faq->question) }}')">
                                        <i class="feather feather-trash-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="7" class="text-center py-5 text-muted">
                                No FAQs found. Click <strong>Add FAQ</strong> to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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
.field-error{font-size:12px;color:#ef4444;margin-top:4px;display:none;}
.field-error.show{display:block;}
.form-control.input-error{border-color:#ef4444!important;}
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let editMode = false;

const faqUpdateUrlTemplate  = "{{ route('faqs.update',  ['faq' => '__FAQ_ID__']) }}";
const faqToggleUrlTemplate  = "{{ route('faqs.toggle',  ['faq' => '__FAQ_ID__']) }}";
const faqDestroyUrlTemplate = "{{ route('faqs.destroy', ['faq' => '__FAQ_ID__']) }}";

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

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(e => { e.textContent=''; e.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('input-error'));
}
function fieldErr(fId, eId, msg) {
    document.getElementById(fId)?.classList.add('input-error');
    const e = document.getElementById(eId);
    if (e) { e.textContent = msg; e.classList.add('show'); }
}

function resetForm() {
    document.getElementById('faqForm').reset();
    document.getElementById('editFaqId').value = '';
    document.getElementById('faq_type').value = 'both';
    clearErrors();
}

function openAdd() {
    editMode = false;
    resetForm();
    document.getElementById('canvasIcon').className   = 'feather-plus-circle me-2 text-primary';
    document.getElementById('canvasTitle').textContent = 'Add FAQ';
    document.getElementById('faqBtnText').textContent  = 'Save';
    new bootstrap.Offcanvas(document.getElementById('faqCanvas')).show();
}

function openEdit(btn) {
    editMode = true;
    resetForm();
    const d = document.getElementById(btn.dataset.row).dataset;
    document.getElementById('editFaqId').value        = d.id;
    document.getElementById('faq_question').value     = d.question;
    document.getElementById('faq_answer').value       = d.answer;
    document.getElementById('faq_type').value         = d.type || 'both';
    document.getElementById('faq_sort_order').value   = d.sortOrder;
    document.getElementById('faq_is_active').value    = d.isActive;
    document.getElementById('canvasIcon').className   = 'feather-edit-3 me-2 text-primary';
    document.getElementById('canvasTitle').textContent = 'Edit FAQ';
    document.getElementById('faqBtnText').textContent  = 'Update';
    new bootstrap.Offcanvas(document.getElementById('faqCanvas')).show();
}

document.getElementById('faqForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const id  = document.getElementById('editFaqId').value;
    const btn = document.getElementById('submitBtn');
    const sp  = document.getElementById('faqSpinner');
    const ic  = document.getElementById('faqBtnIcon');
    const tx  = document.getElementById('faqBtnText');

    btn.disabled = true;
    sp.classList.remove('d-none');
    ic.classList.add('d-none');
    tx.textContent = 'Saving…';

    const payload = {
        question:   document.getElementById('faq_question').value,
        answer:     document.getElementById('faq_answer').value,
        type:       document.getElementById('faq_type').value,
        sort_order: document.getElementById('faq_sort_order').value,
        is_active:  document.getElementById('faq_is_active').value,
        _token: CSRF,
    };
    if (editMode) payload._method = 'PUT';

    const url = editMode
        ? faqUpdateUrlTemplate.replace('__FAQ_ID__', id)
        : "{{ route('faqs.store') }}";

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';

        if (d.status === 'success') {
            showToast(d.message, 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('faqCanvas'))?.hide();
            setTimeout(() => location.reload(), 1200);
        } else if (d.errors) {
            Object.keys(d.errors).forEach(f => fieldErr('faq_' + f, f + 'Error', d.errors[f][0]));
            showToast('Please fix the errors below.', 'error');
        } else {
            showToast(d.message || 'Something went wrong!', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';
        showToast('Network error — try again.', 'error');
    });
});

document.querySelectorAll('.status-toggle').forEach(toggle => {
    toggle.addEventListener('change', function () {
        const id = this.dataset.id;
        const url = faqToggleUrlTemplate.replace('__FAQ_ID__', id);
        fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => showToast(d.message, d.status === 'success' ? 'success' : 'error'))
        .catch(() => { this.checked = !this.checked; showToast('Something went wrong!', 'error'); });
    });
});

function deleteRow(id, question) {
    if (!confirm(`Delete "${question}"? This cannot be undone.`)) return;
    const url = faqDestroyUrlTemplate.replace('__FAQ_ID__', id);
    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            document.getElementById(`faq-row-${id}`)?.remove();
            showToast(d.message, 'success');
        } else {
            showToast(d.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}

/* ── Search + page filter (same logic as the API: type OR "both") ── */
function applyFilters() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const t = document.getElementById('typeFilter').value;
    let n = 0;

    document.querySelectorAll('.faq-row').forEach(row => {
        const d = row.dataset;
        const okSearch = !q || d.question.toLowerCase().includes(q) || d.answer.toLowerCase().includes(q);
        const okType   = !t || d.type === t || d.type === 'both';
        const show = okSearch && okType;
        row.style.display = show ? '' : 'none';
        if (show) row.querySelector('.row-index').textContent = ++n;
    });
}
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('typeFilter').addEventListener('change', applyFilters);
</script>
@endpush