@extends('layouts.app')
@section('title', 'Edit Contact')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Contact</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Contacts</a></li>
            <li class="breadcrumb-item">Edit</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('contacts.index') }}" class="btn btn-light-brand">
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
                        <h5 class="fw-bold mb-1">Contact Information</h5>
                        <span class="fs-12 text-muted">Update the details for this contact</span>
                    </div>

                    <form id="editContactForm" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="mb-4">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-3">
                                <i class="feather-user me-2"></i> Basic Information
                            </h6>
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-user"></i></div>
                                        <input type="text" name="name" id="name" class="form-control"
                                               placeholder="Enter full name" value="{{ old('name', $contact->name) }}">
                                    </div>
                                    <div class="field-error" id="nameError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Contact Code</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-hash"></i></div>
                                        <input type="text" name="code" id="code" class="form-control"
                                               value="{{ old('code', $contact->code) }}">
                                    </div>
                                    <div class="field-error" id="codeError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Contact Type <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-tag"></i></div>
                                        <select name="type" id="type" class="form-control">
                                            <option value="customer"  {{ old('type', $contact->type) === 'customer'  ? 'selected' : '' }}>Customer</option>
                                            <option value="lead"      {{ old('type', $contact->type) === 'lead'      ? 'selected' : '' }}>Lead</option>
                                            <option value="both"      {{ old('type', $contact->type) === 'both'      ? 'selected' : '' }}>Both (Customer + Lead)</option>
                                            <option value="supplier"  {{ old('type', $contact->type) === 'supplier'  ? 'selected' : '' }}>Supplier</option>
                                        </select>
                                    </div>
                                    <div class="field-error" id="typeError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Gender</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-users"></i></div>
                                        <select name="gender" id="gender" class="form-control">
                                            <option value="">-- Select Gender --</option>
                                            <option value="male"   {{ old('gender', $contact->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $contact->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other"  {{ old('gender', $contact->gender) === 'other'  ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                    <div class="field-error" id="genderError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Date of Birth</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-calendar"></i></div>
                                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                               value="{{ old('date_of_birth', $contact->date_of_birth ? \Carbon\Carbon::parse($contact->date_of_birth)->format('Y-m-d') : '') }}">
                                    </div>
                                    <div class="field-error" id="date_of_birthError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-toggle-right"></i></div>
                                        <select name="status" id="status" class="form-control">
                                            <option value="active"   {{ old('status', $contact->status) === 'active'   ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $contact->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="field-error" id="statusError"></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Contact Info --}}
                        <div class="mb-4">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-3">
                                <i class="feather-phone me-2"></i> Contact Details
                            </h6>
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-mail"></i></div>
                                        <input type="email" name="email" id="email" class="form-control"
                                               placeholder="Enter email" value="{{ old('email', $contact->email) }}">
                                    </div>
                                    <div class="field-error" id="emailError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-phone"></i></div>
                                        <input type="text" name="phone" id="phone" class="form-control"
                                               placeholder="Enter phone number" value="{{ old('phone', $contact->phone) }}">
                                    </div>
                                    <div class="field-error" id="phoneError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">WhatsApp Number</label>
                                    <div class="input-group">
                                        <div class="input-group-text" style="background:#25D366;border-color:#25D366;">
                                            <i class="bi bi-whatsapp text-white"></i>
                                        </div>
                                        <input type="text" name="whatsapp" id="whatsapp" class="form-control"
                                               placeholder="e.g. +91 99999 99999" value="{{ old('whatsapp', $contact->whatsapp) }}">
                                    </div>
                                    <div class="field-error" id="whatsappError"></div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <label class="form-label fw-semibold">Business Location</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-map-pin"></i></div>
                                        <select name="business_location_id" id="business_location_id" class="form-control">
                                            <option value="">-- Select Location --</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}"
                                                    {{ old('business_location_id', $contact->business_location_id) == $location->id ? 'selected' : '' }}>
                                                    {{ $location->name }} ({{ $location->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field-error" id="business_location_idError"></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Address --}}
                        <div class="mb-4">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-3">
                                <i class="feather-map me-2"></i> Address
                            </h6>
                            <div class="row">
                                <div class="col-lg-12 mb-4">
                                    <label class="form-label fw-semibold">Address</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-map-pin"></i></div>
                                        <textarea name="address" id="address" class="form-control" rows="2"
                                                  placeholder="Enter full address">{{ old('address', $contact->address) }}</textarea>
                                    </div>
                                    <div class="field-error" id="addressError"></div>
                                </div>

                                <div class="col-lg-3 mb-4">
                                    <label class="form-label fw-semibold">City</label>
                                    <input type="text" name="city" id="city" class="form-control"
                                           placeholder="City" value="{{ old('city', $contact->city) }}">
                                    <div class="field-error" id="cityError"></div>
                                </div>

                                <div class="col-lg-3 mb-4">
                                    <label class="form-label fw-semibold">State</label>
                                    <input type="text" name="state" id="state" class="form-control"
                                           placeholder="State" value="{{ old('state', $contact->state) }}">
                                    <div class="field-error" id="stateError"></div>
                                </div>

                                <div class="col-lg-3 mb-4">
                                    <label class="form-label fw-semibold">Pincode</label>
                                    <input type="text" name="pincode" id="pincode" class="form-control"
                                           placeholder="Pincode" value="{{ old('pincode', $contact->pincode) }}">
                                    <div class="field-error" id="pincodeError"></div>
                                </div>

                                <div class="col-lg-3 mb-4">
                                    <label class="form-label fw-semibold">Country</label>
                                    <input type="text" name="country" id="country" class="form-control"
                                           value="{{ old('country', $contact->country ?? 'India') }}">
                                    <div class="field-error" id="countryError"></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Financial --}}
                        <div class="mb-4">
                            <h6 class="fw-semibold text-uppercase fs-11 text-muted mb-3">
                                <i class="feather-dollar-sign me-2"></i> Financial Details
                            </h6>
                            <div class="row">
                                <div class="col-lg-4 mb-4">
                                    <label class="form-label fw-semibold">GSTIN</label>
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-file-text"></i></div>
                                        <input type="text" name="gstin" id="gstin" class="form-control"
                                               placeholder="GST Number" value="{{ old('gstin', $contact->gstin) }}">
                                    </div>
                                    <div class="field-error" id="gstinError"></div>
                                </div>

                                {{-- KYC Details --}}
<div class="col-lg-4 mb-4">
    <label class="form-label fw-semibold">Aadhaar Number</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-credit-card"></i></div>
        <input type="text" name="aadhaar_number" id="aadhaar_number"
               class="form-control" placeholder="12-digit Aadhaar"
               maxlength="12"
               value="{{ old('aadhaar_number', $contact->aadhaar_number ?? '') }}">
    </div>
    <div class="field-error" id="aadhaar_numberError"></div>
</div>

<div class="col-lg-4 mb-4">
    <label class="form-label fw-semibold">PAN Number</label>
    <div class="input-group">
        <div class="input-group-text"><i class="feather-file-text"></i></div>
        <input type="text" name="pan_number" id="pan_number"
               class="form-control" placeholder="e.g. ABCDE1234F"
               maxlength="10"
               style="text-transform:uppercase"
               value="{{ old('pan_number', $contact->pan_number ?? '') }}">
    </div>
    <div class="field-error" id="pan_numberError"></div>
</div>
                                <div class="col-lg-4 mb-4">
                                    <label class="form-label fw-semibold">Credit Limit</label>
                                    <div class="input-group">
                                        <div class="input-group-text">₹</div>
                                        <input type="number" name="credit_limit" id="credit_limit" class="form-control"
                                               placeholder="0.00" value="{{ old('credit_limit', $contact->credit_limit ?? 0) }}">
                                    </div>
                                    <div class="field-error" id="credit_limitError"></div>
                                </div>

                                <div class="col-lg-4 mb-4">
                                    <label class="form-label fw-semibold">Opening Balance</label>
                                    <div class="input-group">
                                        <div class="input-group-text">₹</div>
                                        <input type="number" name="opening_balance" id="opening_balance" class="form-control"
                                               placeholder="0.00" value="{{ old('opening_balance', $contact->opening_balance ?? 0) }}">
                                    </div>
                                    <div class="field-error" id="opening_balanceError"></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Notes --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"
                                      placeholder="Any additional notes...">{{ old('notes', $contact->notes) }}</textarea>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('contacts.index') }}" class="btn btn-light-brand">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="feather-save me-2" id="btnIcon"></i>
                                <span id="btnText">Update Contact</span>
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

function showToast(message, type = 'success') {
    const toast   = document.getElementById('ajaxToast');
    const msgEl   = document.getElementById('toastMessage');
    const iconEl  = document.getElementById('toastIcon');
    msgEl.textContent  = message;
    toast.className    = 'ajax-toast';
    toast.classList.add(type === 'success' ? 'toast-success' : 'toast-error');
    iconEl.textContent = type === 'success' ? '✅' : '❌';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
}

function hideToast() { document.getElementById('ajaxToast').classList.remove('show'); }

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}

