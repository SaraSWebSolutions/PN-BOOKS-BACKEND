@extends('layouts.app')

@section('title', 'Customer Details')

@section('content')

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Customer Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
            <li class="breadcrumb-item">{{ $customer->name }}</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        @can('edit users')
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm">
            <i class="feather feather-edit-3 me-1"></i> Edit Customer
        </a>
        @endcan
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="feather feather-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="main-content">
    <div class="row">

        {{-- Left: Profile card --}}
        <div class="col-lg-4">
            <div class="card stretch stretch-full">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white"
                         style="width:90px;height:90px;border-radius:50%;font-size:32px;font-weight:bold;overflow:hidden;">
                        @if($customer->customerProfile?->profile_photo)
                            <img src="{{ $customer->customerProfile->profile_photo_url }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        @endif
                    </div>

                    <h5 class="mb-1">{{ $customer->name }}</h5>
                    <p class="text-muted mb-3">{{ $customer->email }}</p>

                    <div id="statusWrapper">
                        @if($customer->status === 'active')
                            <span class="badge bg-soft-success text-success px-3 py-2" id="statusBadge">Active</span>
                        @else
                            <span class="badge bg-soft-danger text-danger px-3 py-2" id="statusBadge">Inactive</span>
                        @endif
                    </div>

                    @can('edit users')
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm {{ $customer->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}" id="toggleStatusBtn" data-id="{{ $customer->id }}">
                            @if($customer->status === 'active')
                                <i class="feather feather-slash me-1"></i> Deactivate Customer
                            @else
                                <i class="feather feather-check me-1"></i> Activate Customer
                            @endif
                        </button>
                    </div>
                    @endcan

                    <hr class="my-4">

                    <div class="text-start">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fs-12">Phone</span>
                            <span class="fw-semibold">{{ $customer->phone ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fs-12">Loyalty Points</span>
                            <span class="fw-semibold">{{ $customer->customerProfile->loyalty_points ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fs-12">Newsletter</span>
                            <span class="fw-semibold">
                                {{ $customer->customerProfile?->newsletter_subscribed ? 'Subscribed' : 'Not subscribed' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted fs-12">Joined On</span>
                            <span class="fw-semibold">{{ $customer->created_at->format('d M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Personal & address info --}}
        <div class="col-lg-8">
            <div class="card stretch stretch-full mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Date of Birth</label>
                            <span class="fw-semibold">
                                {{ optional($customer->customerProfile?->date_of_birth)->format('d M, Y') ?? '—' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Gender</label>
                            <span class="fw-semibold">{{ ucfirst($customer->customerProfile->gender ?? '—') }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Alternate Phone</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->alternate_phone ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h6 class="mb-0">Address</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Address Line 1</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->address_line1 ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Address Line 2</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->address_line2 ?? '—' }}</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-12 d-block">City</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->city ?? '—' }}</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-12 d-block">State</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->state ?? '—' }}</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-12 d-block">Postal Code</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->postal_code ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Country</label>
                            <span class="fw-semibold">
                                {{ $customer->customerProfile->country?->name ?? $customer->customerProfile->country ?? '—' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-12 d-block">Preferred Language</label>
                            <span class="fw-semibold">{{ $customer->customerProfile->language?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('toggleStatusBtn')?.addEventListener('click', function () {
    const btn = this;
    const id  = btn.dataset.id;
    btn.disabled = true;

    fetch(`/customers/${id}/toggle-status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const isActive = data.new_status === 'active';
            const badge = document.getElementById('statusBadge');
            badge.className = isActive ? 'badge bg-soft-success text-success px-3 py-2' : 'badge bg-soft-danger text-danger px-3 py-2';
            badge.textContent = isActive ? 'Active' : 'Inactive';

            btn.className = isActive ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-success';
            btn.innerHTML = isActive
                ? '<i class="feather feather-slash me-1"></i> Deactivate Customer'
                : '<i class="feather feather-check me-1"></i> Activate Customer';
        }
        alert(data.message);
    })
    .catch(() => alert('Something went wrong!'))
    .finally(() => { btn.disabled = false; });
});
</script>
@endpush