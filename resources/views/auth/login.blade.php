<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
   <title>PN-BOOKS | Login</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css') }}">

    <style>
        /* ── Toast Notification ── */
        .ajax-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 320px;
            max-width: 400px;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .ajax-toast.show {
            transform: translateX(0);
        }

        .ajax-toast.toast-success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .ajax-toast.toast-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .ajax-toast .toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .ajax-toast .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: inherit;
            opacity: 0.6;
            padding: 0 4px;
        }

        .ajax-toast .toast-close:hover {
            opacity: 1;
        }

        /* ── Inline Field Error ── */
        .field-error {
            font-size: 12px;
            color: #ef4444;
            margin-top: 4px;
            display: none;
        }

        .field-error.show {
            display: block;
        }

        /* ── Submit button loading state ── */
        .btn-login {
            position: relative;
            transition: all 0.2s;
        }

        .btn-login.loading {
            opacity: 0.75;
            pointer-events: none;
        }

        .btn-login .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }

        .auth-cover-content-inner {
            flex: 1;
            /* makes it take full left side */
            min-height: 100vh;
            /* ensures full screen height */
            background: url('{{ asset('assets/images/auth/Pn_Books_Login2.jpg.jpeg') }}') no-repeat center center;
            background-size: cover;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-text {
            opacity: 0.8;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Input valid/invalid ── */
        .form-control.input-success {
            border-color: #10b981 !important;
        }

        .form-control.input-error {
            border-color: #ef4444 !important;
        }
        

        
    </style>
</head>

<body>

    {{-- ✅ TOAST CONTAINER --}}
    <div class="ajax-toast" id="ajaxToast">
        <span class="toast-icon" id="toastIcon"></span>
        <span id="toastMessage"></span>
        <button class="toast-close" onclick="hideToast()">✕</button>
    </div>

    <main class="auth-cover-wrapper">

        {{-- LEFT SIDE IMAGE --}}
        <div class="auth-cover-content-inner bg-cover">

        </div>



        {{-- RIGHT SIDE FORM --}}
        <div class="auth-cover-sidebar-inner">
            <div class="auth-cover-card-wrapper">
                <div class="auth-cover-card p-sm-5">

                    {{-- LOGO --}}
                    <div class="wd-50 mb-4">
                        <img src="{{ asset('assets/images/PN_Books_logo_png.png') }}" alt="Logo"  style="width: 100px;height:auto;">
                    </div>

{{-- TITLE --}}
<h2 class="fs-20 fw-bolder mb-1">Welcome Back! 📚</h2>

<p class="fs-12 fw-medium text-muted mb-4">
    Sign in to PUSTAKA NASIONAL
</p>


                    {{-- Session logout success --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="feather-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- ✅ AJAX LOGIN FORM --}}
                    <form id="loginForm" class="w-100" novalidate>
                        @csrf

                        {{-- Email Field --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" id="emailInput" class="form-control"
                                placeholder="admin@gmail.com" autofocus>
                            <div class="field-error" id="emailError"></div>
                        </div>

                        {{-- Password Field --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="passwordInput" class="form-control"
                                    placeholder="Enter your password">
                                <span class="input-group-text c-pointer" onclick="togglePassword()">
                                    <i class="feather-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="field-error" id="passwordError"></div>
                        </div>

                        {{-- Remember Me --}}
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label fs-12" for="remember">Remember Me</label>
                            </div>
                            {{-- <a href="#" class="fs-12 text-primary fw-semibold">Forgot Password?</a> --}}
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" class="btn btn-primary w-100 btn-lg btn-login" id="loginBtn">
                            <span class="spinner" id="btnSpinner"></span>
                            <span class="btn-text">
                                <i class="feather-log-in me-2"></i> Sign In
                            </span>
                        </button>

                    </form>

                    <div class="mt-4 text-center">
                        <p class="fs-12 text-muted">
                          PUSTAKA NASIONAL  &copy; {{ date('Y') }}
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>

    <script>
        // ─────────────────────────────────────────────────
        // ✅ TOAST FUNCTIONS
        // ─────────────────────────────────────────────────
        let toastTimer = null;

        function showToast(message, type = 'success') {
            const toast = document.getElementById('ajaxToast');
            const msgEl = document.getElementById('toastMessage');
            const iconEl = document.getElementById('toastIcon');

            // Set content
            msgEl.textContent = message;
            toast.className = 'ajax-toast';

            if (type === 'success') {
                toast.classList.add('toast-success');
                iconEl.textContent = '✅';
            } else {
                toast.classList.add('toast-error');
                iconEl.textContent = '❌';
            }

            // Show
            toast.classList.add('show');

            // Auto-hide after 4s
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => hideToast(), 4000);
        }

        function hideToast() {
            const toast = document.getElementById('ajaxToast');
            toast.classList.remove('show');
        }

        // ─────────────────────────────────────────────────
        // ✅ CLEAR FIELD ERRORS
        // ─────────────────────────────────────────────────
        function clearErrors() {
            ['emailInput', 'passwordInput'].forEach(id => {
                const el = document.getElementById(id);
                el.classList.remove('input-error', 'input-success');
            });
            ['emailError', 'passwordError'].forEach(id => {
                const el = document.getElementById(id);
                el.textContent = '';
                el.classList.remove('show');
            });
        }

        // Show single field error
        function showFieldError(fieldId, errorId, message) {
            const field = document.getElementById(fieldId);
            const error = document.getElementById(errorId);
            field.classList.add('input-error');
            error.textContent = message;
            error.classList.add('show');
        }

        // Mark field as valid
        function markValid(fieldId) {
            const field = document.getElementById(fieldId);
            field.classList.remove('input-error');
            field.classList.add('input-success');
        }

        // ─────────────────────────────────────────────────
        // ✅ TOGGLE PASSWORD SHOW/HIDE
        // ─────────────────────────────────────────────────
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('feather-eye', 'feather-eye-off');
            } else {
                input.type = 'password';
                icon.classList.replace('feather-eye-off', 'feather-eye');
            }
        }

        // ─────────────────────────────────────────────────
        // ✅ AJAX FORM SUBMIT
        // ─────────────────────────────────────────────────
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // stop normal form submit

            clearErrors();

            const btn = document.getElementById('loginBtn');
            const email = document.getElementById('emailInput').value.trim();
            const password = document.getElementById('passwordInput').value.trim();
            const remember = document.getElementById('remember').checked;
            const csrf = document.querySelector('input[name="_token"]').value;

            // ── Client-side validation ──
            let hasError = false;
            if (!email) {
                showFieldError('emailInput', 'emailError', 'Email address is required.');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('emailInput', 'emailError', 'Please enter a valid email address.');
                hasError = true;
            }
            if (!password) {
                showFieldError('passwordInput', 'passwordError', 'Password is required.');
                hasError = true;
            } else if (password.length < 6) {
                showFieldError('passwordInput', 'passwordError', 'Password must be at least 6 characters.');
                hasError = true;
            }
            if (hasError) return;

            // ── Show loading state ──
            btn.classList.add('loading');

            // ── AJAX Request ──
            fetch("{{ route('login.post') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        remember: remember,
                    }),
                })
                .then(res => res.json().then(data => ({
                    status: res.status,
                    data
                })))
                .then(({
                    status,
                    data
                }) => {
                    btn.classList.remove('loading');

                    if (data.status === 'success') {
                        // ✅ Mark fields valid
                        markValid('emailInput');
                        markValid('passwordInput');

                        // ✅ Show success toast
                        showToast(data.message, 'success');

                        // ✅ Redirect after 1.5s
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);

                    } else if (data.errors) {
                        // ✅ Show field-level validation errors
                        if (data.errors.email) {
                            showFieldError('emailInput', 'emailError', data.errors.email[0]);
                        }
                        if (data.errors.password) {
                            showFieldError('passwordInput', 'passwordError', data.errors.password[0]);
                        }

                    } else {
                        // ✅ Show wrong credentials toast
                        showToast(data.message || 'Login failed. Please try again.', 'error');
                        showFieldError('emailInput', 'emailError', ' ');
                        showFieldError('passwordInput', 'passwordError', ' ');
                    }
                })
                .catch(err => {
                    btn.classList.remove('loading');
                    showToast('Something went wrong. Please try again.', 'error');
                    console.error('Login error:', err);
                });
        });

        // ─────────────────────────────────────────────────
        // Clear error on typing
        // ─────────────────────────────────────────────────
        document.getElementById('emailInput').addEventListener('input', function() {
            this.classList.remove('input-error');
            const err = document.getElementById('emailError');
            err.textContent = '';
            err.classList.remove('show');
        });

        document.getElementById('passwordInput').addEventListener('input', function() {
            this.classList.remove('input-error');
            const err = document.getElementById('passwordError');
            err.textContent = '';
            err.classList.remove('show');
        });
    </script>

</body>

</html>
