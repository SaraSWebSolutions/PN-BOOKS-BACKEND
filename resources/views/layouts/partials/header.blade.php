{{-- ══════════════════════════════════════════════
     HEADER
═══════════════════════════════════════════════ --}}
@php
    $authUser = Auth::user();
    $authUser->loadMissing(['authorProfile', 'publisherProfile']);
    $canManagePhoto = $authUser->hasRole('author') || $authUser->hasRole('publisher');
@endphp

<header class="nxl-header">
    <div class="header-wrapper" style="display:flex;align-items:center;">

        {{-- ════════ HEADER LEFT ════════ --}}
        <div class="header-left d-flex align-items-center gap-4" style="flex-shrink:0;">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button">
                    <i class="feather-align-left"></i>
                </a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display:none;">
                    <i class="feather-arrow-right"></i>
                </a>
            </div>
            <div class="nxl-lavel-mega-menu-toggle d-flex d-lg-none">
                <a href="javascript:void(0);" id="nxl-lavel-mega-menu-open">
                    <i class="feather-align-left"></i>
                </a>
            </div>
            <div class="nxl-drp-link nxl-lavel-mega-menu">
                <div class="nxl-lavel-mega-menu-toggle d-flex d-lg-none">
                    <a href="javascript:void(0)" id="nxl-lavel-mega-menu-hide">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ════════ HEADER RIGHT ════════ --}}
        <div class="header-right ms-auto" style="flex-shrink:0;">
            <div class="d-flex align-items-center">

                {{-- ── FULLSCREEN ── --}}
                <div class="nxl-h-item d-none d-sm-flex">
                    <div class="full-screen-switcher">
                        <a href="javascript:void(0);" class="nxl-head-link me-0"
                           onclick="$('body').fullScreenHelper('toggle');">
                            <i class="feather-maximize maximize"></i>
                            <i class="feather-minimize minimize"></i>
                        </a>
                    </div>
                </div>

                {{-- ── USER DROPDOWN ── --}}
                <div class="dropdown nxl-h-item">
                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                        @if($authUser->avatar_url)
                            <img src="{{ $authUser->avatar_url }}" alt="user-image"
                                 class="img-fluid user-avtar me-0" id="headerAvatarImg" />
                        @else
                            <span class="user-avtar me-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-light-brand"
                                  id="headerAvatarFallback" style="width:40px;height:40px;">
                                <i class="feather-user text-brand"></i>
                            </span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                @if($authUser->avatar_url)
                                    <img src="{{ $authUser->avatar_url }}" alt="user-image"
                                         class="img-fluid user-avtar" id="headerAvatarImgDropdown" />
                                @else
                                    <span class="user-avtar d-inline-flex align-items-center justify-content-center rounded-circle bg-light-brand"
                                          id="headerAvatarFallbackDropdown" style="width:40px;height:40px;">
                                        <i class="feather-user text-brand"></i>
                                    </span>
                                @endif
                                <div>
                                    <h6 class="text-dark mb-0">
                                        {{ $authUser->name }}
                                        <span class="badge bg-soft-success text-success ms-1">Active</span>
                                    </h6>
                                    <span class="fs-12 fw-medium text-muted">{{ $authUser->email }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- ✅ Opens the profile offcanvas below --}}
                        <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProfile">
                            <i class="feather-user"></i>
                            <span>My Profile</span>
                        </a>

                        <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="feather-log-out"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</header>

{{-- ══════════════════════════════════════════════
     MY PROFILE — right offcanvas
═══════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProfile" style="width:420px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="profileForm" enctype="multipart/form-data">
            @csrf

            {{-- ── Photo ──
                 Only author/publisher have a real photo/logo column to write to
                 (author_profiles.profile_photo / publisher_profiles.logo).
                 Admin/employee accounts have no photo field in this app, so the
                 upload control simply isn't shown for them. --}}
            <div class="text-center mb-4">
                @if($authUser->avatar_url)
                    <img src="{{ $authUser->avatar_url }}" id="profilePhotoPreview"
                         class="rounded-circle mb-2" style="width:90px;height:90px;object-fit:cover;">
                @else
                    <span id="profilePhotoPreviewFallback"
                          class="rounded-circle mb-2 d-inline-flex align-items-center justify-content-center bg-light-brand"
                          style="width:90px;height:90px;">
                        <i class="feather-user fs-30 text-brand"></i>
                    </span>
                    <img src="" id="profilePhotoPreview" class="rounded-circle mb-2 d-none" style="width:90px;height:90px;object-fit:cover;">
                @endif

                @if($canManagePhoto)
                    <div>
                        <label class="btn btn-sm btn-light-brand mb-0">
                            <i class="feather-camera me-1"></i> Change Photo
                            <input type="file" name="photo" id="profilePhotoInput" accept="image/*" class="d-none">
                        </label>
                        @if($authUser->avatar_url)
                            <button type="button" class="btn btn-sm btn-light-danger" id="removePhotoBtn">
                                <i class="feather-trash-2"></i>
                            </button>
                            <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── Basic info ── --}}
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ $authUser->name }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="{{ $authUser->email }}" disabled>
                <div class="form-text fs-11">Email can't be changed here.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ $authUser->phone }}">
            </div>

            <hr class="my-4">

            {{-- ── Password change ── --}}
            <h6 class="fw-semibold mb-3">Change Password</h6>
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" id="currentPasswordInput" class="form-control" autocomplete="current-password">
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" id="newPasswordInput" class="form-control" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-text fs-11 mb-4">Leave password fields blank if you don't want to change it.</div>

            <button type="button" class="btn btn-primary w-100" id="saveProfileBtn" onclick="saveProfileAjax(this)">
                Save Changes
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     Profile mini-toast (independent of any page's own toast,
     so it works even on pages that don't define #ajaxToast)
═══════════════════════════════════════════════ --}}
<div class="profile-toast" id="profileToast">
    <span id="profileToastIcon"></span><span id="profileToastMsg"></span>
    <button type="button" class="profile-toast-close" onclick="document.getElementById('profileToast').classList.remove('show')">✕</button>
