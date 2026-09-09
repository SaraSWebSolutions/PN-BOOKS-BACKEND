@extends('layouts.app')
@section('title', $book->title)

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Book Details</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Books</a></li>
            <li class="breadcrumb-item">{{ $book->title }}</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto d-flex gap-2">
        <a href="{{ route('books.index') }}" class="btn btn-light-brand btn-sm">
            <i class="feather-arrow-left me-1"></i> Back to Books
        </a>
        <a href="{{ route('books.edit', $book->id) }}" class="btn btn-primary btn-sm">
            <i class="feather-edit-2 me-1"></i> Edit Book
        </a>
    </div>
</div>

<div class="main-content">

    {{-- ── Hero header: cover + title + key facts ─────────────────────── --}}
    <div class="card mb-4 book-hero">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-2 col-4">
                    <img src="{{ $book->cover_image_url ?? 'https://placehold.co/220x300?text=No+Cover' }}"
                         class="img-fluid rounded-3 book-hero-cover">
                </div>

                <div class="col-md-7 col-8">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge status-badge status-{{ $book->status }}">{{ ucfirst($book->status) }}</span>
                        <span class="badge status-badge status-visibility">{{ ucfirst($book->visibility) }}</span>
                        @if($book->is_featured)<span class="badge status-badge status-featured"><i class="feather-star fs-11 me-1"></i>Featured</span>@endif
                        @if($book->is_bestseller)<span class="badge status-badge status-bestseller"><i class="feather-trending-up fs-11 me-1"></i>Bestseller</span>@endif
                    </div>

                    <h3 class="fw-bold mb-1">{{ $book->title }}</h3>
                    @if($book->subtitle)<p class="text-muted mb-2">{{ $book->subtitle }}</p>@endif

                    <div class="d-flex flex-wrap gap-4 fs-13 text-muted mt-3">
                        <div><i class="feather-user me-1"></i>{{ $book->author->pen_name ?? optional($book->author->user)->name ?? '—' }}</div>
                        <div><i class="feather-briefcase me-1"></i>{{ $book->publisher->company_name ?? optional($book->publisher->user)->name ?? '—' }}</div>
                        <div><i class="feather-hash me-1"></i>{{ $book->isbn ?: '—' }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="hero-fact-list">
                        <div class="hero-fact"><span>Category</span><strong>{{ $book->category->name_en ?? '—' }}</strong></div>
                        <div class="hero-fact"><span>Subcategory</span><strong>{{ $book->subcategory->name_en ?? '—' }}</strong></div>
                        <div class="hero-fact"><span>Series</span><strong>{{ $book->series->name ?? '—' }}</strong></div>
                        <div class="hero-fact"><span>Languages</span><strong>{{ $book->languages->pluck('name')->join(', ') ?: '—' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">

            {{-- ── Description ──────────────────────────────────────────── --}}
            @if($book->short_description || $book->description)
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-align-left"></i> Description</div>
                <div class="card-body">
                    @if($book->short_description)
                        <p class="fw-semibold mb-1 fs-13 text-muted text-uppercase">Short Description</p>
                        <p class="mb-3">{{ $book->short_description }}</p>
                    @endif
                    @if($book->description)
                        <p class="fw-semibold mb-1 fs-13 text-muted text-uppercase">Full Description</p>
                        <p class="mb-0 text-body">{{ $book->description }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Formats ──────────────────────────────────────────────── --}}
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-layers"></i> Formats</div>
                <div class="card-body">
                    @forelse($book->formats as $format)
                        @php $settings = json_decode($format->pivot->settings ?? '{}', true) ?? []; @endphp
                        <div class="format-block mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="format-icon-box"><i class="{{ $format->icon }}"></i></div>
                                    <strong>{{ $format->name }}</strong>
                                </div>
                                @if($format->pivot->sku)
                                    <span class="badge bg-light-brand text-brand">SKU: {{ $format->pivot->sku }}</span>
                                @endif
                            </div>
                            @if(!empty($settings))
                            <div class="row fs-13 text-muted gy-1 ps-1">
                                @foreach($settings as $key => $value)
                                    @continue(empty($value))
                                    <div class="col-md-4 col-6 py-1">
                                        <span class="text-uppercase fs-11 text-muted d-block">{{ ucwords(str_replace('_',' ',$key)) }}</span>
                                        <strong class="text-dark">{{ is_array($value) ? implode(', ', $value) : $value }}</strong>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No formats configured.</p>
                    @endforelse
                </div>
            </div>

            {{-- ── Files & Chapters ─────────────────────────────────────── --}}
            @if($book->files->count() || $book->chapters->count())
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-file-text"></i> Files</div>
                <div class="card-body">
                    @if($book->files->count())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Format</th><th>Type</th><th>File</th><th>Size</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($book->files as $file)
                                <tr>
                                    <td>{{ $file->format->name ?? '—' }}</td>
                                    <td>{{ ucfirst(str_replace('_',' ',$file->file_type)) }}</td>
                                    <td><a href="/{{ $file->file_path }}" target="_blank" class="text-decoration-none">{{ $file->file_name }}</a></td>
                                    <td>{{ $file->file_size ? number_format($file->file_size / 1024, 1) . ' KB' : '—' }}</td>
                                    <td><span class="badge bg-light-success text-success">{{ ucfirst($file->status) }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if($book->chapters->count())
                    <p class="fw-semibold mb-2 fs-13 text-muted text-uppercase">Chapters</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th style="width:40px;">#</th><th>Title</th><th>Audio</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($book->chapters as $chapter)
                                <tr>
                                    <td>{{ $chapter->chapter_number }}</td>
                                    <td>{{ $chapter->title }}</td>
                                    <td>@if($chapter->audio_url)<a href="{{ $chapter->audio_url }}" target="_blank" class="text-decoration-none"><i class="feather-play-circle me-1"></i>Play</a>@else — @endif</td>
                                    <td><span class="badge bg-light-info text-info">{{ ucfirst($chapter->status) }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Pricing & Inventory side by side ─────────────────────── --}}
            <div class="row g-4">
                @if($book->prices->count())
                <div class="col-md-6">
                    <div class="card mb-4 section-card h-100">
                        <div class="section-card-header"><i class="feather-dollar-sign"></i> Pricing</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Format</th><th>Country</th><th class="text-end">Price</th><th class="text-end">Sale</th></tr></thead>
                                    <tbody>
                                    @foreach($book->prices as $price)
                                        <tr>
                                            <td>{{ $price->format->name ?? '—' }}</td>
                                            <td>{{ $price->country->name ?? '—' }}</td>
                                            <td class="text-end">{{ $price->currency->symbol ?? '' }}{{ number_format($price->price, 2) }}</td>
                                            <td class="text-end">{{ $price->sale_price ? ($price->currency->symbol ?? '').number_format($price->sale_price, 2) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($book->inventory->count())
                <div class="col-md-6">
                    <div class="card mb-4 section-card h-100">
                        <div class="section-card-header"><i class="feather-package"></i> Inventory</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Format</th><th>SKU</th><th class="text-end">Stock</th><th>Status</th></tr></thead>
                                    <tbody>
                                    @foreach($book->inventory as $inv)
                                        <tr>
                                            <td>{{ $inv->format->name ?? '—' }}</td>
                                            <td>{{ $inv->sku ?: '—' }}</td>
                                            <td class="text-end">{{ $inv->stock_quantity }}</td>
                                            <td><span class="badge bg-light-success text-success">{{ ucfirst(str_replace('_',' ',$inv->stock_status)) }}</span></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- ── Shipping ─────────────────────────────────────────────── --}}
            @if($book->shipping)
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-truck"></i> Shipping</div>
                <div class="card-body">
                    <div class="row fs-13 mb-3 gy-2">
                        <div class="col-md-3"><span class="text-muted d-block fs-11 text-uppercase">Class</span><strong>{{ $book->shipping->shipping_class ?: '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted d-block fs-11 text-uppercase">Weight</span><strong>{{ $book->shipping->weight ? $book->shipping->weight.' kg' : '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted d-block fs-11 text-uppercase">Ships From</span><strong>{{ $book->shipping->ships_from ?: '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted d-block fs-11 text-uppercase">Dimensions</span>
                            <strong>{{ $book->shipping->length ?: '—' }} × {{ $book->shipping->width ?: '—' }} × {{ $book->shipping->height ?: '—' }} cm</strong>
                        </div>
                    </div>
                    @if($book->shipping->methods->count())
                        <p class="fw-semibold mb-2 fs-13 text-muted text-uppercase">Shipping Methods</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Method</th><th class="text-end">Cost</th><th>Status</th></tr></thead>
                                <tbody>
                                @foreach($book->shipping->methods as $method)
                                    <tr>
                                        <td>{{ $method->name }}</td>
                                        <td class="text-end">{{ number_format($method->cost, 2) }}</td>
                                        <td><span class="badge {{ $method->is_active ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── SEO ──────────────────────────────────────────────────── --}}
            @if($book->seo)
            @php $showMetaKeywords = is_array($book->seo->meta_keywords) ? $book->seo->meta_keywords : []; @endphp
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-search"></i> SEO & Meta</div>
                <div class="card-body fs-13">
                    <div class="row gy-2 mb-3">
                        <div class="col-md-6"><span class="text-muted d-block fs-11 text-uppercase">SEO Title</span><strong>{{ $book->seo->seo_title ?: '—' }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block fs-11 text-uppercase">URL Slug</span><strong>{{ $book->seo->url_slug ?: '—' }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block fs-11 text-uppercase">Meta Description</span><strong>{{ $book->seo->meta_description ?: '—' }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block fs-11 text-uppercase">Meta Keywords</span><strong>{{ !empty($showMetaKeywords) ? implode(', ', $showMetaKeywords) : '—' }}</strong></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge {{ $book->seo->allow_index ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            {{ $book->seo->allow_index ? 'Indexable' : 'No Index' }}
                        </span>
                        <span class="badge {{ $book->seo->allow_follow ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            {{ $book->seo->allow_follow ? 'Follow' : 'No Follow' }}
                        </span>
                        <span class="badge {{ $book->seo->enable_structured_data ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            {{ $book->seo->enable_structured_data ? 'Structured Data On' : 'Structured Data Off' }}
                        </span>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Badges ───────────────────────────────────────────────── --}}
            @if(!empty($book->badges))
            <div class="card mb-4 section-card">
                <div class="section-card-header"><i class="feather-award"></i> Badges</div>
                <div class="card-body">
                    @foreach($book->badges as $badge)
                        <span class="badge bg-light-brand text-brand me-1 mb-1">{{ ucwords(str_replace('_',' ',$badge)) }}</span>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.book-hero-cover{ width:100%; aspect-ratio: 2/3; object-fit:cover; box-shadow:0 4px 16px rgba(0,0,0,.08); }

.status-badge{ font-size:11px; font-weight:600; padding:5px 10px; border-radius:8px; }
.status-published{ background:#d1fae5; color:#065f46; }
.status-draft{ background:#fef3c7; color:#92400e; }
.status-private, .status-inactive{ background:#fee2e2; color:#991b1b; }
.status-visibility{ background:#eef0f4; color:#4b5563; }
.status-featured{ background:#f2eeff; color:#7b5cf0; }
.status-bestseller{ background:#fde8ec; color:#c2185b; }

.hero-fact-list{ display:flex; flex-direction:column; gap:10px; }
.hero-fact{ display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px dashed #eef0f4; font-size:13px; }
.hero-fact span{ color:#8a94a6; }
.hero-fact strong{ color:#1c1f26; text-align:right; max-width:60%; }

.section-card{ border-radius:14px; overflow:hidden; }
.section-card-header{ background:#f8f9fb; padding:14px 20px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #eef0f4; color:#374151; }
.section-card-header i{ color:#7b5cf0; }

.format-block{ border:1px solid #eef0f4; border-radius:12px; padding:14px 16px; }
.format-icon-box{ width:34px; height:34px; border-radius:9px; background:#f2eeff; color:#7b5cf0; display:flex; align-items:center; justify-content:center; font-size:15px; }
</style>
@endpush