function showFieldError(fieldId, errorId, message) {
    document.getElementById(fieldId)?.classList.add('input-error');
    const err = document.getElementById(errorId);
    if (err) { err.textContent = message; err.classList.add('show'); }
}

document.getElementById('editContactForm').addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnIcon.classList.add('d-none');
    btnText.textContent = 'Saving...';

    const formData = {
        name:                 document.getElementById('name').value.trim(),
        code:                 document.getElementById('code').value.trim(),
        type:                 document.getElementById('type').value,
        gender:               document.getElementById('gender').value,
        date_of_birth:        document.getElementById('date_of_birth').value,
        status:               document.getElementById('status').value,
        email:                document.getElementById('email').value.trim(),
        phone:                document.getElementById('phone').value.trim(),
        whatsapp:             document.getElementById('whatsapp').value.trim(),
        business_location_id: document.getElementById('business_location_id').value,
        address:              document.getElementById('address').value.trim(),
        city:                 document.getElementById('city').value.trim(),
        state:                document.getElementById('state').value.trim(),
        pincode:              document.getElementById('pincode').value.trim(),
        country:              document.getElementById('country').value.trim(),
        gstin:                document.getElementById('gstin').value.trim(),
        credit_limit:         document.getElementById('credit_limit').value,
        opening_balance:      document.getElementById('opening_balance').value,
        notes:                document.getElementById('notes').value.trim(),
        _token:               document.querySelector('input[name="_token"]').value,
         aadhaar_number: document.getElementById('aadhaar_number').value.trim(),
pan_number:     document.getElementById('pan_number').value.toUpperCase().trim(),
        _method:              'PUT',
    };

    fetch("{{ route('contacts.update', $contact) }}", {
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
        btnText.textContent = 'Update Contact';

        if (data.status === 'success') {
    showToast(data.message, 'success');
    setTimeout(() => window.location.href = "{{ route('contacts.index') }}", 1500);
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
        btnText.textContent = 'Update Contact';
        showToast('Something went wrong.', 'error');
    });
});
</script>
@endpush