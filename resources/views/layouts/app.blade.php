{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PN-BOOKS')</title>

    {{-- Bootstrap CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}" />

    {{-- Vendors CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/daterangepicker.min.css') }}" />

    {{-- Theme CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap"
      rel="stylesheet"
    />

    {{-- Extra page-specific CSS --}}
    @stack('styles')
</head>

<body>


    <style>
        .book-wizard-tabs .nav-link.active {
    color:#162556 !important;
    border-bottom-color: #162556 !important;
}

.book-wizard-tabs .nav-link.active .step-num {
    background: #162556 !important;
    color: #fff;
}

body {
   font-family: "Rubik", sans-serif !important;
}
    </style>
    {{-- ✅ SIDEBAR --}}
    @include('layouts.partials.sidebar')

    {{-- ✅ HEADER --}}
    @include('layouts.partials.header')

    {{-- ✅ MAIN CONTENT --}}
    <main class="nxl-container">
        <div class="nxl-content">

            
            @yield('content')

        </div>

       
        {{-- ✅ FOOTER --}}
              @include('layouts.partials.footer')
       
    </main>


    {{-- Vendors JS --}}
    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/circle-progress.min.js') }}"></script>

    {{-- App JS --}}
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>

    {{-- Extra page-specific JS --}}
    @stack('scripts')

</body>
</html>