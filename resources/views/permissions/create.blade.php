@extends('layouts.app')
@section('title', 'Add Permission')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Add Permission</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
            <li class="breadcrumb-item">Create</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('permissions.index') }}" class="btn btn-light-brand">
            <i class="feather-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card stretch stretch-full">
                <div class="card-body">

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1">Create New Permission</h5>
                        <span class="fs-12 text-muted">
                            Permissions are auto-generated as:
                            <code>action module</code> — e.g.
                            <strong>view contacts</strong>,
                            <strong>approve orders</strong>,
                            <strong>export reports</strong>
                        </span>
                    </div>

                    <form id="createPermissionForm" novalidate>
                        @csrf

                        {{-- Module Name --}}
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label class="form-label fw-semibold">
                                    Module Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="text" name="module" id="module" class="form-control"
                                           placeholder="e.g. contacts, products, orders, invoices">
                                </div>
                                <div class="field-error" id="moduleError"></div>
                                <small class="text-muted">
                                    This will be the second word of the permission. e.g. <strong>view [module]</strong>
                                </small>
                            </div>
                        </div>

                        <hr>

                        {{-- Actions --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <label class="form-label fw-semibold mb-0">
                                    Select Actions <span class="text-danger">*</span>
                                </label>
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-sm btn-light-brand" onclick="selectAll()">
                                        <i class="feather-check-square me-1"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light-brand" onclick="deselectAll()">
                                        <i class="feather-square me-1"></i> Deselect All
                                    </button>
                                </div>
                            </div>

                            {{-- Standard CRUD --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-soft-primary text-primary fw-semibold">
                                        <i class="feather-database me-1"></i> Standard CRUD
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>
                                <div class="row">
                                    @foreach(['view', 'create', 'edit', 'delete'] as $action)
                                    <div class="col-lg-2 col-4 mb-3">
                                        <div class="card border action-card" id="card_{{ $action }}" onclick="toggleAction('{{ $action }}')">
                                            <div class="card-body text-center py-3 px-2">
                                                <input class="form-check-input action-check d-none"
                                                       type="checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}"
                                                       checked>
                                                <i class="feather-
                                                    @if($action === 'view') eye
                                                    @elseif($action === 'create') plus-circle
                                                    @elseif($action === 'edit') edit-3
                                                    @elseif($action === 'delete') trash-2
                                                    @endif
                                                    fs-20 d-block mb-1 action-icon"></i>
                                                <span class="fs-12 fw-semibold text-capitalize">{{ $action }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Data Actions --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-soft-success text-success fw-semibold">
                                        <i class="feather-hard-drive me-1"></i> Data Actions
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>
                                <div class="row">
                                    @foreach([
                                        'export'   => 'upload',
                                        'import'   => 'download',
                                        'download' => 'arrow-down-circle',
                                        'upload'   => 'arrow-up-circle',
                                        'print'    => 'printer',
                                        'report'   => 'bar-chart-2',
                                    ] as $action => $icon)
                                    <div class="col-lg-2 col-4 mb-3">
                                        <div class="card border action-card" id="card_{{ $action }}" onclick="toggleAction('{{ $action }}')">
                                            <div class="card-body text-center py-3 px-2">
                                                <input class="form-check-input action-check d-none"
                                                       type="checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}">
                                                <i class="feather-{{ $icon }} fs-20 d-block mb-1 action-icon"></i>
                                                <span class="fs-12 fw-semibold text-capitalize">{{ $action }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Workflow Actions --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-soft-warning text-warning fw-semibold">
                                        <i class="feather-git-branch me-1"></i> Workflow Actions
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>
                                <div class="row">
                                    @foreach([
                                        'approve'  => 'check-circle',
                                        'reject'   => 'x-circle',
                                        'publish'  => 'globe',
                                        'archive'  => 'archive',
                                        'restore'  => 'rotate-ccw',
                                        'send'     => 'send',
                                    ] as $action => $icon)
                                    <div class="col-lg-2 col-4 mb-3">
                                        <div class="card border action-card" id="card_{{ $action }}" onclick="toggleAction('{{ $action }}')">
                                            <div class="card-body text-center py-3 px-2">
                                                <input class="form-check-input action-check d-none"
                                                       type="checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}">
                                                <i class="feather-{{ $icon }} fs-20 d-block mb-1 action-icon"></i>
                                                <span class="fs-12 fw-semibold text-capitalize">{{ $action }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Access Actions --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-soft-info text-info fw-semibold">
                                        <i class="feather-lock me-1"></i> Access Actions
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>
                                <div class="row">
                                    @foreach([
                                        'assign'   => 'user-plus',
                                        'revoke'   => 'user-minus',
                                        'manage'   => 'settings',
                                        'verify'   => 'shield',
                                        'cancel'   => 'slash',
                                        'confirm'  => 'check-square',
                                    ] as $action => $icon)
                                    <div class="col-lg-2 col-4 mb-3">
                                        <div class="card border action-card" id="card_{{ $action }}" onclick="toggleAction('{{ $action }}')">
                                            <div class="card-body text-center py-3 px-2">
                                                <input class="form-check-input action-check d-none"
                                                       type="checkbox"
                                                       name="actions[]"
                                                       value="{{ $action }}"
                                                       id="action_{{ $action }}">
                                                <i class="feather-{{ $icon }} fs-20 d-block mb-1 action-icon"></i>
                                                <span class="fs-12 fw-semibold text-capitalize">{{ $action }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Custom Action --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-soft-danger text-danger fw-semibold">
                                        <i class="feather-plus-square me-1"></i> Custom Action
                                    </span>
                                    <hr class="flex-grow-1 m-0">
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-lg-5 mb-3">
                                        <div class="input-group">
                                            <div class="input-group-text"><i class="feather-edit-2"></i></div>
                                            <input type="text" id="customActionInput" class="form-control"
                                                   placeholder="Type custom action e.g. verify, confirm, process">
                                            <button type="button" class="btn btn-primary" onclick="addCustomAction()">
                                                <i class="feather-plus me-1"></i> Add
                                            </button>
                                        </div>
                                        <small class="text-muted">Press Enter or click Add</small>
                                    </div>
                                    <div class="col-lg-7 mb-3">
                                        <div id="customActionsList" class="hstack gap-2 flex-wrap"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="field-error" id="actionsError"></div>
                        </div>

                        <hr>

                        {{-- Preview --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="feather-eye me-2"></i> Permission Preview
                            </label>
                            <div id="permissionPreview"
                                 class="p-3 rounded border"
                                 style="min-height:50px;background:#f8f9fa;">
                                <span class="text-muted fs-12">
                                    Fill in module name and select actions to see preview...
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('permissions.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-plus me-2" id="btnIcon"></i>
                                <span id="btnText">Create Permissions</span>
                            </button>
                        </div>

                    </form>
                </div>
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
    font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.15);
    transform:translateX(120%);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:0.6;}
.field-error{font-size:12px;color:#ef4444;margin-top:4px;display:none;}
.field-error.show{display:block;}
.form-control.input-error{border-color:#ef4444!important;}

/* Action Cards */
.action-card {
    cursor:pointer;
    transition:all 0.2s;
    border-radius:8px !important;
    user-select:none;
}
.action-card:hover {
    border-color:#696cff !important;
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(105,108,255,0.15);
}
.action-card.selected {
    border-color:#696cff !important;
    background:#f0f0ff;
}
.action-card.selected .action-icon { color:#696cff; }
.action-card.selected span { color:#696cff; }
</style>
@endpush

@push('scripts')
<script>
let toastTimer = null;
function showToast(message, type='success') {
    const toast  = document.getElementById('ajaxToast');
    const msgEl  = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    msgEl.textContent = message;
    toast.className   = 'ajax-toast';
    toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
    iconEl.textContent = type === 'success' ? '✅' : '❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
}
function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }

// ── Toggle action card ──
function toggleAction(action) {
    const cb   = document.getElementById('action_' + action);
    const card = document.getElementById('card_' + action);
    cb.checked = !cb.checked;
    card.classList.toggle('selected', cb.checked);
    updatePreview();
}

// ── Select / Deselect All ──
function selectAll() {
    document.querySelectorAll('.action-check').forEach(cb => {
        cb.checked = true;
        const card = document.getElementById('card_' + cb.value);
        if (card) card.classList.add('selected');
    });
    updatePreview();
}
function deselectAll() {
    document.querySelectorAll('.action-check').forEach(cb => {
        cb.checked = false;
        const card = document.getElementById('card_' + cb.value);
        if (card) card.classList.remove('selected');
    });
    updatePreview();
}

// ── Live Preview ──
function updatePreview() {
    const module  = document.getElementById('module').value.trim().toLowerCase();
    const actions = Array.from(document.querySelectorAll('.action-check:checked')).map(cb => cb.value);
    const preview = document.getElementById('permissionPreview');

    if (!module || actions.length === 0) {
        preview.innerHTML = '<span class="text-muted fs-12">Fill in module name and select actions to see preview...</span>';
        return;
    }

    preview.innerHTML = actions.map(action =>
        `<span class="badge bg-soft-success text-success me-1 mb-1 p-2 fs-12">${action} ${module}</span>`
    ).join('');
}

document.getElementById('module').addEventListener('input', updatePreview);

// ── Custom Actions ──
function addCustomAction() {
    const input = document.getElementById('customActionInput');
    const value = input.value.trim().toLowerCase().replace(/\s+/g, '_');
    const list  = document.getElementById('customActionsList');

    if (!value) {
        showToast('Please enter a custom action name.', 'error');
        return;
    }
    if (document.getElementById('action_' + value)) {
        showToast('Action "' + value + '" already exists!', 'error');
        return;
    }

    // Hidden checkbox
    const cb = document.createElement('input');
    cb.type      = 'checkbox';
    cb.name      = 'actions[]';
    cb.value     = value;
    cb.id        = 'action_' + value;
    cb.className = 'action-check d-none';
    cb.checked   = true;
    document.getElementById('createPermissionForm').appendChild(cb);

    // Badge
    const badge = document.createElement('span');
    badge.className = 'badge bg-soft-danger text-danger d-flex align-items-center gap-2 p-2 fs-12';
    badge.id = 'custom_badge_' + value;
    badge.innerHTML = `
        <i class="feather-zap"></i>
        <span class="text-capitalize">${value}</span>
        <span onclick="removeCustomAction('${value}')"
              style="cursor:pointer;font-size:14px;line-height:1;">✕</span>
    `;
    list.appendChild(badge);

    input.value = '';
    updatePreview();
    showToast('Custom action "' + value + '" added!', 'success');
}

function removeCustomAction(value) {
    document.getElementById('custom_badge_' + value)?.remove();
    document.getElementById('action_' + value)?.remove();
    updatePreview();
}

// Enter key for custom action
document.getElementById('customActionInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addCustomAction(); }
});

// Init selected cards (standard CRUD are checked by default)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.action-check:checked').forEach(cb => {
        const card = document.getElementById('card_' + cb.value);
        if (card) card.classList.add('selected');
    });
});

// ── Errors ──
function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}
function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent = message; err.classList.add('show'); }
}

// ── Submit ──
document.getElementById('createPermissionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    // Client validation
    const module  = document.getElementById('module').value.trim();
    const actions = Array.from(document.querySelectorAll('.action-check:checked')).map(cb => cb.value);

    if (!module) {
        showFieldError('module', 'moduleError', 'Module name is required.');
        showToast('Please enter a module name.', 'error');
        return;
    }
    if (actions.length === 0) {
        const err = document.getElementById('actionsError');
        err.textContent = 'Please select at least one action.';
        err.classList.add('show');
        showToast('Please select at least one action.', 'error');
        return;
    }

    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Creating...';

    const formData = {
        module:  module,
        actions: actions,
        _token:  document.querySelector('input[name="_token"]').value,
    };

    fetch("{{ route('permissions.store') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': formData._token,
            'Accept':       'application/json',
        },
        body: JSON.stringify(formData),
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnIcon.classList.remove('d-none');
        btnText.textContent = 'Create Permissions';

        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => window.location.href = data.redirect, 1500);
        } else if (data.errors) {
            Object.keys(data.errors).forEach(field => {
                showFieldError(field, field + 'Error', data.errors[field][0]);
            });
            showToast('Please fix the errors below.', 'error');
        } else {
            showToast(data.message || 'Something went wrong!', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnIcon.classList.remove('d-none');
        btnText.textContent = 'Create Permissions';
        showToast('Something went wrong. Please try again.', 'error');
    });
});
</script>
@endpush