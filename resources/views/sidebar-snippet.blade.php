{{-- ══════════════════════════════════════════ --}}
{{-- BOOKSTORE — add this block inside the existing            --}}
{{-- @can('view_masters sidebar') ... <ul class="nxl-submenu">  --}}
{{-- block in your nav.blade.php, right after "Property Types"  --}}
{{-- ══════════════════════════════════════════ --}}

{{-- Categories --}}
<li class="nxl-item nxl-hasmenu">
    <a href="javascript:void(0);" class="nxl-link">
        <i class="feather-grid me-2"></i>
        <span class="nxl-mtext">Categories</span>
        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>
    <ul class="nxl-submenu">
        <li class="nxl-item">
            <a class="nxl-link {{ request()->routeIs('categories.index') ? 'active' : '' }}"
               href="{{ route('categories.index') }}">
                <i class="feather-list me-2"></i> Categories
            </a>
        </li>
        <li class="nxl-item">
            <a class="nxl-link {{ request()->routeIs('subcategories.index') ? 'active' : '' }}"
               href="{{ route('subcategories.index') }}">
                <i class="feather-list me-2"></i> Subcategories
            </a>
        </li>
    </ul>
</li>

{{-- Series --}}
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('series.index') ? 'active' : '' }}"
       href="{{ route('series.index') }}">
        <span class="nxl-micon"><i class="feather-bookmark"></i></span>
        <span class="nxl-mtext">Series</span>
    </a>
</li>

{{-- Books --}}
<li class="nxl-item nxl-hasmenu {{ request()->routeIs('books.*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="nxl-link">
        <span class="nxl-micon"><i class="feather-book-open"></i></span>
        <span class="nxl-mtext">Books</span>
        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>
    <ul class="nxl-submenu">
        <li class="nxl-item {{ request()->routeIs('books.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('books.index') }}">
                <i class="feather-list me-2"></i> All Books
            </a>
        </li>
        <li class="nxl-item {{ request()->routeIs('books.create') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('books.create') }}">
                <i class="feather-plus-circle me-2"></i> Add Book
            </a>
        </li>
    </ul>
</li>
