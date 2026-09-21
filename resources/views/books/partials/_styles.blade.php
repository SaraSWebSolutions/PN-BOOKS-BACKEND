<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<style>
.book-wizard-tabs{ display:flex; overflow-x:auto; padding:0 20px; }
.book-wizard-tabs .nav-link{ border:none; background:none; padding:16px 20px; font-size:13px; font-weight:600; color:#8a94a6; border-bottom:2px solid transparent; display:flex; align-items:center; gap:8px; white-space:nowrap; }
.book-wizard-tabs .nav-link.active{ color:#7b5cf0; border-bottom-color:#7b5cf0; }
.book-wizard-tabs .step-num{ width:22px; height:22px; border-radius:50%; background:#eef0f4; color:#8a94a6; font-size:11px; display:flex; align-items:center; justify-content:center; }
.book-wizard-tabs .nav-link.active .step-num{ background:#7b5cf0; color:#fff; }
.format-card{ border-radius:12px; }
.format-card.is-enabled{ border-color:#7b5cf0!important; background:#faf9ff; }
:root{ --select2-accent:#7b5cf0; --select2-accent-soft:#f2eeff; }
.select2-container{ width:100% !important; }
.select2-container--default .select2-selection--single{ height:44px; border:1px solid #e2e5ec; border-radius:10px; display:flex; align-items:center; padding:0 14px; }
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single{ border-color:var(--select2-accent); box-shadow:0 0 0 3px rgba(123,92,240,.12); }
.pricing-format-block,.inv-format-block,.file-format-block{ border:1px solid #e2e5ec; border-radius:12px; margin-bottom:18px; overflow:hidden; }
.pricing-format-header,.inv-format-header,.file-format-header{ background:#f8f9fb; padding:12px 16px; font-weight:600; display:flex; align-items:center; gap:8px; }

.format-icon-box{ width:40px; height:40px; border-radius:10px; background:#f2eeff; color:#7b5cf0; display:flex; align-items:center; justify-content:center; font-size:18px; }
.format-badge{ font-size:11px; font-weight:600; padding:2px 8px; border-radius:6px; }
.badge-disabled{ background:#eef0f4; color:#8a94a6; }
.badge-enabled{ background:#e8f9f0; color:#10b981; }
.file-format-header .badge{ font-size:11px; font-weight:600; }
#filesContainer .table th{ font-size:11px; text-transform:uppercase; color:#8a94a6; font-weight:600; }

.btn-quick-add{ padding:2px 8px; border:1px solid #e2e5ec; border-radius:6px; background:#f8f9fb; color:#7b5cf0; line-height:1; }
.btn-quick-add:hover{ background:#f2eeff; }
.offcanvas{ width:400px; }
.spinner-border-sm{width:1rem;height:1rem;border-width:.15em;}
.spinner-border{display:inline-block;border-radius:50%;border:.25em solid currentColor;border-right-color:transparent;animation:spinner-border .75s linear infinite;}
@keyframes spinner-border{to{transform:rotate(360deg);}}

/* ── Processing overlay: only ever triggered from Files & DRM / Media tabs now ── */
.processing-overlay{
    position:fixed; inset:0; z-index:10000;
    background:rgba(255,255,255,.85); backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center;
}
.processing-overlay.d-none{ display:none; }
.processing-box{
    text-align:center; background:#fff; padding:32px 44px;
    border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,.15);
}

/* ── EPUB inline error box (lives inside tab-files now, no more popup/modal) ── */
.epub-inline-error{ margin-top:16px; }
.epub-inline-error.d-none{ display:none !important; }
.epub-inline-error-card{ background:#fff5f5; border:1px solid #f5c2c7; border-radius:12px; padding:16px; }
.epub-error-item{ background:#fff5f5; }
.epub-error-item .badge{ font-weight:500; }





/* ── ISBN validation card — now takes real space in the layout (not
     absolutely positioned), so it pushes the Subcategory field down
     instead of overlapping it. Animates open/closed via max-height. */
/* max-height is now set dynamically via JS (scrollHeight) in showIsbnPopup(),
   not a fixed guess — this is what stops clipping/overlap on longer messages. */
.isbn-popup-wrap{
    max-height:0;
    overflow:hidden;
    transition:max-height .25s ease;
}
.isbn-popup{
    display:flex; align-items:center; gap:8px;
    margin-top:6px; padding:9px 12px; border-radius:10px;
    background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.08);
    font-size:12.5px; font-weight:500; line-height:1.35;
}
.isbn-popup-icon{ display:flex; align-items:center; font-size:15px; flex-shrink:0; }
.isbn-popup-text{ flex:1; }

.isbn-popup-loading{ border-left:3px solid #9ca3af; color:#374151; }
.isbn-popup-loading .isbn-popup-icon{ color:#9ca3af; }

.isbn-popup-success{ border-left:3px solid #10b981; color:#065f46; }
.isbn-popup-success .isbn-popup-icon{ color:#10b981; }

.isbn-popup-error{ border-left:3px solid #ef4444; color:#991b1b; }
.isbn-popup-error .isbn-popup-icon{ color:#ef4444; }

.isbn-popup-info{ border-left:3px solid #6366f1; color:#3730a3; }
.isbn-popup-info .isbn-popup-icon{ color:#6366f1; }

.final-price-cell.text-success{
    color: #10b981 !important;
}

.book-header-row{
    display:flex;
    align-items:stretch;   /* both children fill full header height */
}

.book-title-banner{
    display:flex;
    align-items:center;         /* vertical-center icon+text */
    gap:8px;
    padding:16px 20px;          /* ⬅ SAME as .book-wizard-tabs .nav-link padding */
    font-size:14px;
    font-weight:700;
    color:#374151;
    white-space:nowrap;
    flex-shrink:0;
    align-self:stretch;         /* ⬅ makes the border-right span full height */
    border-right:1px solid #e2e5ec;   /* the divider line, now full-height */
}

.book-title-banner i{
    font-size:16px;
    color:#7b5cf0;
}

.book-title-banner.is-empty{
    color:#9ca3af;
    font-style:italic;
}

/* tabs row should not add its own extra height beside the banner */
.book-wizard-tabs{
    align-items:stretch;
    flex:1;
}

</style>