@extends('layouts.app')
@section('title', 'Add New Book')

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
        <div class="page-header-title"><h5 class="m-b-10">Add New Book</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Books</a></li>
            <li class="breadcrumb-item">Add New Book</li>
        </ul>
    </div>
</div>

<div class="main-content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- No more action/method submit — every tab saves itself via AJAX --}}
    <form id="bookForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="book_id" id="book_id_field" value="">

        <div class="card stretch stretch-full">
           <div class="card-header p-0 border-bottom">
    <div class="book-title-banner" id="bookTitleBanner">
        <i class="feather-book-open"></i>
       <span id="bookTitleBannerText">New Book (untitled)</span>
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
                @include('books.partials._basic-info',    ['book' => null])
                @include('books.partials._formats',       ['book' => null])
                @include('books.partials._files-drm',     ['book' => null])
                @include('books.partials._media',         ['book' => null])
                @include('books.partials._pricing',       ['book' => null])
                @include('books.partials._inventory',     ['book' => null])
                @include('books.partials._shipping',      ['book' => null])
                @include('books.partials._seo',           ['book' => null])
                @include('books.partials._publishing',    ['book' => null])
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




<script id="formatsData" type="application/json">{!! json_encode($formats->map(fn($f)=>['id'=>$f->id,'code'=>$f->code,'name'=>$f->name,'icon'=>$f->icon,'requires_shipping'=>$f->requires_shipping])) !!}</script>
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
@endpush