@extends('layouts.app')
@section('title', 'Global Settings')

@section('content')
<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Global Settings</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('website.index') }}">Website Content</a></li>
            <li class="breadcrumb-item">Settings</li>
        </ul>
    </div>
</div>

<div class="main-content">
    <form id="settingsForm">
        @csrf

        @forelse($settings as $group => $items)
        <div class="card stretch mb-4">
            <div class="card-header">
                <h6 class="mb-0 text-capitalize">{{ $group }} Settings</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($items as $setting)
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-capitalize">
                            {{ str_replace('_', ' ', $setting->key) }}
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                @if($setting->type === 'textarea')
                                    <textarea class="form-control" rows="2"
                                        name="settings[{{ $setting->key }}][value_en]"
                                        placeholder="English">{{ old("settings.{$setting->key}.value_en", $setting->value_en) }}</textarea>
                                @else
                                    <input type="text" class="form-control"
                                        name="settings[{{ $setting->key }}][value_en]"
                                        placeholder="English"
                                        value="{{ old("settings.{$setting->key}.value_en", $setting->value_en) }}">
                                @endif
                                <small class="text-muted">English</small>
                            </div>
                            <div class="col-6">
                                @if($setting->type === 'textarea')
                                    <textarea class="form-control" rows="2"
                                        name="settings[{{ $setting->key }}][value_ms]"
                                        placeholder="Malay">{{ old("settings.{$setting->key}.value_ms", $setting->value_ms) }}</textarea>
                                @else
                                    <input type="text" class="form-control"
                                        name="settings[{{ $setting->key }}][value_ms]"
                                        placeholder="Malay"
                                        value="{{ old("settings.{$setting->key}.value_ms", $setting->value_ms) }}">
                                @endif
                                <small class="text-muted">Malay</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="card stretch">
            <div class="card-body text-center text-muted py-5">
                No settings found yet. Add rows to the <code>website_settings</code> table
                (e.g. <code>site_name</code>, <code>footer_text</code>, <code>contact_email</code>) to see them here.
            </div>
        </div>
        @endforelse

        {{-- Sticky save bar --}}
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary px-4" onclick="saveSettings()">
                <i class="feather-save me-1"></i> Save Settings
            </button>
        </div>
    </form>
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
function saveSettings(){
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);

    // FormData -> nested JSON, since storeSettings() expects a plain JSON body
    const settings = {};
    for (const [name, value] of formData.entries()) {
        const match = name.match(/^settings\[(.+?)\]\[(.+?)\]$/);
        if (match) {
            const [, key, field] = match;
            settings[key] = settings[key] || {};
            settings[key][field] = value;
        }
    }

    fetch(`{{ route('website.settings.store') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ settings })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.status, res.message);
    })
    .catch(() => showToast('error', 'Something went wrong'));
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