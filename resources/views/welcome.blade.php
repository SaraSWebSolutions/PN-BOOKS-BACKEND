{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Saras Jewellery')</title>

    {{-- Bootstrap CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}" />

    {{-- Vendors CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/daterangepicker.min.css') }}" />

    {{-- Theme CSS --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}" />

    {{-- Extra page-specific CSS --}}
    @stack('styles')
</head>

<body>

    {{-- ✅ SIDEBAR --}}
    @include('layouts.partials.sidebar')

    {{-- ✅ HEADER --}}
    @include('layouts.partials.header')

    {{-- ✅ MAIN CONTENT --}}
    <main class="nxl-container">
        <div class="nxl-content">

            {{-- Page content goes here --}}
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