</div>

<style>
.profile-toast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
.profile-toast.show{transform:translateX(0);}
.profile-toast.toast-success{background:#d1fae5;border-left:4px solid #10b981;color:#065f46;}
.profile-toast.toast-error{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;}
.profile-toast .profile-toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;opacity:.6;}
.profile-toast .spinner-border-sm{width:1rem;height:1rem;border-width:.15em;}
.profile-toast .spinner-border{display:inline-block;border-radius:50%;border:.25em solid currentColor;border-right-color:transparent;animation:profile-spin .75s linear infinite;}
@keyframes profile-spin{to{transform:rotate(360deg);}}
#saveProfileBtn .spinner-border-sm{width:1rem;height:1rem;border-width:.15em;}
#saveProfileBtn .spinner-border{display:inline-block;border-radius:50%;border:.25em solid currentColor;border-right-color:transparent;animation:profile-spin .75s linear infinite;}
</style>

{{-- ══════════════════════════════════════════════
     Profile JS — lives right here in the header so it
     is available on EVERY page, regardless of which
     page-specific scripts (like _scripts.blade.php) load.
═══════════════════════════════════════════════ --}}
<script>
(function () {

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    let _pt;
    function showProfileToast(msg, type = 'success') {
        const t = document.getElementById('profileToast');
        document.getElementById('profileToastMsg').textContent = msg;
        document.getElementById('profileToastIcon').textContent = type === 'success' ? '✅' : '❌';
        t.className = 'profile-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
        t.classList.add('show');
        clearTimeout(_pt);
        _pt = setTimeout(() => t.classList.remove('show'), type === 'error' ? 6000 : 4000);
    }

    function setProfileButtonLoading(btn, isLoading) {
        if (!btn) return;
        if (isLoading) {
            if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }
    }

    /* ── Photo preview when a new file is chosen ── */
    document.getElementById('profilePhotoInput')?.addEventListener('change', function (e) {
        if (!e.target.files[0]) return;

        const url = URL.createObjectURL(e.target.files[0]);
        const img = document.getElementById('profilePhotoPreview');
        const fallback = document.getElementById('profilePhotoPreviewFallback');

        img.src = url;
        img.classList.remove('d-none');
        if (fallback) fallback.classList.add('d-none');

        const removeFlag = document.getElementById('removePhotoFlag');
        if (removeFlag) removeFlag.value = '0'; // picking a new photo cancels "remove"
    });

    /* ── Remove current photo ── */
    document.getElementById('removePhotoBtn')?.addEventListener('click', function () {
        document.getElementById('removePhotoFlag').value = '1';
        document.getElementById('profilePhotoInput').value = '';

        const img = document.getElementById('profilePhotoPreview');
        const fallback = document.getElementById('profilePhotoPreviewFallback');
        img.classList.add('d-none');
        if (fallback) fallback.classList.remove('d-none');

        showProfileToast('Photo will be removed when you save.', 'success');
    });

    /* ── Save profile (name/phone/photo/password) ── */
    window.saveProfileAjax = async function (btn) {
        const form = document.getElementById('profileForm');
        const newPassword = document.getElementById('newPasswordInput').value;
        const currentPassword = document.getElementById('currentPasswordInput').value;

        if (newPassword && !currentPassword) {
            showProfileToast('Please enter your current password to set a new one.', 'error');
            return;
        }

        const fd = new FormData(form);
        setProfileButtonLoading(btn, true);

        try {
            const res = await fetch('{{ route('profile.update') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();

            if (data.status === 'success') {
                showProfileToast(data.message, 'success');

                // update every avatar spot on the page immediately, no reload needed
                document.querySelectorAll('#headerAvatarImg, #headerAvatarImgDropdown, #profilePhotoPreview').forEach(img => {
                    if (data.avatar_url) {
                        img.src = data.avatar_url;
                        img.classList.remove('d-none');
                    }
                });
                document.querySelectorAll('#headerAvatarFallback, #headerAvatarFallbackDropdown, #profilePhotoPreviewFallback').forEach(el => {
                    if (data.avatar_url) el.classList.add('d-none');
                });

                // clear password fields after a successful save
                document.getElementById('currentPasswordInput').value = '';
                document.getElementById('newPasswordInput').value = '';
                form.querySelector('[name="new_password_confirmation"]').value = '';

                const removeFlag = document.getElementById('removePhotoFlag');
                if (removeFlag) removeFlag.value = '0';

            } else {
                showProfileToast(data.message || 'Could not update profile', 'error');
            }
        } catch (e) {
            showProfileToast('Something went wrong', 'error');
        } finally {
            setProfileButtonLoading(btn, false);
        }
    };

})();
</script>