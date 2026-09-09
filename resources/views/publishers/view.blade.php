@extends('layouts.app')

@section('title', 'Publisher Details')

@section('content')

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Publisher Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('publishers.index') }}">Publishers</a></li>
            <li class="breadcrumb-item">View</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex gap-2">
            @can('edit users')
            <a href="{{ route('publishers.edit', $publisher) }}" class="btn btn-primary">
                <i class="feather-edit-3 me-2"></i> Edit Publisher
            </a>
            @endcan
            <a href="{{ route('publishers.index') }}" class="btn btn-light-brand">
                <i class="feather-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <div class="col-lg-4">
            <div class="card stretch stretch-full">
                <div class="card-body text-center py-5">
                    <div class="avatar-image avatar-xxl bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:80px;height:80px;border-radius:50%;font-size:32px;font-weight:bold;overflow:hidden;">
                        @if($publisher->publisherProfile->logo ?? null)
                            <img src="{{ $publisher->publisherProfile->logo_url }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ strtoupper(substr($publisher->name, 0, 1)) }}
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $publisher->publisherProfile->company_name ?? $publisher->name }}</h5>
                    <p class="text-muted mb-2">{{ $publisher->email }}</p>
                    <span class="badge bg-soft-primary text-primary fs-12">Publisher</span>
                    <div class="mt-2">
                        @if($publisher->status === 'active')
                            <span class="badge bg-soft-success text-success">Active</span>
                        @else
                            <span class="badge bg-soft-danger text-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Publisher Information</h5>

                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Contact Login Name</div>
                        <div class="col-8">{{ $publisher->name }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Email</div>
                        <div class="col-8">{{ $publisher->email }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Phone</div>
                        <div class="col-8">{{ $publisher->phone ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Company Name</div>
                        <div class="col-8">{{ $publisher->publisherProfile->company_name ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">GST Number</div>
                        <div class="col-8">{{ $publisher->publisherProfile->gst_number ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">PAN / Tax ID</div>
                        <div class="col-8">{{ $publisher->publisherProfile->pan_number ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Contact Person</div>
                        <div class="col-8">{{ $publisher->publisherProfile->contact_person ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Contact Phone / Email</div>
                        <div class="col-8">
                            {{ $publisher->publisherProfile->contact_phone ?? '—' }}
                            @if($publisher->publisherProfile->contact_email ?? null)
                                / {{ $publisher->publisherProfile->contact_email }}
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Company Address</div>
                        <div class="col-8">{{ $publisher->publisherProfile->company_address ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Verification Status</div>
                        <div class="col-8">
                            @php $v = $publisher->publisherProfile->verification_status ?? 'pending'; @endphp
                            @if($v === 'verified')
                                <span class="badge bg-soft-success text-success text-capitalize">Verified</span>
                            @elseif($v === 'rejected')
                                <span class="badge bg-soft-danger text-danger text-capitalize">Rejected</span>
                            @else
                                <span class="badge bg-soft-warning text-warning text-capitalize">Pending</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Bank Details</div>
                        <div class="col-8">
                            @if($publisher->publisherProfile->bank_account_number ?? null)
                                {{ $publisher->publisherProfile->bank_account_name }} — {{ $publisher->publisherProfile->bank_account_number }} ({{ $publisher->publisherProfile->bank_ifsc }})
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Joined</div>
                        <div class="col-8">{{ $publisher->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection