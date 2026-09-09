@extends('layouts.app')
@section('title', $page->name_en . ' — Website Content')

@section('content')
<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">{{ $page->name_en }} — Banners & Sections</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('website.index') }}">Website Content</a></li>
            <li class="breadcrumb-item">{{ $page->name_en }}</li>
        </ul>
    </div>
</div>

<div class="main-content">

    {{-- ══════════════════════════ Banners ══════════════════════════ --}}
    <div class="card stretch mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Hero Banners</h6>
            <button type="button" class="btn btn-sm btn-primary" onclick="showBannerForm()">
                <i class="feather-plus"></i> Add Banner
            </button>
        </div>
        <div class="card-body">

            <div id="bannerList">
                @forelse($page->banners as $banner)
                @php
                    $bannerJson = json_encode($banner->only([
                        'id','title_en','title_ms','subtitle_en','subtitle_ms',
                        'button_text_en','button_text_ms','button_url','sort_order','is_active'
                    ]));
                @endphp
                <div class="border rounded-3 p-3 mb-3 d-flex gap-3 align-items-start"
                     data-id="{{ $banner->id }}"
                     data-banner="{{ $bannerJson }}">
                    <img src="{{ $banner->image_url ?? 'https://placehold.co/160x90?text=Banner' }}" style="width:160px;height:90px;object-fit:cover;border-radius:8px;">
                    <div class="flex-grow-1">
                        <strong>{{ $banner->title_en }}</strong> <span class="text-muted">/ {{ $banner->title_ms }}</span>
                        <div class="text-muted small">{{ $banner->subtitle_en }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" onclick="editBanner(this)"><i class="feather-edit-2"></i></button>
                        <button type="button" class="btn btn-sm btn-light text-danger" onclick="deleteBanner({{ $banner->id }})"><i class="feather-trash-2"></i></button>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">No banners yet. Click "Add Banner" to create the hero slide for this page.</p>
                @endforelse
            </div>

            <form id="bannerForm" class="border rounded-3 p-3 mt-3 d-none" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="banner_id" id="banner_id">
                <div id="bannerFormTitle" class="fw-semibold mb-2 text-muted small"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title (English)</label>
                        <input type="text" name="title_en" id="banner_title_en" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title (Malay)</label>
                        <input type="text" name="title_ms" id="banner_title_ms" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subtitle (English)</label>
                        <textarea name="subtitle_en" id="banner_subtitle_en" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subtitle (Malay)</label>
                        <textarea name="subtitle_ms" id="banner_subtitle_ms" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Button Text (English)</label>
                        <input type="text" name="button_text_en" id="banner_button_text_en" class="form-control" placeholder="Explore Books">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Button Text (Malay)</label>
                        <input type="text" name="button_text_ms" id="banner_button_text_ms" class="form-control" placeholder="Terokai Buku">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Button Link</label>
                        <input type="text" name="button_url" id="banner_button_url" class="form-control" placeholder="/physical-books">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" id="banner_sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="banner_is_active" value="1" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Banner Image (Desktop)</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <div class="form-text">Leave empty to keep the current image when editing.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Banner Image (Mobile)</label>
                        <input type="file" name="mobile_image" accept="image/*" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-light" onclick="hideBannerForm()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveBanner({{ $page->id }})">Save Banner</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════ Sections (generic: stats / features / steps / checklist) ══════════════════════════ --}}
    <div class="card stretch mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">Page Sections</h6>
                <small class="text-muted">Stat rows, feature grids, "how it works" steps, checklists — any repeatable block on this page.</small>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="showSectionForm()">
                <i class="feather-plus"></i> Add Section
            </button>
        </div>
        <div class="card-body">

            {{-- Existing sections --}}
            @forelse($page->sections as $section)
            @php
                $sectionJson = json_encode($section->only([
                    'id','section_key','section_type','title_en','title_ms',
                    'description_en','description_ms','sort_order'
                ]));
            @endphp
            <div class="border rounded-3 p-3 mb-3"
                 data-section-id="{{ $section->id }}"
                 data-section="{{ $sectionJson }}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-light-primary text-primary text-uppercase me-2">{{ str_replace('_',' ',$section->section_type) }}</span>
                        <strong>{{ $section->title_en ?: $section->section_key }}</strong>
                        @if($section->title_ms)
                            <span class="text-muted"> / {{ $section->title_ms }}</span>
                        @endif
                        @if($section->description_en)
                            <div class="text-muted small mt-1">{{ $section->description_en }}</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" onclick="editSection(this)"><i class="feather-edit-2"></i></button>
                        <button type="button" class="btn btn-sm btn-light text-danger" onclick="deleteSection({{ $section->id }})"><i class="feather-trash-2"></i></button>
                    </div>
                </div>

                {{-- Items inside this section --}}
                <div class="row g-2 mt-2">
                    @forelse($section->items as $item)
                    @php
                        $itemJson = json_encode($item->only([
                            'id','section_id','icon','value_en','value_ms',
                            'label_en','label_ms','description_en','description_ms','sort_order'
                        ]));
                    @endphp
                    <div class="col-md-3">
                        <div class="border rounded-3 p-2 h-100 position-relative"
                             data-item="{{ $itemJson }}">
                            <div class="d-flex gap-2 position-absolute top-0 end-0 p-1">
                                <button type="button" class="btn btn-xs btn-light" onclick="editItem(this)"><i class="feather-edit-2" style="font-size:12px;"></i></button>
                                <button type="button" class="btn btn-xs btn-light text-danger" onclick="deleteItem({{ $item->id }})"><i class="feather-trash-2" style="font-size:12px;"></i></button>
                            </div>
                            @if($item->icon)
                                <i class="{{ $item->icon }} mb-1 d-block" style="font-size:20px;"></i>
                            @endif
                            @if($item->value_en)
                                <div class="fw-bold">{{ $item->value_en }}</div>
                            @endif
                            <div class="small">{{ $item->label_en }}</div>
                            <div class="small text-muted">{{ $item->label_ms }}</div>
                            @if($item->description_en)
                                <div class="small text-muted mt-1">{{ $item->description_en }}</div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-muted small">No items yet in this section.</div>
                    @endforelse

                    <div class="col-md-3 d-flex align-items-center justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" style="min-height:80px;" onclick="showItemForm({{ $section->id }})">
                            <i class="feather-plus"></i> Add Item
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted mb-0">No sections yet. Click "Add Section" to create a stats row, feature grid, or steps block.</p>
            @endforelse

            {{-- Section form (hidden until Add/Edit clicked) --}}
            <form id="sectionForm" class="border rounded-3 p-3 mt-3 d-none" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="section_id" id="section_id">
                <div id="sectionFormTitle" class="fw-semibold mb-2 text-muted small"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Section Type</label>
                        <select name="section_type" id="section_type" class="form-control">
                            <option value="stats_row">Stats Row (numbers)</option>
                            <option value="feature_grid">Feature Grid (icon + label)</option>
                            <option value="steps">Steps (numbered process)</option>
                            <option value="checklist">Checklist (bullet points)</option>
                            <option value="text_block">Text Block</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Section Key</label>
                        <input type="text" name="section_key" id="section_key" class="form-control" placeholder="e.g. stats, features, how_it_works">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" id="section_sort_order" class="form-control" value="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Section Title (English)</label>
                        <input type="text" name="title_en" id="section_title_en" class="form-control" placeholder="e.g. What is EasyReadz?">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Section Title (Malay)</label>
                        <input type="text" name="title_ms" id="section_title_ms" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Section Description (English)</label>
                        <textarea name="description_en" id="section_description_en" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Section Description (Malay)</label>
                        <textarea name="description_ms" id="section_description_ms" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-light" onclick="hideSectionForm()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSection({{ $page->id }})">Save Section</button>
                </div>
            </form>

            {{-- Item form (hidden until "Add Item" clicked on a section) --}}
            <form id="itemForm" class="border rounded-3 p-3 mt-3 d-none" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="item_id" id="item_id">
                <input type="hidden" id="item_section_id">
                <div id="itemFormTitle" class="fw-semibold mb-2 text-muted small"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Icon</label>
                        <input type="text" name="icon" id="item_icon" class="form-control" placeholder="feather-shield">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Value (English)</label>
                        <input type="text" name="value_en" id="item_value_en" class="form-control" placeholder="1,000+">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Value (Malay)</label>
                        <input type="text" name="value_ms" id="item_value_ms" class="form-control" placeholder="1,000+">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Label (English)</label>
                        <input type="text" name="label_en" id="item_label_en" class="form-control" placeholder="Published Books">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Label (Malay)</label>
                        <input type="text" name="label_ms" id="item_label_ms" class="form-control" placeholder="Buku Diterbitkan">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description (English)</label>
                        <textarea name="description_en" id="item_description_en" class="form-control" rows="2" placeholder="Only needed for steps / feature cards"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description (Malay)</label>
                        <textarea name="description_ms" id="item_description_ms" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" id="item_sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Image (optional, instead of icon)</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-light" onclick="hideItemForm()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveItem()">Save Item</button>
                </div>
            </form>

        </div>
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
.btn-xs{padding:.1rem .35rem;font-size:11px;line-height:1;}
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ══════════════ Banners ══════════════ */
function showBannerForm(){ document.getElementById('bannerForm').classList.remove('d-none'); }
function hideBannerForm(){
    document.getElementById('bannerForm').classList.add('d-none');
    document.getElementById('bannerForm').reset();
    document.getElementById('banner_id').value = '';
    document.getElementById('bannerFormTitle').textContent = '';
}

function editBanner(btn){
    const row = btn.closest('[data-banner]');
    const data = JSON.parse(row.dataset.banner);

    document.getElementById('banner_id').value               = data.id;
    document.getElementById('banner_title_en').value         = data.title_en ?? '';
    document.getElementById('banner_title_ms').value         = data.title_ms ?? '';
    document.getElementById('banner_subtitle_en').value      = data.subtitle_en ?? '';
    document.getElementById('banner_subtitle_ms').value      = data.subtitle_ms ?? '';
    document.getElementById('banner_button_text_en').value   = data.button_text_en ?? '';
    document.getElementById('banner_button_text_ms').value   = data.button_text_ms ?? '';
    document.getElementById('banner_button_url').value       = data.button_url ?? '';
    document.getElementById('banner_sort_order').value       = data.sort_order ?? 0;
    document.getElementById('banner_is_active').checked      = !!data.is_active;
    document.getElementById('bannerFormTitle').textContent   = 'Editing: ' + (data.title_en || 'banner #' + data.id);

    showBannerForm();
    document.getElementById('bannerForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function saveBanner(pageId){
    const form = document.getElementById('bannerForm');
    const fd = new FormData(form);

    fetch(`/admin/website/${pageId}/banners`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: fd
    })
    .then(r => r.json())
    .then(res => { if (res.status === 'success') location.reload(); else alert(res.message); });
}

function deleteBanner(id){
    if(!confirm('Delete this banner?')) return;
    fetch(`/admin/website/banners/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF }
    }).then(r=>r.json()).then(res => { if(res.status==='success') location.reload(); });
}

/* ══════════════ Sections ══════════════ */
function showSectionForm(){ document.getElementById('sectionForm').classList.remove('d-none'); }
function hideSectionForm(){
    document.getElementById('sectionForm').classList.add('d-none');
    document.getElementById('sectionForm').reset();
    document.getElementById('section_id').value = '';
    document.getElementById('sectionFormTitle').textContent = '';
}

function editSection(btn){
    const row = btn.closest('[data-section]');
    const data = JSON.parse(row.dataset.section);

    document.getElementById('section_id').value             = data.id;
    document.getElementById('section_type').value           = data.section_type ?? 'feature_grid';
    document.getElementById('section_key').value             = data.section_key ?? '';
    document.getElementById('section_sort_order').value      = data.sort_order ?? 0;
    document.getElementById('section_title_en').value        = data.title_en ?? '';
    document.getElementById('section_title_ms').value        = data.title_ms ?? '';
    document.getElementById('section_description_en').value  = data.description_en ?? '';
    document.getElementById('section_description_ms').value  = data.description_ms ?? '';
    document.getElementById('sectionFormTitle').textContent  = 'Editing: ' + (data.title_en || data.section_key);

    showSectionForm();
    document.getElementById('sectionForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function saveSection(pageId){
    const form = document.getElementById('sectionForm');
    const fd = new FormData(form);

    fetch(`/admin/website/${pageId}/sections`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: fd
    })
    .then(r => r.json())
    .then(res => { if (res.status === 'success') location.reload(); else alert(res.message); });
}

function deleteSection(id){
    if(!confirm('Delete this section and all its items?')) return;
    fetch(`/admin/website/sections/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF }
    }).then(r=>r.json()).then(res => { if(res.status==='success') location.reload(); });
}

