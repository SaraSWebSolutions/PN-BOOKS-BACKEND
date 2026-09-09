@extends('layouts.app')
@section('title', 'Edit Permission')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Permission</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
            <li class="breadcrumb-item">Edit</li>
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
        <div class="col-lg-8 mx-auto">
            <div class="card stretch stretch-full">
                <div class="card-body">

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1">Edit Permission</h5>
                        <span class="fs-12 text-muted">
                            Currently: <code>{{ $permission->name }}</code> — used by
                            <strong>{{ $permission->roles->count() }}</strong> role(s).
                            Renaming it updates the permission everywhere it's assigned.
                        </span>
                    </div>

                    @if($permission->roles->count() > 0)
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="feather-alert-triangle"></i>
                        <div class="fs-13">
                            This permission is currently assigned to
                            <strong>{{ $permission->roles->pluck('name')->implode(', ') }}</strong>.
                            Renaming it keeps those assignments intact (the link is by ID), only the label changes.
                        </div>
                    </div>
                    @endif

                    <form id="editPermissionForm" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row mb-4">
                            {{-- Action --}}
                            <div class="col-lg-6">
                                <label class="form-label fw-semibold">
                                    Action <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-zap"></i></div>
                                    <input type="text" name="action" id="action" class="form-control"
                                           value="{{ $action }}" placeholder="e.g. view, create, edit, approve">
                                </div>
                                <div class="field-error" id="actionError"></div>
                                <small class="text-muted">First word of the permission name.</small>
                            </div>

                            {{-- Module --}}
                            <div class="col-lg-6">
                                <label class="form-label fw-semibold">
                                    Module Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="text" name="module" id="module" class="form-control"
                                           value="{{ $module }}" placeholder="e.g. contacts, products, orders">
                                </div>
                                <div class="field-error" id="moduleError"></div>
                                <small class="text-muted">Second word — e.g. <strong>{{ $action ?: 'view' }} [module]</strong></small>
                            </div>
                        </div>

                        <hr>

                        {{-- Preview --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="feather-eye me-2"></i> Permission Preview
                            </label>
                            <div id="permissionPreview" class="p-3 rounded border" style="min-height:50px;background:#f8f9fa;">
                                <span class="badge bg-soft-success text-success p-2 fs-12" id="previewBadge">
                                    {{ $action }} {{ $module }}
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('permissions.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-save me-2" id="btnIcon"></i>
                                <span id="btnText">Update Permission</span>
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

// ── Live Preview ──
function updatePreview() {
    const action = document.getElementById('action').value.trim().toLowerCase();
    const module = document.getElementById('module').value.trim().toLowerCase();
    const badge  = document.getElementById('previewBadge');

    if (!action || !module) {
        badge.textContent = '— incomplete —';
        badge.className = 'badge bg-soft-secondary text-secondary p-2 fs-12';
        return;
    }
    badge.textContent = action + ' ' + module;
    badge.className = 'badge bg-soft-success text-success p-2 fs-12';
}
document.getElementById('action').addEventListener('input', updatePreview);
document.getElementById('module').addEventListener('input', updatePreview);

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
document.getElementById('editPermissionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    const action = document.getElementById('action').value.trim();
    const module = document.getElementById('module').value.trim();

    if (!action) {
        showFieldError('action', 'actionError', 'Action is required.');
        showToast('Please enter an action.', 'error');
        return;
    }
    if (!module) {
        showFieldError('module', 'moduleError', 'Module name is required.');
        showToast('Please enter a module name.', 'error');
        return;
    }

    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Updating...';

    const formData = {
        action:  action,
        module:  module,
        _method: 'PUT',
        _token:  document.querySelector('input[name="_token"]').value,
    };

    fetch("{{ route('permissions.update', $permission) }}", {
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
        btnText.textContent = 'Update Permission';

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
        btnText.textContent = 'Update Permission';
        showToast('Something went wrong. Please try again.', 'error');
    });
});
</script>
@endpush