@extends('layouts.app')
@section('title', 'Permissions')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMessage"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Permissions</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Permissions</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('permissions.create') }}" class="btn btn-primary">
            <i class="feather-plus me-2"></i> Add Permission
        </a>
    </div>
</div>

<div class="main-content">
    <div class="row">
        @forelse($permissions as $module => $perms)
        <div class="col-lg-4 mb-4">
            <div class="card stretch stretch-full">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-semibold text-capitalize mb-0">
                        <i class="feather-key me-2 text-primary"></i> {{ $module }}
                    </h6>
                    <span class="badge bg-soft-primary text-primary">{{ $perms->count() }}</span>
                </div>
                <div class="card-body py-2">
                    @foreach($perms as $perm)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div>
                            <span class="fw-semibold text-capitalize">{{ explode(' ', $perm->name)[0] }}</span>
                            <span class="fs-12 text-muted d-block">{{ $perm->name }}</span>
                        </div>
                        <div class="hstack gap-2">
                            <span class="badge bg-soft-info text-info">
                                {{ $perm->roles->count() }} roles
                            </span>
                             <a href="{{ route('permissions.edit', $perm) }}" title="Edit">
        <i class="feather feather-edit-3"></i>
    </a>
                            <a href="javascript:void(0);" class="text-danger"
                               onclick="deletePermission({{ $perm->id }})" title="Delete">
                                <i class="feather feather-trash-2"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="feather-key fs-30 d-block mb-2"></i>
                    No permissions found.
                </div>
            </div>
        </div>
        @endforelse
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

function deletePermission(id) {
    if (!confirm('Delete this permission? This will remove it from all roles!')) return;
    fetch(`/permissions/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Something went wrong!', 'error'));
}
</script>
@endpush