{{-- resources/views/books/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Books')

@section('content')

<div class="ajax-toast" id="ajaxToast">
    <span id="toastIcon"></span><span id="toastMsg"></span>
    <button class="toast-close" onclick="hideToast()">✕</button>
</div>

<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title"><h5 class="m-b-10">Books</h5></div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Bookstore</li>
            <li class="breadcrumb-item">Books</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <a href="{{ route('books.create') }}" class="btn btn-primary">
            <i class="feather-plus me-2"></i>Add Book
        </a>
    </div>
</div>

<div class="main-content">

    <div class="row g-3 mb-4">
        @foreach([
            ['label'=>'Total','value'=>$stats['total'],'icon'=>'feather-book-open','bg'=>'rgba(99,102,241,.1)','color'=>'#6366f1'],
            ['label'=>'Published','value'=>$stats['published'],'icon'=>'feather-check-circle','bg'=>'rgba(16,185,129,.1)','color'=>'#10b981','fw'=>'text-success'],
            ['label'=>'Draft','value'=>$stats['draft'],'icon'=>'feather-edit-3','bg'=>'rgba(234,179,8,.1)','color'=>'#eab308','fw'=>'text-warning'],
            ['label'=>'Featured','value'=>$stats['featured'],'icon'=>'feather-star','bg'=>'rgba(236,72,153,.1)','color'=>'#ec4899'],
        ] as $s)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="hstack justify-content-between">
                        <div>
                            <div class="text-muted fs-12 mb-1">{{ $s['label'] }}</div>
                            <div class="fs-22 fw-bold {{ $s['fw'] ?? '' }}">{{ $s['value'] }}</div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:10px;background:{{ $s['bg'] }};display:flex;align-items:center;justify-content:center;color:{{ $s['color'] }};">
                            <i class="{{ $s['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card stretch stretch-full">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3 flex-wrap">
            <span class="fw-semibold">All Books</span>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                {{-- Show X entries — pure front-end, no reload (same pattern as Categories) --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted fs-12">Show</span>
                    <select id="perPageSelect" class="form-select form-select-sm entries-select">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                    <span class="text-muted fs-12">entries</span>
                </div>

                {{-- ✅ NEW — Category filter (Select2) --}}
                <select id="categoryFilter" class="form-select form-select-sm select2-filter" data-placeholder="All Categories" style="width:170px;">
                    <option value=""></option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                    @endforeach
                </select>

                {{-- ✅ NEW — Author filter (Select2) --}}
                <select id="authorFilter" class="form-select form-select-sm select2-filter" data-placeholder="All Authors" style="width:170px;">
                    <option value=""></option>
                    @foreach($authors as $author)
                        <option value="{{ $author->id }}">{{ $author->pen_name ?: optional($author->user)->name ?? 'Unnamed Author' }}</option>
                    @endforeach
                </select>

                <select id="typeFilter" class="form-select form-select-sm" style="width:150px;">
                    <option value="">All Types</option>
                    <option value="physical">Physical</option>
                    <option value="ebook">eBook</option>
                    <option value="audiobook">Audiobook</option>
                </select>

                <select id="statusFilter" class="form-select form-select-sm" style="width:150px;">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="private">Private</option>
                </select>

                <div class="input-group input-group-sm" style="max-width:220px;">
                    <span class="input-group-text"><i class="feather-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search books…">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Type</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Featured</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookTableBody">
                        @forelse($books as $book)
                        @php
                            $formatCodes = $book->formats->pluck('code')->toArray();

                            // ✅ Map each format to an icon + color so Physical / eBook / Audiobook
                            // are visually distinct at a glance in the Type column.
                            $formatIconMap = [
                                'physical'  => ['icon' => 'feather-book',        'bg' => 'bg-light-primary', 'color' => 'text-primary'],
                                'ebook'     => ['icon' => 'feather-tablet',      'bg' => 'bg-light-info',    'color' => 'text-info'],
                                'audiobook' => ['icon' => 'feather-headphones', 'bg' => 'bg-light-warning', 'color' => 'text-warning'],
                            ];
                        @endphp
                        <tr class="book-row" id="book-row-{{ $book->id }}"
                            data-type="{{ implode(' ', $formatCodes) }}"
                            data-status="{{ $book->status }}"
                            data-category-id="{{ $book->category_id }}"
                            data-author-id="{{ $book->author_id }}">
                            <td class="text-muted fs-12 row-index">{{ $loop->iteration }}</td>
                            <td>
                                @if($book->cover_image)
                                    <img src="{{ $book->cover_image_url }}" style="width:42px;height:56px;object-fit:cover;border-radius:6px;">
                                @else
                                    <div style="width:42px;height:56px;border-radius:6px;background:#eee;display:flex;align-items:center;justify-content:center;">
                                        <i class="feather-book text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('books.show', $book->id) }}" class="fw-semibold fs-13 text-dark text-decoration-none">{{ $book->title }}</a>
                                <div class="text-muted fs-11">{{ $book->isbn ?? '—' }}</div>
                            </td>
                            <td class="fs-12">
                                {{ $book->category->name_en ?? '—' }}
                                @if($book->subcategory)
                                    <div class="text-muted fs-11">{{ $book->subcategory->name_en }}</div>
                                @endif
                            </td>
                            <td class="fs-12">{{ $book->author->pen_name ?? optional($book->author->user)->name ?? '—' }}</td>
                            <td>
                                @forelse($book->formats as $format)
                                    @php
                                        $codeOrName = strtolower($format->code ?: $format->name);
                                        $meta = $formatIconMap[$format->code] ?? [
                                            'icon'  => str_contains($codeOrName, 'audio') ? 'feather-headphones'
                                                       : (str_contains($codeOrName, 'ebook') || str_contains($codeOrName, 'e-book') || str_contains($codeOrName, 'e_book') ? 'feather-tablet'
                                                       : 'feather-book'),
                                            'bg'    => 'bg-light-secondary',
                                            'color' => 'text-secondary',
                                        ];
                                    @endphp
                                    <span class="badge {{ $meta['bg'] }} {{ $meta['color'] }} d-inline-flex align-items-center gap-1 mb-1" title="{{ $format->name }}">
                                        <i class="{{ $meta['icon'] }} fs-11"></i>{{ $format->name }}
                                    </span>
                                @empty
                                    <span class="text-muted fs-12">—</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                <span class="badge
                                    {{ $book->status === 'published' ? 'bg-light-success text-success' :
                                       ($book->status === 'draft' ? 'bg-light-warning text-warning' : 'bg-light-danger text-danger') }}
                                    text-capitalize">{{ $book->status }}</span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input featured-toggle" type="checkbox"
                                           {{ $book->is_featured ? 'checked' : '' }} data-id="{{ $book->id }}">
                                </div>
                            </td>
                            <td>
                                <div class="hstack gap-2 justify-content-end">
                                    <a href="{{ route('books.show', $book->id) }}" class="avatar-text avatar-md" title="View">
                                        <i class="feather feather-eye"></i>
                                    </a>
                                    <a href="{{ route('books.edit', $book->id) }}" class="avatar-text avatar-md" title="Edit">
                                        <i class="feather feather-edit-3"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="avatar-text avatar-md text-danger" title="Delete"
                                       onclick="deleteBook({{ $book->id }}, '{{ addslashes($book->title) }}')">
                                        <i class="feather feather-trash-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="noDataRow">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="feather-book-open fs-30 d-block mb-2 opacity-50"></i>
                                No books found. Click <strong>Add Book</strong> to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══ Pagination footer (built entirely by JS — same as Categories) ══ --}}
        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-3" id="paginationFooter">
            <span class="text-muted fs-12" id="entriesInfo">Showing 0 entries</span>
            <div class="custom-pagination" id="paginationContainer"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<style>
.ajax-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.ajax-toast.show{transform:translateX(0);}
.ajax-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.ajax-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.ajax-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}

.entries-select{
    width:auto;
    border-radius:8px;
    border:1px solid #e2e5ec;
    font-size:13px;
    padding:4px 28px 4px 10px;
}
.entries-select:focus{
    border-color:#7b5cf0;
    box-shadow:0 0 0 3px rgba(123,92,240,.12);
}

/* ── Select2 styling for Category / Author filters — matches purple accent used elsewhere ── */
.select2-filter + .select2-container{ width:170px !important; }
.select2-container--default .select2-selection--single{
    height:31px;
    border:1px solid #e2e5ec;
    border-radius:6px;
    display:flex;
    align-items:center;
    padding:0 8px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:29px;
    font-size:13px;
    padding-left:0;
    color:#4f5b76;
}
.select2-container--default .select2-selection--single .select2-selection__arrow{
    height:29px;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single{
    border-color:#7b5cf0;
    box-shadow:0 0 0 3px rgba(123,92,240,.12);
}
.select2-dropdown{
    border-color:#e2e5ec;
    border-radius:8px;
    overflow:hidden;
}
.select2-results__option--highlighted[aria-selected]{
    background-color:#f2eeff !important;
    color:#7b5cf0 !important;
}
.select2-search--dropdown .select2-search__field{
    border-radius:6px;
    border:1px solid #e2e5ec;
}

/* ── Format badges (Physical / eBook / Audiobook) ── */
.bg-light-secondary{ background:#eef0f4 !important; }
.text-secondary{ color:#6b7280 !important; }

.custom-pagination ul{
    list-style:none;
    display:flex;
    gap:4px;
    margin:0;
    padding:0;
}
.custom-pagination .page-item .page-link{
    border:1px solid #e2e5ec;
    border-radius:8px;
    color:#4f5b76;
    font-size:13px;
    font-weight:500;
    min-width:32px;
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0 8px;
    margin:0;
    cursor:pointer;
    background:#fff;
    text-decoration:none;
    user-select:none;
}
.custom-pagination .page-item .page-link:hover:not(.disabled):not(.active){
    background:#f2eeff;
    color:#7b5cf0;
    border-color:#e2e5ec;
}
.custom-pagination .page-item.active .page-link{
    background:#7b5cf0;
    border-color:#7b5cf0;
    color:#fff;
    cursor:default;
}
.custom-pagination .page-item.disabled .page-link{
    color:#c3c9d4;
    background:#fff;
    cursor:default;
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ══ Route-name-based URL templates ══
   Laravel resolves the route name to a real URL at page-load time.
   JS then swaps the placeholder for the real numeric ID before firing fetch. */
const bookFeaturedUrlTemplate = "{{ route('books.featured', ['book' => '__BOOK_ID__']) }}";
const bookDestroyUrlTemplate  = "{{ route('books.destroy',  ['book' => '__BOOK_ID__']) }}";

/* ── Init Select2 on the filter dropdowns ── */
$(function(){
    $('.select2-filter').select2({
        width: 'resolve',
        placeholder: function(){ return $(this).data('placeholder'); },
        allowClear: true
    });
});

/* ========================================================
   Front-end pagination — identical pattern to Categories.
   Works on rows already rendered by Blade + respects the
   Category / Author / Type / Status filters and the search box.
   ======================================================== */
(function () {
    const rowSelector    = '.book-row';
    const perPageSelect  = document.getElementById('perPageSelect');
    const searchInput    = document.getElementById('searchInput');
    const typeFilter     = document.getElementById('typeFilter');
    const statusFilter   = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const authorFilter   = document.getElementById('authorFilter');
    const entriesInfo    = document.getElementById('entriesInfo');
    const paginationBox  = document.getElementById('paginationContainer');

    let currentPage = 1;
    let perPage = 10;

    function matches(row) {
        const q = (searchInput.value || '').toLowerCase().trim();
        const type = typeFilter.value;
        const status = statusFilter.value;
        const category = categoryFilter.value;
        const author = authorFilter.value;

        const mq = !q || row.textContent.toLowerCase().includes(q);
        const mt = !type || (row.dataset.type || '').includes(type);
        const ms = !status || row.dataset.status === status;
        const mc = !category || row.dataset.categoryId === category;
        const ma = !author || row.dataset.authorId === author;

        return mq && mt && ms && mc && ma;
    }

    function render() {
        const allRows = Array.from(document.querySelectorAll(rowSelector));
        const visibleRows = allRows.filter(matches);
        const total = visibleRows.length;
        const effectivePerPage = perPage === 'all' ? Math.max(total, 1) : perPage;
        const totalPages = Math.max(1, Math.ceil(total / effectivePerPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * effectivePerPage;
        const end = start + effectivePerPage;

        allRows.forEach(r => r.style.display = 'none');
        visibleRows.forEach((r, i) => {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        visibleRows.slice(start, end).forEach((r, i) => {
            const idxCell = r.querySelector('.row-index');
            if (idxCell) idxCell.textContent = start + i + 1;
        });

        entriesInfo.textContent = total === 0
            ? 'No entries found'
            : `Showing ${start + 1}–${Math.min(end, total)} of ${total} entries`;

        renderPaginationButtons(totalPages);
    }

    function renderPaginationButtons(totalPages) {
        paginationBox.innerHTML = '';
        if (totalPages <= 1) return;

        const ul = document.createElement('ul');
        ul.className = 'pagination';

        function addBtn(label, page, { disabled = false, active = false } = {}) {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.textContent = label;
            if (!disabled && !active) {
                a.addEventListener('click', () => { currentPage = page; render(); });
            }
            li.appendChild(a);
            ul.appendChild(li);
        }

        addBtn('«', currentPage - 1, { disabled: currentPage === 1 });

        const windowSize = 7;
        let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
        let endPage = Math.min(totalPages, startPage + windowSize - 1);
        startPage = Math.max(1, endPage - windowSize + 1);

        for (let p = startPage; p <= endPage; p++) {
            addBtn(p, p, { active: p === currentPage });
        }

        addBtn('»', currentPage + 1, { disabled: currentPage === totalPages });

        paginationBox.appendChild(ul);
    }

    perPageSelect.addEventListener('change', function () {
        perPage = this.value === 'all' ? 'all' : parseInt(this.value, 10);
        currentPage = 1;
        render();
    });

    searchInput.addEventListener('input', function () { currentPage = 1; render(); });
    typeFilter.addEventListener('change', function () { currentPage = 1; render(); });
    statusFilter.addEventListener('change', function () { currentPage = 1; render(); });

    // Select2 fires change through jQuery — use jQuery's .on() for these two
    $('#categoryFilter').on('change', function () { currentPage = 1; render(); });
    $('#authorFilter').on('change', function () { currentPage = 1; render(); });

    window.__bookPaginationRender = render;

    render();
})();

let _tt;
function showToast(msg, type='success'){
    const t=document.getElementById('ajaxToast');
    document.getElementById('toastMsg').textContent=msg;
    document.getElementById('toastIcon').textContent= type==='success'?'✅':'❌';
    t.className='ajax-toast '+(type==='success'?'toast-success':'toast-error');
    t.classList.add('show'); clearTimeout(_tt); _tt=setTimeout(()=>t.classList.remove('show'),4000);
}
function hideToast(){ document.getElementById('ajaxToast').classList.remove('show'); }

document.querySelectorAll('.featured-toggle').forEach(toggle=>{
    toggle.addEventListener('change', function(){
        const id=this.dataset.id;
        const url = bookFeaturedUrlTemplate.replace('__BOOK_ID__', id);
        fetch(url, {method:'PATCH', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json())
        .then(d=>showToast(d.message, d.status==='success'?'success':'error'))
        .catch(()=>{ this.checked=!this.checked; showToast('Something went wrong!','error'); });
    });
});

function deleteBook(id, title){
    if(!confirm(`Delete "${title}"? This cannot be undone.`)) return;
    const url = bookDestroyUrlTemplate.replace('__BOOK_ID__', id);
    fetch(url, {method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='success'){
            document.getElementById(`book-row-${id}`)?.remove();
            showToast(d.message,'success');
            window.__bookPaginationRender && window.__bookPaginationRender();
        } else { showToast(d.message,'error'); }
    })
    .catch(()=>showToast('Something went wrong!','error'));
}
</script>
@endpush