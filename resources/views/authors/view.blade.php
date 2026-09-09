@extends('layouts.app')

@section('title', 'Author Details')

@section('content')

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Author Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('authors.index') }}">Authors</a></li>
            <li class="breadcrumb-item">View</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex gap-2">
            @can('edit users')
            <a href="{{ route('authors.edit', $author) }}" class="btn btn-primary">
                <i class="feather-edit-3 me-2"></i> Edit Author
            </a>
            @endcan
            <a href="{{ route('authors.index') }}" class="btn btn-light-brand">
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
                        @if($author->authorProfile->profile_photo_url ?? null)
                            <img src="{{ $author->authorProfile->profile_photo_url }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ strtoupper(substr($author->name, 0, 1)) }}
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $author->authorProfile->pen_name ?? $author->name }}</h5>
                    <p class="text-muted mb-2">{{ $author->email }}</p>
                    <span class="badge bg-soft-primary text-primary fs-12">Author</span>
                    <div class="mt-2">
                        @if($author->status === 'active')
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
                    <h5 class="fw-bold mb-4">Author Information</h5>

                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Full Name</div>
                        <div class="col-8">{{ $author->name }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Email</div>
                        <div class="col-8">{{ $author->email }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Phone</div>
                        <div class="col-8">{{ $author->phone ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Pen Name</div>
                        <div class="col-8">{{ $author->authorProfile->pen_name ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Website</div>
                        <div class="col-8">
                            @if($author->authorProfile->website ?? null)
                                <a href="{{ $author->authorProfile->website }}" target="_blank">{{ $author->authorProfile->website }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Bio</div>
                        <div class="col-8">{{ $author->authorProfile->bio ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Social Links</div>
                        <div class="col-8">{{ $author->authorProfile->social_links ?? '—' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Verification Status</div>
                        <div class="col-8">
                            @php $v = $author->authorProfile->verification_status ?? 'pending'; @endphp
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
                        <div class="col-4 text-muted fw-semibold">Commission Rate</div>
                        <div class="col-8">{{ $author->authorProfile->commission_rate ?? '—' }}{{ ($author->authorProfile->commission_rate ?? null) ? '%' : '' }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Bank Details</div>
                        <div class="col-8">
                            @if($author->authorProfile->bank_account_number ?? null)
                                {{ $author->authorProfile->bank_account_name }} — {{ $author->authorProfile->bank_account_number }} ({{ $author->authorProfile->bank_ifsc }})
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Joined</div>
                        <div class="col-8">{{ $author->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection