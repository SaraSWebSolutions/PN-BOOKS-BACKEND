@extends('layouts.app')
@section('title', 'Edit Book')

@section('content')
<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

{{-- Center-screen processing overlay — now only shown by the Files & DRM tab
     (and Media, which uploads real files). Every other tab just uses the
     button spinner, which is why Basic Info now feels instant. --}}
<div id="processingOverlay" class="processing-overlay d-none">
    <div class="processing-box">
        <div class="processing-icon">
            <i class="feather-upload-cloud"></i>
        </div>
        <p class="mt-1 mb-1 fw-semibold" id="processingText">Saving...</p>
        <p class="text-muted fs-13 mb-3" id="processingSubtext">Please wait a moment</p>
        <div class="processing-bar-track">
            <div class="processing-bar-fill"></div>
        </div>
    </div>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Edit Book</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Books</a></li>
            <li class="breadcrumb-item">Edit Book</li>
        </ul>
    </div>
</div>

<div class="main-content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form id="bookForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="book_id" id="book_id_field" value="{{ $book->id }}">

       <div class="card stretch stretch-full">
    <div class="card-header p-0 border-bottom d-flex align-items-stretch book-header-row">
        <div class="book-title-banner" id="bookTitleBanner">
            <i class="feather-book-open"></i>
           <span id="bookTitleBannerText">{{ $book->title ?? 'New Book (untitled)' }}{{ $book->isbn ? ' ('.$book->isbn.')' : '' }}</span>
        </div>
        <ul class="nav nav-tabs book-wizard-tabs" id="bookTabs">
                    <li class="nav-item"><button type="button" class="nav-link active" data-tab="tab-basic"><span class="step-num">1</span> Book Info</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-formats"><span class="step-num">2</span> Formats</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-files"><span class="step-num">3</span> Files & DRM</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-media"><span class="step-num">4</span> Media</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-pricing"><span class="step-num">5</span> Pricing</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-inventory"><span class="step-num">6</span> Inventory</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-shipping"><span class="step-num">7</span> Shipping</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-seo"><span class="step-num">8</span> SEO & Meta</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-tab="tab-publishing"><span class="step-num">9</span> Publishing</button></li>
                </ul>
            </div>

            <div class="card-body">
                @include('books.partials._basic-info',   ['book' => $book])
                @include('books.partials._formats',      ['book' => $book])
                @include('books.partials._files-drm',    ['book' => $book])
                @include('books.partials._media', ['book' => $book])
                @include('books.partials._pricing',      ['book' => $book])
                @include('books.partials._inventory',    ['book' => $book])
                @include('books.partials._shipping',     ['book' => $book])
                @include('books.partials._seo',          ['book' => $book])
                @include('books.partials._publishing',   ['book' => $book])
            </div>
        </div>
    </form>
</div>

{{-- ✅ Each country now carries its mapped currency (id/code/symbol) so the
     Pricing tab can show "RM", "$", "₹" etc. next to the price inputs and
     save the correct currency_id per row. --}}
<script id="countriesData" type="application/json">{!! json_encode($countries->map(fn($c)=>[
    'id'              => $c->id,
    'name'            => $c->name,
    'currency_id'     => optional($c->currency)->id,
    'currency_code'   => optional($c->currency)->code,
    'currency_symbol' => optional($c->currency)->symbol ?: optional($c->currency)->code,
    'tax_id'          => optional($c->activeTax)->id,
    'tax_name'        => optional($c->activeTax)->tax_name,
    'tax_rate'        => optional($c->activeTax)->tax_rate,
])) !!}</script>

{{-- ✅ FIX: 'code' was missing here, which broke ebook/audiobook block rendering in Files & DRM tab --}}
<script id="formatsData" type="application/json">{!! json_encode($formats->map(fn($f)=>['id'=>$f->id,'code'=>$f->code,'name'=>$f->name,'icon'=>$f->icon,'requires_shipping'=>$f->requires_shipping])) !!}</script>

{{-- ✅ Existing data for JS-rebuilt tabs (Files, Pricing, Inventory) --}}
<script id="bookExistingData" type="application/json">
{!! json_encode([
    'formats'   => $book->formats->pluck('id'),
    'prices'    => $book->prices,
    'inventory' => $book->inventory,
    'files'     => $book->files,
    'chapters'  => $book->chapters,
]) !!}
</script>
@endsection

@push('styles')
@include('books.partials._styles')
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}

/* ── Processing overlay ── */
.processing-overlay{
    position:fixed; inset:0; z-index:1040;
    background:rgba(255,255,255,.85); backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center;
}
.processing-overlay.d-none{ display:none !important; }
.processing-box{
    text-align:center; background:#fff; padding:32px 44px;
    border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,.15);
    min-width:320px;
}

/* ── Processing icon (replaces spinner) ── */
.processing-icon{
    width:56px; height:56px; border-radius:50%;
    background:#eef2ff; color:#4f46e5;
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 8px; font-size:24px;
}

/* ── Processing bar (indeterminate line animation) ── */
.processing-bar-track{
    width:100%; height:6px; border-radius:999px;
    background:#e5e7eb; overflow:hidden; margin-top:4px;
}
.processing-bar-fill{
    height:100%; width:40%; border-radius:999px;
    background:linear-gradient(90deg,#4f46e5,#818cf8);
    animation:processingSlide 1.2s ease-in-out infinite;
}
@keyframes processingSlide{
    0%   { margin-left:-40%; }
    50%  { margin-left:60%; }
    100% { margin-left:-40%; }
}
</style>
@endpush

@push('scripts')
@include('books.partials._scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const existing = JSON.parse(document.getElementById('bookExistingData').textContent);

    // Format checkboxes are now checked server-side via Blade (see _formats.blade.php),
    // so we just need to build the dependent tabs (Files, Pricing, Inventory) from them.
    buildAllDynamicTabs();

    // ── Prefill pricing ──
(existing.prices || []).forEach(p => {
    const input = document.querySelector(`[name="prices[${p.book_format_id}][${p.country_id}][price]"]`);
    if (input) input.value = p.price;
    const saleInput = document.querySelector(`[name="prices[${p.book_format_id}][${p.country_id}][sale_price]"]`);
    if (saleInput && p.sale_price) saleInput.value = p.sale_price;

    // ← NEW
    const discountInput = document.querySelector(`[name="prices[${p.book_format_id}][${p.country_id}][discount_percent]"]`);
    if (discountInput && p.discount_percent) discountInput.value = p.discount_percent;

    const applyTaxInput = document.querySelector(`[name="prices[${p.book_format_id}][${p.country_id}][apply_tax]"]`);
    if (applyTaxInput) applyTaxInput.checked = !!p.tax_id;

    const showDiscountInput = document.querySelector(`[name="prices[${p.book_format_id}][${p.country_id}][is_on_sale]"]`);
    if (showDiscountInput) showDiscountInput.checked = p.is_on_sale === undefined ? true : !!p.is_on_sale;

    const row = input?.closest('tr[data-has-tax]');
    if (row) recalcRowFinalPrice(row);
});

    // ── Prefill inventory ──
    (existing.inventory || []).forEach(inv => {
        ['sku', 'stock_quantity', 'low_stock_threshold'].forEach(key => {
            const el = document.querySelector(`[name="inventory[${inv.book_format_id}][${key}]"]`);
            if (el && inv[key] !== null && inv[key] !== undefined) el.value = inv[key];
        });
        const manageStockEl = document.querySelector(`[name="inventory[${inv.book_format_id}][manage_stock]"]`);
        if (manageStockEl) manageStockEl.checked = !!inv.manage_stock;
    });

    // ── Show already-uploaded files (browsers block prefilling <input type=file> for security) ──
  // ── Show already-uploaded files + a Remove button (browsers block prefilling <input type=file>) ──
(existing.files || []).forEach(f => {
    const input = document.querySelector(`[name="files[${f.book_format_id}][${f.file_type}]"]`);
    if (input && !input.dataset.hinted) {
        input.dataset.hinted = '1';

        const wrap = document.createElement('div');
        wrap.className = 'uploaded-file-hint d-flex align-items-center gap-2 mt-1';
        wrap.innerHTML = `
            <span class="fs-11 text-success">
                Already uploaded: <a href="/${f.file_path}" target="_blank">${f.file_name}</a>
            </span>
            <button type="button" class="btn btn-sm btn-light-danger py-0 px-2" title="Remove file"
                    onclick="removeUploadedFile(${f.id}, this)">
                <i class="feather-trash-2"></i>
            </button>
        `;
        input.insertAdjacentElement('afterend', wrap);
    }
});

    // ── Prefill existing chapters ──
    if (existing.chapters && existing.chapters.length) {
        // find the audiobook format id that has a chapters table rendered
        const audiobookFormatId = existing.formats.find(id => document.getElementById(`chaptersBody-${id}`));
        if (audiobookFormatId) {
            existing.chapters.forEach(ch => {
                addChapterRow(audiobookFormatId);
                const body = document.getElementById(`chaptersBody-${audiobookFormatId}`);
                const lastRow = body.lastElementChild;
                lastRow.querySelector('input[type=text]').value = ch.title;
              if (ch.audio_file_path) {
    const wrap = document.createElement('div');
    wrap.className = 'uploaded-file-hint d-flex align-items-center gap-2 mt-1';
    wrap.innerHTML = `
        <span class="fs-11 text-success">
            Uploaded: <a href="/${ch.audio_file_path}" target="_blank">audio file</a>
        </span>
        <button type="button" class="btn btn-sm btn-light-danger py-0 px-2" title="Remove audio"
                onclick="removeChapterAudio(${ch.id}, this)">
            <i class="feather-trash-2"></i>
        </button>
    `;
    lastRow.querySelector('input[type=file]').insertAdjacentElement('afterend', wrap);
}
            });
        }
    }
});
</script>
@endpush