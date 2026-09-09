@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Role</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item">Edit</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('roles.index') }}" class="btn btn-light-brand">
            <i class="feather-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body">

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1">Edit Role: <span class="text-capitalize">{{ $role->name }}</span></h5>
                        <span class="fs-12 text-muted">Update role name and permissions</span>
                    </div>

                    <form id="editRoleForm" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-shield"></i></div>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ $role->name }}">
                                </div>
                                <div class="field-error" id="nameError"></div>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-0">
                                    <i class="feather-key me-2"></i> Permissions
                                </h6>
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-sm btn-light-brand" onclick="selectAll()">Select All</button>
                                    <button type="button" class="btn btn-sm btn-light-brand" onclick="deselectAll()">Deselect All</button>
                                </div>
                            </div>

                            <div class="row">
                                @foreach($permissions as $module => $perms)
                                <div class="col-lg-4 mb-4">
                                    <div class="card border">
                                        <div class="card-header py-2 d-flex align-items-center justify-content-between">
                                            <h6 class="fw-semibold text-capitalize mb-0">{{ $module }}</h6>
                                            @php
                                                $modulePermsIds = $perms->pluck('id')->toArray();
                                                $allChecked = count(array_intersect($modulePermsIds, $rolePermissions)) === count($modulePermsIds);
                                            @endphp
                                            <input type="checkbox" class="module-check" data-module="{{ $module }}"
                                                   {{ $allChecked ? 'checked' : '' }}
                                                   onchange="toggleModule('{{ $module }}', this.checked)">
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($perms as $perm)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input perm-check perm-{{ $module }}"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm->id }}"
                                                       id="perm_{{ $perm->id }}"
                                                       {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label text-capitalize" for="perm_{{ $perm->id }}">
                                                    {{ explode(' ', $perm->name)[0] }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-save me-2" id="btnIcon"></i>
                                <span id="btnText">Update Role</span>
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
    const toast=document.getElementById('ajaxToast');
    const msgEl=document.getElementById('toastMessage');
    const iconEl=document.getElementById('toastIcon');
    msgEl.textContent=message;
    toast.className='ajax-toast';
    toast.classList.add(type==='success'?'toast-success':'toast-error');
    iconEl.textContent=type==='success'?'✅':'❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>toast.classList.remove('show'),4000);
}
function hideToast(){document.getElementById('ajaxToast').classList.remove('show');}

function selectAll() {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = true);
    document.querySelectorAll('.module-check').forEach(cb => cb.checked = true);
}
function deselectAll() {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
    document.querySelectorAll('.module-check').forEach(cb => cb.checked = false);
}
function toggleModule(module, checked) {
    document.querySelectorAll(`.perm-${module}`).forEach(cb => cb.checked = checked);
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => { el.textContent=''; el.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}
function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent=message; err.classList.add('show'); }
}

document.getElementById('editRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Updating...';

    const permissions = Array.from(document.querySelectorAll('.perm-check:checked')).map(cb => cb.value);

    const formData = {
        name:        document.getElementById('name').value.trim(),
        permissions: permissions,
        _method:     'PUT',
        _token:      document.querySelector('input[name="_token"]').value,
    };

    fetch("{{ route('roles.update', $role) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': formData._token,
            'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnIcon.classList.remove('d-none');
        btnText.textContent = 'Update Role';

        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => window.location.href = data.redirect, 1500);
        } else if (data.errors) {
            Object.keys(data.errors).forEach(field => {
                showFieldError(field, field + 'Error', data.errors[field][0]);
            });
            showToast('Please fix the errors.', 'error');
        } else {
            showToast(data.message || 'Something went wrong!', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnIcon.classList.remove('d-none');
        btnText.textContent = 'Update Role';
        showToast('Something went wrong.', 'error');
    });
});
</script>
@endpush