/* ══════════════ Section Items ══════════════ */
function showItemForm(sectionId){
    document.getElementById('item_section_id').value = sectionId;
    document.getElementById('itemForm').classList.remove('d-none');
    document.getElementById('itemForm').scrollIntoView({ behavior:'smooth', block:'center' });
}
function hideItemForm(){
    document.getElementById('itemForm').classList.add('d-none');
    document.getElementById('itemForm').reset();
    document.getElementById('item_id').value = '';
    document.getElementById('itemFormTitle').textContent = '';
}

function editItem(btn){
    const row = btn.closest('[data-item]');
    const data = JSON.parse(row.dataset.item);

    document.getElementById('item_section_id').value        = data.section_id;
    document.getElementById('item_id').value                = data.id;
    document.getElementById('item_icon').value               = data.icon ?? '';
    document.getElementById('item_value_en').value           = data.value_en ?? '';
    document.getElementById('item_value_ms').value           = data.value_ms ?? '';
    document.getElementById('item_label_en').value           = data.label_en ?? '';
    document.getElementById('item_label_ms').value           = data.label_ms ?? '';
    document.getElementById('item_description_en').value     = data.description_en ?? '';
    document.getElementById('item_description_ms').value     = data.description_ms ?? '';
    document.getElementById('item_sort_order').value         = data.sort_order ?? 0;
    document.getElementById('itemFormTitle').textContent     = 'Editing: ' + (data.label_en || data.value_en || 'item #' + data.id);

    document.getElementById('itemForm').classList.remove('d-none');
    document.getElementById('itemForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function saveItem(){
    const sectionId = document.getElementById('item_section_id').value;
    if (!sectionId) { alert('Section not selected.'); return; }

    const form = document.getElementById('itemForm');
    const fd = new FormData(form);

    fetch(`/admin/website/sections/${sectionId}/items`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: fd
    })
    .then(r => r.json())
    .then(res => { if (res.status === 'success') location.reload(); else alert(res.message); });
}

function deleteItem(id){
    if(!confirm('Delete this item?')) return;
    fetch(`/admin/website/section-items/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF }
    }).then(r=>r.json()).then(res => { if(res.status==='success') location.reload(); });
}

/* ══════════════ Toast ══════════════ */
function showToast(status, message){
    const toast = document.getElementById('ajaxToast');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMsg');
    toast.className = 'ajax-toast show ' + (status === 'success' ? 'toast-success' : 'toast-error');
    icon.innerHTML = status === 'success' ? '<i class="feather-check-circle"></i>' : '<i class="feather-alert-circle"></i>';
    msg.textContent = message;
    setTimeout(hideToast, 3500);
}
function hideToast(){ document.getElementById('ajaxToast').classList.remove('show'); }
</script>
@endpush