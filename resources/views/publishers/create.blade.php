@extends('layouts.app')

@section('title', 'Add Publisher')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Add Publisher</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('publishers.index') }}">Publishers</a></li>
            <li class="breadcrumb-item">Create</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('publishers.index') }}" class="btn btn-light-brand">
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
                        <h5 class="fw-bold mb-1">Publisher Information</h5>
                        <span class="fs-12 text-muted">Fill in the details to add a new publisher</span>
                    </div>

                    <form id="createPublisherForm" novalidate>
                        @csrf

                        <h6 class="fw-semibold mb-3 text-primary">Account Details</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Contact Login Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-user"></i></div>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter full name">
                                </div>
                                <div class="field-error" id="nameError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Company Logo</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:56px;height:56px;border-radius:50%;overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e2e8f0;">
                                        <img id="photoPreview" src="" style="width:100%;height:100%;object-fit:cover;display:none;">
                                        <i class="feather-briefcase" id="photoPlaceholder" style="color:#94a3b8;font-size:20px;"></i>
                                    </div>
                                    <input type="file" name="logo" id="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
                                </div>
                                <div class="field-error" id="logoError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Login Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-mail"></i></div>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address">
                                </div>
                                <div class="field-error" id="emailError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Mobile Number</label>
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-phone"></i></div>
                                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter mobile number">
                                </div>
                                <div class="field-error" id="phoneError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div class="field-error" id="statusError"></div>
                            </div>

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
                        <h6 class="fw-semibold mb-3 text-primary">Company Details</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" id="company_name" class="form-control" placeholder="e.g. Star Publications Pte Ltd">
                                <div class="field-error" id="company_nameError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">GST Number</label>
                                <input type="text" name="gst_number" id="gst_number" class="form-control" placeholder="e.g. 200912345A">
                                <div class="field-error" id="gst_numberError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">PAN / Tax ID</label>
                                <input type="text" name="pan_number" id="pan_number" class="form-control">
                                <div class="field-error" id="pan_numberError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Verification Status</label>
                                <select name="verification_status" id="verification_status" class="form-control">
                                    <option value="pending">Pending</option>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <div class="field-error" id="verification_statusError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Contact Person</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control">
                                <div class="field-error" id="contact_personError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Contact Phone</label>
                                <input type="text" name="contact_phone" id="contact_phone" class="form-control">
                                <div class="field-error" id="contact_phoneError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Contact Email</label>
                                <input type="email" name="contact_email" id="contact_email" class="form-control">
                                <div class="field-error" id="contact_emailError"></div>
                            </div>

                            <div class="col-lg-12 mb-4">
                                <label class="form-label fw-semibold">Company Address</label>
                                <textarea name="company_address" id="company_address" class="form-control" rows="2"></textarea>
                                <div class="field-error" id="company_addressError"></div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-semibold mb-3 text-primary">Payout Details (optional)</h6>
                        <div class="row">
                            <div class="col-lg-4 mb-4">
                                <label class="form-label fw-semibold">Bank Account Name</label>
                                <input type="text" name="bank_account_name" id="bank_account_name" class="form-control">
                                <div class="field-error" id="bank_account_nameError"></div>
                            </div>
                            <div class="col-lg-4 mb-4">
                                <label class="form-label fw-semibold">Bank Account Number</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" class="form-control">
                                <div class="field-error" id="bank_account_numberError"></div>
                            </div>
                            <div class="col-lg-4 mb-4">
                                <label class="form-label fw-semibold">Bank IFSC / SWIFT</label>
                                <input type="text" name="bank_ifsc" id="bank_ifsc" class="form-control">
                                <div class="field-error" id="bank_ifscError"></div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('publishers.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-user-plus me-2" id="btnIcon"></i>
                                <span id="btnText">Create Publisher</span>
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
</style>
@endpush

@push('scripts')
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
function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }

document.getElementById('logo')?.addEventListener('change', function () {
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

function togglePass(fieldId, iconId) {
    const input = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('feather-eye');
    icon.classList.toggle('feather-eye-off');
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}

function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent = message; err.classList.add('show'); }
}

document.getElementById('createPublisherForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Creating...';

    const formData = new FormData();
    formData.append('name', document.getElementById('name').value.trim());
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('status', document.getElementById('status').value);
    formData.append('password', document.getElementById('password').value);
    formData.append('password_confirmation', document.getElementById('password_confirmation').value);
    formData.append('company_name', document.getElementById('company_name').value.trim());
    formData.append('gst_number', document.getElementById('gst_number').value.trim());
    formData.append('pan_number', document.getElementById('pan_number').value.trim());
    formData.append('verification_status', document.getElementById('verification_status').value);
    formData.append('contact_person', document.getElementById('contact_person').value.trim());
    formData.append('contact_phone', document.getElementById('contact_phone').value.trim());
    formData.append('contact_email', document.getElementById('contact_email').value.trim());
    formData.append('company_address', document.getElementById('company_address').value.trim());
    formData.append('bank_account_name', document.getElementById('bank_account_name').value.trim());
    formData.append('bank_account_number', document.getElementById('bank_account_number').value.trim());
    formData.append('bank_ifsc', document.getElementById('bank_ifsc').value.trim());

    const logoFile = document.getElementById('logo').files[0];
    if (logoFile) formData.append('logo', logoFile);

    fetch("{{ route('publishers.store') }}", {
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
        btnText.textContent = 'Create Publisher';

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
        btnText.textContent = 'Create Publisher';
        showToast('Something went wrong. Please try again.', 'error');
    });
});
</script>
@endpush