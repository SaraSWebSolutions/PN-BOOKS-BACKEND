@extends('layouts.app')

@section('title', 'View User')

@section('content')

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">User Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
            <li class="breadcrumb-item">View</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex gap-2">
            @can('edit users')
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                <i class="feather-edit-3 me-2"></i> Edit User
            </a>
            @endcan
            <a href="{{ route('users.index') }}" class="btn btn-light-brand">
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
    @if($user->photo)
        <img src="{{ $user->photo_url }}" style="width:100%;height:100%;object-fit:cover;">
    @else
        {{ strtoupper(substr($user->name, 0, 1)) }}
    @endif
</div>
                    <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @foreach($user->roles as $role)
                        <span class="badge bg-soft-primary text-primary text-capitalize fs-12">{{ $role->name }}</span>
                    @endforeach
                    <div class="mt-2">
                        @if($user->status === 'active')
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
                    <h5 class="fw-bold mb-4">User Information</h5>

                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Full Name</div>
                        <div class="col-8">{{ $user->name }}</div>
                    </div>
                    
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Email</div>
                        <div class="col-8">{{ $user->email }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Phone</div>
                        <div class="col-8">{{ $user->phone ?? '—' }}</div>
                    </div>
                    <hr>
                  <div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Employee ID</div>
    <div class="col-8">
        @if($user->employee_id)
            <span class="badge"
                  style="background:rgba(99,102,241,.1);color:#6366f1;
                         border:1px solid rgba(99,102,241,.2);font-size:12px;">
                <i class="feather-hash me-1"></i>{{ $user->employee_id }}
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </div>
    
</div>
<hr>
<div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Agent No</div>
    <div class="col-8">
        @if($user->agent_no)
            <span class="badge" style="background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.2);font-size:12px;">
                <i class="feather-user-check me-1"></i>{{ $user->agent_no }}
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </div>
</div>
<hr>
<div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Department</div>
    <div class="col-8">
        @if($user->department)
            <span class="badge"
                  style="background:rgba(99,102,241,.1);color:#6366f1;
                         border:1px solid rgba(99,102,241,.2);font-size:12px;">
                <i class="feather-briefcase me-1"></i>
                {{ $user->department->name }}
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </div>
</div>
<hr>
<div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Date of Birth</div>
    <div class="col-8">
        {{ $user->date_of_birth ? $user->date_of_birth->format('d M Y') : '—' }}
    </div>
</div>
<hr>
<div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Date of Joining</div>
    <div class="col-8">
        {{ $user->date_of_joining ? $user->date_of_joining->format('d M Y') : '—' }}
    </div>
</div>
{{-- <div class="row mb-3">
    <div class="col-4 text-muted fw-semibold">Business Location</div>
    <div class="col-8">
        @if($user->businessLocation)
            <span class="badge bg-soft-info text-info">
                <i class="feather-map-pin me-1"></i>
                {{ $user->businessLocation->name }}
                ({{ $user->businessLocation->code }})
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </div>
</div> --}}
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Role</div>
                        <div class="col-8">
                            @foreach($user->roles as $role)
                                <span class="badge bg-soft-primary text-primary text-capitalize">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Status</div>
                        <div class="col-8">
                            @if($user->status === 'active')
                                <span class="badge bg-soft-success text-success">Active</span>
                            @else
                                <span class="badge bg-soft-danger text-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-4 text-muted fw-semibold">Created At</div>
                        <div class="col-8">{{ $user->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection