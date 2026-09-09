@extends('layouts.app')

@section('title', 'Create User')

@section('content')

{{-- Toast --}}
<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Create User</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
            <li class="breadcrumb-item">Create</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('users.index') }}" class="btn btn-light-brand">
            <i class="feather-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body">

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1">User Information</h5>
                        <span class="fs-12 text-muted">Fill in the details to create a new user</span>
                    </div>

                    <form id="createUserForm" novalidate>
                        @csrf

                        <div class="row">
                            {{-- Name --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-user"></i></div>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter full name">
                                </div>
                                <div class="field-error" id="nameError"></div>
                            </div>
                            {{-- Employee Photo --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">User Photo</label>
    <div class="d-flex align-items-center gap-3">
        <div id="photoPreviewWrap" style="width:56px;height:56px;border-radius:50%;overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e2e8f0;">
            <img id="photoPreview" src="" style="width:100%;height:100%;object-fit:cover;display:none;">
            <i class="feather-user" id="photoPlaceholder" style="color:#94a3b8;font-size:20px;"></i>
        </div>
        <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
    </div>
    <div class="field-error" id="photoError"></div>
</div>

                           
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Employee ID <span class="text-muted fs-11">(HR reference)</span></label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-id-badge"></i></div>
        <input type="text" name="employee_id" id="employee_id" class="form-control" placeholder="e.g. EMP-0001">
    </div>
    <div class="field-error" id="employee_idError"></div>
</div>

                            {{-- Email --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger"></span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-mail"></i></div>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address">
                                </div>
                                <div class="field-error" id="emailError"></div>
                            </div>

                            {{-- Phone --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Mobile Number</label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-phone"></i></div>
                                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter mobile number">
                                </div>
                                <div class="field-error" id="phoneError"></div>
                            </div>

                            {{-- Employee Code (biometric device ID) --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Employee Code <span class="text-muted fs-11">(biometric ID)</span></label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-hash"></i></div>
        <input type="text" name="employee_code" id="employee_code" class="form-control" placeholder="e.g. SWS25">
    </div>
    <div class="field-error" id="employee_codeError"></div>
</div>
{{-- Agent No --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Agent No</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-user-check"></i></div>
        <input type="text" name="agent_no" id="agent_no" class="form-control" placeholder="e.g. AGT-1023">
    </div>
    <div class="field-error" id="agent_noError"></div>
</div>
                            {{-- Business Location --}}
{{-- <div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Business Location</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-map-pin"></i></div>
        <select name="business_location_id" id="business_location_id" class="form-control">
            <option value="">-- Select Location --</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}">
                    {{ $location->name }} ({{ $location->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="field-error" id="business_location_idError"></div>
</div> --}}

                            {{-- Role --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Assign Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-shield"></i></div>
                                    <select name="role" id="role" class="form-control">
                                        <option value="">-- Select Role --</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field-error" id="roleError"></div>
                            </div>
                            {{-- Department (Select2) --}}
{{-- Department (Select2) --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Department</label>
    <div class="d-flex align-items-center"
         style="border:1px solid #dee2e6;border-radius:6px;overflow:hidden;">
        <div class="input-group-text"
             style="border:none;border-right:1px solid #dee2e6;border-radius:0;background:#f8f9fa;">
            <i class="feather-briefcase"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <select name="department_id" id="department_id" style="width:100%;">
                <option value="">-- Select Department --</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}"
                        {{ isset($user) && $user->department_id == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field-error" id="department_idError"></div>
</div>
{{-- Date of Birth --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Date of Birth</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-gift"></i></div>
        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
               value="{{ isset($user) ? $user->date_of_birth?->format('Y-m-d') : '' }}">
    </div>
    <div class="field-error" id="date_of_birthError"></div>
</div>

{{-- Date of Joining --}}
<div class="col-lg-6 mb-4">
    <label class="form-label fw-semibold">Date of Joining</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-calendar"></i></div>
        <input type="date" name="date_of_joining" id="date_of_joining" class="form-control"
               value="{{ isset($user) ? $user->date_of_joining?->format('Y-m-d') : '' }}">
    </div>
    <div class="field-error" id="date_of_joiningError"></div>
</div>

                            {{-- Status --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-toggle-right"></i></div>
                                    <select name="status" id="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="field-error" id="statusError"></div>
                            </div>

                            {{-- Password --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-lock"></i></div>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password">
                                    <span class="input-group-text c-pointer" onclick="togglePass('password', 'eyeIcon1')">
                                        <i class="feather-eye" id="eyeIcon1"></i>
                                    </span>
                                </div>
                                <div class="field-error" id="passwordError"></div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-lock"></i></div>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm password">
                                    <span class="input-group-text c-pointer" onclick="togglePass('password_confirmation', 'eyeIcon2')">
                                        <i class="feather-eye" id="eyeIcon2"></i>
                                    </span>
                                </div>
                                <div class="field-error" id="password_confirmationError"></div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('users.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-user-plus me-2" id="btnIcon"></i>
                                <span id="btnText">Create Employee</span>
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

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    
.ajax-toast {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    min-width: 320px; max-width: 400px; border-radius: 12px;
    padding: 14px 18px; display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-weight: 500; box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ajax-toast.show { transform: translateX(0); }
.ajax-toast.toast-success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
.ajax-toast.toast-error   { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
.ajax-toast .toast-close  { margin-left: auto; background: none; border: none; cursor: pointer; font-size: 16px; color: inherit; opacity: 0.6; }
.field-error { font-size: 12px; color: #ef4444; margin-top: 4px; display: none; }
.field-error.show { display: block; }
.form-control.input-error { border-color: #ef4444 !important; }

/* Select2 match input-group height */
.select2-container--default .select2-selection--single {
    height: 38px !important;
    border: none !important;
    border-radius: 0 !important;
    display: flex !important;
    align-items: center !important;
    padding: 0 8px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 4px !important;
    color: #212529 !important;
    font-size: 13px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    top: 1px !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: #ccccda !important;
}
.select2-dropdown {
    border: 1px solid #dee2e6 !important;
    border-radius: 6px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,.1) !important;
}
.select2-container { width: 100% !important; }
</style>
@endpush

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#department_id').select2({
        placeholder: '-- Select Department --',
        allowClear: true,
        width: '100%',
    });
});
</script>
<script>
let toastTimer = null;

function showToast(message, type = 'success') {
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

document.getElementById('photo')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('photoPreview').src = e.target.result;
        document.getElementById('photoPreview').style.display = 'block';
        document.getElementById('photoPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(file);
});

function hideToast() {
    document.getElementById('ajaxToast').classList.remove('show');
}

function togglePass(fieldId, iconId) {
    const input = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('feather-eye');
    icon.classList.toggle('feather-eye-off');
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => {
        el.textContent = ''; el.classList.remove('show');
    });
    document.querySelectorAll('.form-control').forEach(el => {
        el.classList.remove('input-error');
    });
}

/* ── Live employee_code uniqueness check ── */
let codeCheckTimer = null;
const employeeCodeInput = document.getElementById('employee_code');

employeeCodeInput.addEventListener('input', function () {
    const code = this.value.trim();
    const errEl = document.getElementById('employee_codeError');

    clearTimeout(codeCheckTimer);
    employeeCodeInput.classList.remove('input-error');
    errEl.textContent = '';
    errEl.classList.remove('show');

    if (!code) return;

    codeCheckTimer = setTimeout(() => {
        fetch(`{{ route('users.checkEmployeeCode') }}?employee_code=${encodeURIComponent(code)}`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(res => res.json())
        .then(data => {
            if (!data.available) {
                employeeCodeInput.classList.add('input-error');
                errEl.textContent = 'This employee code is already taken.';
                errEl.classList.add('show');
            }
        })
        .catch(() => {}); // fail silently — server-side validation is the real guard
    }, 450); // debounce
});

$(function () {
    $('#department_id').select2({
        placeholder: '-- Select Department --',
        allowClear: true,
        width: '100%',
    });

    // Fix Select2 border/height to match other inputs
    $('.select2-container .select2-selection--single').css({
        'height':      '38px',
        'border':      'none',
        'border-radius': '0',
        'display':     'flex',
        'align-items': 'center',
        'padding':     '0 8px',
    });
    $('.select2-selection__arrow').css('height', '38px');
});
function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent = message; err.classList.add('show'); }
}

document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    if (document.getElementById('employee_code').classList.contains('input-error')) {
    showToast('Please fix the employee code before submitting.', 'error');
    return;
}
    
    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    // Loading state
    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Creating...';

   const formData = new FormData();
    formData.append('name', document.getElementById('name').value.trim());
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('employee_id', document.getElementById('employee_id').value.trim());
    formData.append('role', document.getElementById('role').value);
    formData.append('status', document.getElementById('status').value);
    formData.append('password', document.getElementById('password').value);
    formData.append('password_confirmation', document.getElementById('password_confirmation').value);
    formData.append('employee_code', document.getElementById('employee_code').value.trim());
    formData.append('department_id', document.getElementById('department_id').value || '');
    formData.append('date_of_birth', document.getElementById('date_of_birth').value || '');
    formData.append('date_of_joining', document.getElementById('date_of_joining').value || '');
    formData.append('agent_no', document.getElementById('agent_no').value.trim());
    const photoFile = document.getElementById('photo').files[0];
    if (photoFile) formData.append('photo', photoFile);

    fetch("{{ route('users.store') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnIcon.classList.remove('d-none');
        btnText.textContent = 'Create User';

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
        btnText.textContent = 'Create User';
        showToast('Something went wrong. Please try again.', 'error');
    });
});
</script>
@endpush