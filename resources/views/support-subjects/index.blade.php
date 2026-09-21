@extends('layouts.app')
@section('title', 'Support Subjects')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span>
    <span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="subCanvas" style="width:420px;">
    <div class="offcanvas-header border-bottom py-3">
        <h5 class="offcanvas-title fw-bold mb-0"><span id="canvasTitle">Add Subject</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="subForm" novalidate>
            <input type="hidden" id="editSubId">

            <div class="mb-3">
                <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
                <input type="text" id="sub_name" class="form-control" placeholder="e.g. Payment Issue">
                <div class="field-error" id="nameError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Route To Email</label>
                <input type="email" id="sub_notify_email" class="form-control" placeholder="team@pnbooks.com">
                <small class="text-muted">Leave blank to use default support inbox.</small>
                <div class="field-error" id="notify_emailError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Sort Order</label>
                <input type="number" id="sub_sort_order" class="form-control" min="0" value="0">
                <div class="field-error" id="sort_orderError"></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select id="sub_is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-brand flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="subSpinner"></span>
                    <i class="feather-save me-2" id="subBtnIcon"></i>
                    <span id="subBtnText">Save</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Support Subjects</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Website</li>
            <li class="breadcrumb-item">Support Subjects</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <button class="btn btn-primary" onclick="openAdd()"><i class="feather-plus me-2"></i>Add Subject</button>
    </div>
</div>

<div class="main-content">
    <div class="card stretch stretch-full">
        <div class="card-header py-3"><span class="fw-semibold">All Subjects</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Route Email</th>
                            <th class="text-center">Order</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $sub)
                        <tr id="sub-row-{{ $sub->id }}"
                            data-id="{{ $sub->id }}"
                            data-name="{{ $sub->name }}"
                            data-notify-email="{{ $sub->notify_email }}"
                            data-sort-order="{{ $sub->sort_order }}"
                            data-is_active="{{ $sub->is_active ? '1' : '0' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $sub->name }}</td>
                            <td class="text-muted">{{ $sub->notify_email ?: '—' }}</td>
                            <td class="text-center">{{ $sub->sort_order }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox"
                                           {{ $sub->is_active ? 'checked' : '' }} data-id="{{ $sub->id }}">
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="javascript:void(0);" title="Edit" onclick="openEdit(this)" data-row="sub-row-{{ $sub->id }}">
                                    <i class="feather feather-edit-3"></i>
                                </a>
                                <a href="javascript:void(0);" class="text-danger ms-2" title="Delete" onclick="deleteRow({{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                                    <i class="feather feather-trash-2"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="6" class="text-center py-5 text-muted">
                                No subjects found. Click <strong>Add Subject</strong> to get started.
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
const updateUrlTemplate  = "{{ route('support-subjects.update',  ['support_subject' => '__ID__']) }}";
const toggleUrlTemplate  = "{{ route('support-subjects.toggle',  ['support_subject' => '__ID__']) }}";
const destroyUrlTemplate = "{{ route('support-subjects.destroy', ['support_subject' => '__ID__']) }}";

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
    document.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('input-error'));
}
function fieldErr(fId, eId, msg) {
    document.getElementById(fId)?.classList.add('input-error');
    const e = document.getElementById(eId);
    if (e) { e.textContent = msg; e.classList.add('show'); }
}

function resetForm() {
    document.getElementById('subForm').reset();
    document.getElementById('editSubId').value = '';
    clearErrors();
}

function openAdd() {
    editMode = false;
    resetForm();
    document.getElementById('canvasTitle').textContent = 'Add Subject';
    document.getElementById('subBtnText').textContent  = 'Save';
    new bootstrap.Offcanvas(document.getElementById('subCanvas')).show();
}

function openEdit(btn) {
    editMode = true;
    resetForm();
    const d = document.getElementById(btn.dataset.row).dataset;
    document.getElementById('editSubId').value        = d.id;
    document.getElementById('sub_name').value           = d.name;
    document.getElementById('sub_notify_email').value   = d.notifyEmail || '';
    document.getElementById('sub_sort_order').value     = d.sortOrder;
   document.getElementById('sub_is_active').value = d.is_active;
    document.getElementById('canvasTitle').textContent = 'Edit Subject';
    document.getElementById('subBtnText').textContent  = 'Update';
    new bootstrap.Offcanvas(document.getElementById('subCanvas')).show();
}

document.getElementById('subForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const id  = document.getElementById('editSubId').value;
    const btn = document.getElementById('submitBtn');
    const sp  = document.getElementById('subSpinner');
    const ic  = document.getElementById('subBtnIcon');
    const tx  = document.getElementById('subBtnText');

    btn.disabled = true;
    sp.classList.remove('d-none');
    ic.classList.add('d-none');
    tx.textContent = 'Saving…';

    const payload = {
        name: document.getElementById('sub_name').value,
        notify_email: document.getElementById('sub_notify_email').value,
        sort_order: document.getElementById('sub_sort_order').value,
        is_active: document.getElementById('sub_is_active').value,
    };
    if (editMode) payload._method = 'PUT';

    const url = editMode
        ? updateUrlTemplate.replace('__ID__', id)
        : "{{ route('support-subjects.store') }}";

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ d }) => {
        btn.disabled = false;
        sp.classList.add('d-none');
        ic.classList.remove('d-none');
        tx.textContent = editMode ? 'Update' : 'Save';

        if (d.status === 'success') {
            showToast(d.message, 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('subCanvas'))?.hide();
            setTimeout(() => location.reload(), 1200);
        } else if (d.errors) {
            Object.keys(d.errors).forEach(f => fieldErr('sub_' + f, f + 'Error', d.errors[f][0]));
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
        const url = toggleUrlTemplate.replace('__ID__', id);
        fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => showToast(d.message, d.status === 'success' ? 'success' : 'error'))
        .catch(() => { this.checked = !this.checked; showToast('Something went wrong!', 'error'); });
    });
});

function deleteRow(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const url = destroyUrlTemplate.replace('__ID__', id);
    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            document.getElementById(`sub-row-${id}`)?.remove();
            showToast(d.message, 'success');
        } else {
            showToast(d.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}
</script>
@endpush