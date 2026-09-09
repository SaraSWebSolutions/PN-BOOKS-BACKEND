@extends('layouts.app')
@section('title', 'Website Content')

@section('content')
<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Website Content</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Website Content</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddPage">
            <i class="feather-plus me-1"></i> Add Page
        </button>
        <a href="{{ route('website.settings') }}" class="btn btn-outline-primary">
            <i class="feather-settings me-1"></i> Global Settings
        </a>
    </div>
</div>

<div class="main-content">

    {{-- Stat cards --}}
    <div class="row g-4 mb-2">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Total Pages</div>
                        <h4 class="mb-0">{{ $pages->count() }}</h4>
                    </div>
                    <div class="avatar-text bg-light-primary text-primary rounded-circle">
                        <i class="feather-grid"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Total Banners</div>
                        <h4 class="mb-0">{{ $pages->sum(fn($p) => $p->banners->count()) }}</h4>
                    </div>
                    <div class="avatar-text bg-light-success text-success rounded-circle">
                        <i class="feather-image"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted">Active Pages</div>
                        <h4 class="mb-0">{{ $pages->where('is_active', true)->count() }}</h4>
                    </div>
                    <div class="avatar-text bg-light-warning text-warning rounded-circle">
                        <i class="feather-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page cards --}}
    <div class="card stretch stretch-full">
        <div class="card-header">
            <h6 class="mb-0">All Pages</h6>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @forelse($pages as $page)
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-0">{{ $page->name_en }}</h6>
                                <small class="text-muted">{{ $page->name_ms }}</small>
                            </div>
                            <span class="badge {{ $page->is_active ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                {{ $page->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="text-muted small mb-3">
                            <i class="feather-image me-1"></i> {{ $page->banners->count() }} banner(s)
                            &nbsp;·&nbsp;
                            <i class="feather-layers me-1"></i> {{ $page->sections->count() }} section(s)
                        </div>

                        <a href="{{ route('website.show', $page->page_key) }}" class="btn btn-sm btn-primary mt-auto">
                            <i class="feather-edit-2 me-1"></i> Manage Content
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted py-5">
                    No pages found yet. Click <strong>Add Page</strong> above to create your first one.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════ Add Page Offcanvas ═══════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPage">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add New Page</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Page Key <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="new_page_key" placeholder="e.g. pi-readz">
            <div class="form-text">Lowercase, hyphens only — used in the page URL. Auto-formatted on save.</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Page Name (English) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="new_page_name_en" placeholder="e.g. PI Readz">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Page Name (Malay)</label>
            <input type="text" class="form-control" id="new_page_name_ms" placeholder="e.g. PI Readz">
        </div>
        <div id="addPageError" class="alert alert-danger d-none py-2 px-3 small"></div>
        <button type="button" class="btn btn-primary w-100" id="createPageBtn" onclick="createPage()">
            <i class="feather-save me-1"></i> Create Page
        </button>
    </div>
</div>
@endsection

@push('styles')
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}
</style>
@endpush

@push('scripts')
<script>
function createPage(){
    const keyInput   = document.getElementById('new_page_key');
    const nameEnInput = document.getElementById('new_page_name_en');
    const nameMsInput = document.getElementById('new_page_name_ms');
    const errorBox   = document.getElementById('addPageError');
    const btn        = document.getElementById('createPageBtn');

    errorBox.classList.add('d-none');

    if (!keyInput.value.trim() || !nameEnInput.value.trim()) {
        errorBox.textContent = 'Page Key and Page Name (English) are required.';
        errorBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="feather-loader me-1"></i> Creating…';

    fetch(`{{ route('website.pages.store') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            page_key: keyInput.value.trim(),
            name_en:  nameEnInput.value.trim(),
            name_ms:  nameMsInput.value.trim(),
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            location.reload();
        } else {
            errorBox.textContent = res.message || 'Something went wrong.';
            errorBox.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="feather-save me-1"></i> Create Page';
        }
    })
    .catch(() => {
        errorBox.textContent = 'Request failed. Please try again.';
        errorBox.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="feather-save me-1"></i> Create Page';
    });
}

function showToast(status, message){
    const toast = document.getElementById('ajaxToast');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMsg');
    toast.className = 'ajax-toast show ' + (status === 'success' ? 'toast-success' : 'toast-error');
    icon.innerHTML = status === 'success' ? '<i class="feather-check-circle"></i>' : '<i class="feather-alert-circle"></i>';
    msg.textContent = message;
    setTimeout(hideToast, 3500);
}
function hideToast(){
    document.getElementById('ajaxToast').classList.remove('show');
}
</script>
@endpush