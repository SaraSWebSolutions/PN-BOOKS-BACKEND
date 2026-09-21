<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ asset('assets/images/PN_Books_logo.png') }}" style="width: 100%;" alt="" class="logo logo-lg" />
                <img src="{{ asset('assets/images/PN_Books_logo_png.png') }}" alt="" class="logo logo-sm" />
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">

                {{-- ══════════════════════ DASHBOARD ══════════════════════ --}}
                <li class="nxl-item">
                    <a href="{{ route('dashboard') }}" class="nxl-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                {{-- ══════════════════════ CATALOG ══════════════════════ --}}
                <li class="nxl-item nxl-menu-caption"><span>Catalog</span></li>

                {{-- Books --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('books.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-book-open"></i></span>
                        <span class="nxl-mtext">Book Catalog</span>
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

                  
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('orders.*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="nxl-link">
        <span class="nxl-micon"><i class="feather-shopping-cart"></i></span>
        <span class="nxl-mtext">Orders</span>
        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>

    <ul class="nxl-submenu">
        <li class="nxl-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('orders.index') }}">
                <i class="feather-list me-2"></i> All Orders
            </a>
        </li>
    </ul>
</li>


                {{-- Categories & Subcategories --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-grid"></i></span>
                        <span class="nxl-mtext">Genres</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('categories.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('categories.index') }}">
                                <i class="feather-list me-2"></i> Genres
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('subcategories.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('subcategories.index') }}">
                                <i class="feather-list me-2"></i> Subcategories
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Series --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('series.index') ? 'active' : '' }}" href="{{ route('series.index') }}">
                        <span class="nxl-micon"><i class="feather-bookmark"></i></span>
                        <span class="nxl-mtext">Series Catalog</span>
                    </a>
                </li>

                {{-- Book Formats --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('book-formats.index') ? 'active' : '' }}" href="{{ route('book-formats.index') }}">
                        <span class="nxl-micon"><i class="feather-layers"></i></span>
                        <span class="nxl-mtext">Book Formats</span>
                    </a>
                </li>

                {{-- Authors --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('authors.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-edit-3"></i></span>
                        <span class="nxl-mtext">Authors</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('authors.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('authors.index') }}">
                                <i class="feather-list me-2"></i> Authors List
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('authors.create') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('authors.create') }}">
                                <i class="feather-user-plus me-2"></i> Add Author
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Publishers --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('publishers.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                        <span class="nxl-mtext">Publishers</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('publishers.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('publishers.index') }}">
                                <i class="feather-list me-2"></i> Publishers List
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('publishers.create') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('publishers.create') }}">
                                <i class="feather-plus-circle me-2"></i> Add Publisher
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ══════════════════════ CONFIGURATION (master/reference data) ══════════════════════ --}}
                <li class="nxl-item nxl-menu-caption"><span>Configuration</span></li>

                {{-- Countries --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('countries.index') ? 'active' : '' }}" href="{{ route('countries.index') }}">
                        <span class="nxl-micon"><i class="feather-globe"></i></span>
                        <span class="nxl-mtext">Countries</span>
                    </a>
                </li>

                {{-- Offers --}}
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('offers.index') ? 'active' : '' }}" href="{{ route('offers.index') }}">
        <span class="nxl-micon"><i class="feather-tag"></i></span>
        <span class="nxl-mtext">Promotions</span>
    </a>
</li>

                {{-- Languages --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('languages.index') ? 'active' : '' }}" href="{{ route('languages.index') }}">
                        <span class="nxl-micon"><i class="feather-message-square"></i></span>
                        <span class="nxl-mtext">Languages</span>
                    </a>
                </li>

                {{-- Currencies --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('currencies.index') ? 'active' : '' }}" href="{{ route('currencies.index') }}">
                        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                        <span class="nxl-mtext">Currencies</span>
                    </a>
                </li>

                {{-- Taxes --}}
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('taxes.index') ? 'active' : '' }}" href="{{ route('taxes.index') }}">
                        <span class="nxl-micon"><i class="feather-percent"></i></span>
                        <span class="nxl-mtext">Taxes</span>
                    </a>
                </li>

                {{-- ══════════════════════ SALES ══════════════════════ --}}
                @can('view_orders sidebar')
                <li class="nxl-item nxl-menu-caption"><span>Sales</span></li>

                {{-- Orders (single entry — no more duplicate under Catalog) --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-shopping-cart"></i></span>
                        <span class="nxl-mtext">Orders</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('orders.index') }}">
                                <i class="feather-list me-2"></i> All Orders
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan

                {{-- Reports --}}
                @can('view_reports sidebar')
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-pie-chart"></i></span>
                        <span class="nxl-mtext">Reports</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('reports.sales') }}">
                                <i class="feather-trending-up me-2"></i> Sales Report
                            </a>
                        </li>
                        @can('view_all_product_items sidebar')
                        <li class="nxl-item {{ request()->routeIs('reports.products') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('reports.products') }}">
                                <i class="feather-package me-2"></i> Product Report
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan

                {{-- ══════════════════════ PEOPLE ══════════════════════ --}}
                <li class="nxl-item nxl-menu-caption"><span>People</span></li>

                {{-- Customers --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-user"></i></span>
                        <span class="nxl-mtext">Customers</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('customers.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('customers.index') }}">
                                <i class="feather-list me-2"></i> Customers List
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Users Management --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-users"></i></span>
                        <span class="nxl-mtext">Users Management</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('users.index') }}">
                                <i class="feather-list me-2"></i> Users List
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('users.create') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('users.create') }}">
                                <i class="feather-user-plus me-2"></i> Create User
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- ══════════════════════ SYSTEM ══════════════════════ --}}
                <li class="nxl-item nxl-menu-caption"><span>System</span></li>

                {{-- Roles & Permissions --}}
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-shield"></i></span>
                        <span class="nxl-mtext">Roles &amp; Permissions</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item {{ request()->routeIs('roles.index') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('roles.index') }}">
                                <i class="feather-list me-2"></i> Roles List
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('roles.create') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('roles.create') }}">
                                <i class="feather-plus-circle me-2"></i> Create Role
                            </a>
                        </li>
                        <li class="nxl-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                            <a class="nxl-link" href="{{ route('permissions.index') }}">
                                <i class="feather-lock me-2"></i> Permissions
                            </a>
                        </li>
                    </ul>
                </li>
               <li class="nxl-item nxl-hasmenu {{ request()->routeIs('faqs.*') || request()->routeIs('support-tickets.*') || request()->routeIs('support-subjects.*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="nxl-link">
        <span class="nxl-micon"><i class="feather-life-buoy"></i></span>
        <span class="nxl-mtext">Website Support</span>
        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>
    <ul class="nxl-submenu">
        <li class="nxl-item {{ request()->routeIs('faqs.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('faqs.index') }}">
                <i class="feather-help-circle me-2"></i> FAQs
            </a>
        </li>
        <li class="nxl-item {{ request()->routeIs('support-subjects.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('support-subjects.index') }}">
                <i class="feather-tag me-2"></i> Support Subjects
            </a>
        </li>
        <li class="nxl-item {{ request()->routeIs('support-tickets.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('support-tickets.index') }}">
                <i class="feather-headphones me-2"></i> Support Tickets
            </a>
        </li>
    </ul>
</li>
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('website.*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="nxl-link">
        <span class="nxl-micon"><i class="feather-layout"></i></span>
        <span class="nxl-mtext">Website Content</span>
        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>

    <ul class="nxl-submenu">
        <li class="nxl-item {{ request()->routeIs('website.index') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('website.index') }}">
                <i class="feather-grid me-2"></i> Pages & Banners
            </a>
        </li>
        <li class="nxl-item {{ request()->routeIs('website.settings') ? 'active' : '' }}">
            <a class="nxl-link" href="{{ route('website.settings') }}">
                <i class="feather-settings me-2"></i> Global Settings
            </a>
        </li>
    </ul>
</li>
                {{-- Logout --}}
                <li class="nxl-item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nxl-link w-100 text-start border-0 bg-transparent text-danger">
                            <span class="nxl-micon"><i class="feather-log-out"></i></span>
                            <span class="nxl-mtext">Logout</span>
                        </button>
                    </form>
                </li>

            </ul>


        </div>
    </div>
</nav>