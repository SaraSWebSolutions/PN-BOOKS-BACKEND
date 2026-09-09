@extends('layouts.app')
@section('title', 'View Role')

@section('content')

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Role Details</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item">View</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex gap-2">
            <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary">
                <i class="feather-edit-3 me-2"></i> Edit Role
            </a>
            <a href="{{ route('roles.index') }}" class="btn btn-light-brand">
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
                    <div class="avatar-text bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:80px;height:80px;border-radius:16px;font-size:32px;font-weight:bold;">
                        {{ strtoupper(substr($role->name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-1 text-capitalize">{{ $role->name }}</h5>
                    <p class="text-muted mb-3">{{ $role->guard_name }} guard</p>
                    <div class="hstack gap-2 justify-content-center">
                        <span class="badge bg-soft-primary text-primary">
                            {{ $role->permissions->count() }} Permissions
                        </span>
                        <span class="badge bg-soft-info text-info">
                            {{ $users->count() }} Users
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Permissions --}}
            <div class="card stretch stretch-full mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Assigned Permissions</h5>
                    @php
                        $grouped = $role->permissions->groupBy(function($perm) {
                            return explode(' ', $perm->name)[1] ?? 'other';
                        });
                    @endphp
                    @forelse($grouped as $module => $perms)
                    <div class="mb-3">
                        <h6 class="fw-semibold text-capitalize text-muted mb-2">{{ $module }}</h6>
                        <div class="hstack gap-2 flex-wrap">
                            @foreach($perms as $perm)
                            <span class="badge bg-soft-success text-success text-capitalize">
                                {{ explode(' ', $perm->name)[0] }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No permissions assigned.</p>
                    @endforelse
                </div>
            </div>

            {{-- Users --}}
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Users with this Role</h5>
                    @forelse($users as $user)
                    <div class="hstack gap-3 mb-3">
                        <div class="avatar-text bg-primary text-white d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;border-radius:50%;font-weight:bold;flex-shrink:0;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <span class="fw-semibold d-block">{{ $user->name }}</span>
                            <span class="fs-12 text-muted">{{ $user->email }}</span>
                        </div>
                        <div class="ms-auto">
                            @if($user->status === 'active')
                                <span class="badge bg-soft-success text-success">Active</span>
                            @else
                                <span class="badge bg-soft-danger text-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No users assigned to this role.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

@endsection