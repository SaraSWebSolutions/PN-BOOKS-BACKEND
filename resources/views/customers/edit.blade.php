@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Customer</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
            <li class="breadcrumb-item">Edit</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('customers.index') }}" class="btn btn-light-brand">
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
                        <h5 class="fw-bold mb-1">Edit Customer: {{ $customer->name }}</h5>
                        <span class="fs-12 text-muted">Update customer account and address details</span>
                    </div>

                    <form id="editCustomerForm" novalidate>
                        @csrf
                        @method('PUT')

                        <h6 class="fw-semibold mb-3 text-primary">Account Details</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ $customer->name }}">
                                <div class="field-error" id="nameError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Photo</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:56px;height:56px;border-radius:50%;overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e2e8f0;">
                                        <img id="photoPreview" src="{{ $customer->photo_url ?? '' }}" style="width:100%;height:100%;object-fit:cover;{{ $customer->photo ? '' : 'display:none;' }}">
                                        <i class="feather-user" id="photoPlaceholder" style="color:#94a3b8;font-size:20px;{{ $customer->photo ? 'display:none;' : '' }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="photo" id="photo" class="form-control mb-1" accept="image/jpeg,image/png,image/webp">
                                        @if($customer->photo)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                                            <label class="form-check-label fs-12 text-danger" for="remove_photo">Remove current photo</label>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="field-error" id="photoError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ $customer->email }}">
                                <div class="field-error" id="emailError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Mobile Number</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ $customer->phone }}">
                                <div class="field-error" id="phoneError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Alternate Phone</label>
                                <input type="text" name="alternate_phone" id="alternate_phone" class="form-control" value="{{ $customer->customerProfile->alternate_phone ?? '' }}">
                                <div class="field-error" id="alternate_phoneError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active"   {{ $customer->status === 'active'   ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $customer->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <div class="field-error" id="statusError"></div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-semibold mb-3 text-primary">Personal Details</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                       value="{{ $customer->customerProfile->date_of_birth?->format('Y-m-d') }}">
                                <div class="field-error" id="date_of_birthError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Gender</label>
                                <select name="gender" id="gender" class="form-control">
                                    <option value="">-- Select --</option>
                                    @php $g = $customer->customerProfile->gender ?? ''; @endphp
                                    <option value="male"   {{ $g === 'male'   ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $g === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other"  {{ $g === 'other'  ? 'selected' : '' }}>Other</option>
                                </select>
                                <div class="field-error" id="genderError"></div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="newsletter_subscribed" name="newsletter_subscribed" value="1"
                                        {{ ($customer->customerProfile->newsletter_subscribed ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="newsletter_subscribed">Subscribed to newsletter</label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-semibold mb-3 text-primary">Address</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Address Line 1</label>
                                <input type="text" name="address_line1" id="address_line1" class="form-control" value="{{ $customer->customerProfile->address_line1 ?? '' }}">
                                <div class="field-error" id="address_line1Error"></div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <label class="form-label fw-semibold">Address Line 2</label>
                                <input type="text" name="address_line2" id="address_line2" class="form-control" value="{{ $customer->customerProfile->address_line2 ?? '' }}">
                                <div class="field-error" id="address_line2Error"></div>
                            </div>
                            <div class="col-lg-3 mb-4">
                                <label class="form-label fw-semibold">City</label>
                                <input type="text" name="city" id="city" class="form-control" value="{{ $customer->customerProfile->city ?? '' }}">
                                <div class="field-error" id="cityError"></div>
                            </div>
                            <div class="col-lg-3 mb-4">
                                <label class="form-label fw-semibold">State</label>
                                <input type="text" name="state" id="state" class="form-control" value="{{ $customer->customerProfile->state ?? '' }}">
                                <div class="field-error" id="stateError"></div>
                            </div>
                            <div class="col-lg-3 mb-4">
                                <label class="form-label fw-semibold">Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ $customer->customerProfile->postal_code ?? '' }}">
                                <div class="field-error" id="postal_codeError"></div>
                            </div>
                            <div class="col-lg-3 mb-4">
                                <label class="form-label fw-semibold">Country</label>
                                <input type="text" name="country" id="country" class="form-control" value="{{ $customer->customerProfile->country ?? '' }}">
                                <div class="field-error" id="countryError"></div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('customers.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-save me-2" id="btnIcon"></i>
                                <span id="btnText">Update Customer</span>
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

document.getElementById('photo')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('photoPreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.getElementById('photoPlaceholder').style.display = 'none';
        const removeBox = document.getElementById('remove_photo');
        if (removeBox) removeBox.checked = false;
    };
    reader.readAsDataURL(file);
});

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}

function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent = message; err.classList.add('show'); }
}

document.getElementById('editCustomerForm').addEventListener('submit', function(e) {
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

    const formData = new FormData();
    formData.append('name', document.getElementById('name').value.trim());
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('alternate_phone', document.getElementById('alternate_phone').value.trim());
    formData.append('status', document.getElementById('status').value);
    formData.append('date_of_birth', document.getElementById('date_of_birth').value || '');
    formData.append('gender', document.getElementById('gender').value);
    formData.append('address_line1', document.getElementById('address_line1').value.trim());
    formData.append('address_line2', document.getElementById('address_line2').value.trim());
    formData.append('city', document.getElementById('city').value.trim());
    formData.append('state', document.getElementById('state').value.trim());
    formData.append('postal_code', document.getElementById('postal_code').value.trim());
    formData.append('country', document.getElementById('country').value.trim());
    formData.append('newsletter_subscribed', document.getElementById('newsletter_subscribed').checked ? '1' : '0');

    const photoFile = document.getElementById('photo').files[0];
    if (photoFile) formData.append('photo', photoFile);

    const removeBox = document.getElementById('remove_photo');
    if (removeBox && removeBox.checked) formData.append('remove_photo', '1');

    formData.append('_method', 'PUT');
    formData.append('_token', document.querySelector('input[name="_token"]').value);

    fetch("{{ route('customers.update', $customer) }}", {
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
        btnText.textContent = 'Update Customer';

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
        btnText.textContent = 'Update Customer';
        showToast('Something went wrong. Please try again.', 'error');
    });
});
</script>
@endpush