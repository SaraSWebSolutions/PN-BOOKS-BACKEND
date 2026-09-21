<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script>




const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const COUNTRIES = JSON.parse(document.getElementById('countriesData').textContent);
const FORMATS   = JSON.parse(document.getElementById('formatsData').textContent);

/* ══ Route-name-based URL templates ══
   Laravel resolves the route name to a real URL at page-load time.
   JS then swaps the placeholder for the real numeric ID before firing fetch. */
const categorySubcategoriesUrlTemplate = "{{ route('categories.subcategories', ['category' => '__CATEGORY_ID__']) }}";
const publisherSeriesUrlTemplate       = "{{ route('publishers.series', ['publisher' => '__PUBLISHER_ID__']) }}";
const bookMediaUrlTemplate             = "{{ route('books.media.store', ['book' => '__BOOK_ID__']) }}";
const bookRelatedUrlTemplate           = "{{ route('books.related.store', ['book' => '__BOOK_ID__']) }}";
const galleryDeleteUrlTemplate         = "{{ route('books.gallery.delete', ['image' => '__IMAGE_ID__']) }}";
const bookSearchSelectUrl              = "{{ route('books.searchSelect') }}";
const bookFileDeleteUrlTemplate     = "{{ route('books.files.destroy', ['bookFile' => '__FILE_ID__']) }}";
const chapterAudioDeleteUrlTemplate = "{{ route('books.chapters.audio.destroy', ['chapter' => '__CHAPTER_ID__']) }}";
/* Wizard tab-save endpoints — 'basic' has 2 routes (with/without book id), others always need book id */
const bookTabRouteTemplates = {
    basic: {
        withId:    "{{ route('books.basic.update',   ['book' => '__BOOK_ID__']) }}",
        withoutId: "{{ route('books.basic.store') }}",
    },
    formats:   "{{ route('books.formats.store',   ['book' => '__BOOK_ID__']) }}",
    files:     "{{ route('books.files.store',     ['book' => '__BOOK_ID__']) }}",
    pricing:   "{{ route('books.pricing.store',   ['book' => '__BOOK_ID__']) }}",
    inventory: "{{ route('books.inventory.store', ['book' => '__BOOK_ID__']) }}",
    shipping:  "{{ route('books.shipping.store',  ['book' => '__BOOK_ID__']) }}",
    seo:       "{{ route('books.seo.store',       ['book' => '__BOOK_ID__']) }}",
    publish:   "{{ route('books.publish',         ['book' => '__BOOK_ID__']) }}",
};

/* Quick-create endpoints — static, no dynamic ID needed */
const quickCreateUrls = {
    category:    "{{ route('quick.category') }}",
    subcategory: "{{ route('quick.subcategory') }}",
    series:      "{{ route('quick.series') }}",
    author:      "{{ route('quick.author') }}",
    publisher:   "{{ route('quick.publisher') }}",
};

$(function(){ $('.select2-field').select2({ width:'100%', placeholder:function(){return $(this).data('placeholder');}, allowClear:true }); });

/* ── Toast ── */
let _tt;
function showToast(msg, type='success'){
    const t=document.getElementById('ajaxToast');
    document.getElementById('toastMsg').textContent=msg;
    document.getElementById('toastIcon').textContent= type==='success'?'✅':'❌';
    t.className='ajax-toast '+(type==='success'?'toast-success':'toast-error');
    t.classList.add('show'); clearTimeout(_tt); _tt=setTimeout(()=>t.classList.remove('show'), type==='error' ? 6000 : 4000);
}
function hideToast(){ document.getElementById('ajaxToast').classList.remove('show'); }

/* ── Center-screen processing overlay ──
   Shown while a tab save is in flight. Used by tabs that upload real files
   (Files & DRM, Media) AND now also by the ISBN check, so the user gets the
   same clear "something is happening" feedback while we validate ISBN
   against external catalogs.
   hideProcessingOverlay() forces BOTH the class and inline style off, so it
   can never linger behind anything due to a CSS specificity/z-index mixup. */
function showProcessingOverlay(text, subtext){
    const overlay = document.getElementById('processingOverlay');
    if (!overlay) return;
    document.getElementById('processingText').textContent = text || 'Processing...';
    document.getElementById('processingSubtext').textContent = subtext || 'Please wait a moment';
    overlay.classList.remove('d-none');
    overlay.style.display = 'flex';
}
function hideProcessingOverlay(){
    const overlay = document.getElementById('processingOverlay');
    if (!overlay) return;
    overlay.classList.add('d-none');
    overlay.style.display = 'none';
}

/* ── EPUB validation error box (INLINE inside #tab-files, not a Bootstrap modal) ──
   Shows every error returned by the backend's epubcheck run. Only the first
   few are visible at first; "Show all errors" reveals the rest. Living inside
   the tab (which starts d-none) means it can never flash on page load. */
const EPUB_ERROR_PREVIEW_COUNT = 3;

function escapeHtml(str){
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function showEpubErrorModal(message, errors){
    // Always force the processing overlay off before showing the error box.
    hideProcessingOverlay();

    document.getElementById('epubErrorSummary').textContent = message || 'The uploaded EPUB has validation errors.';
    document.getElementById('epubErrorCount').textContent = `${errors.length} issue${errors.length === 1 ? '' : 's'} found`;

    const list = document.getElementById('epubErrorList');
    list.innerHTML = '';

    errors.forEach((err, idx) => {
        const isExtra = idx >= EPUB_ERROR_PREVIEW_COUNT;
        list.insertAdjacentHTML('beforeend', `
            <div class="epub-error-item border rounded-3 p-2 mb-2 ${isExtra ? 'd-none epub-error-extra' : ''}">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-light-danger text-danger">${escapeHtml(err.id || 'ERROR')}</span>
                    <span class="badge bg-light text-muted text-uppercase">${escapeHtml(err.severity || 'error')}</span>
                </div>
                <div class="fw-medium">${escapeHtml(err.message)}</div>
                ${err.location ? `<div class="fs-12 text-muted mt-1">${escapeHtml(err.location.path || '')}${err.location.line ? ' : line ' + err.location.line : ''}</div>` : ''}
            </div>`);
    });

    const showMoreWrap = document.getElementById('epubShowMoreWrap');
    const showMoreBtn  = document.getElementById('epubShowMoreBtn');

    if (errors.length > EPUB_ERROR_PREVIEW_COUNT) {
        showMoreWrap.classList.remove('d-none');
        showMoreBtn.textContent = `Show all ${errors.length} errors`;
        showMoreBtn.onclick = function(){
            document.querySelectorAll('.epub-error-extra').forEach(el => el.classList.remove('d-none'));
            showMoreWrap.classList.add('d-none');
        };
    } else {
        showMoreWrap.classList.add('d-none');
    }

    // Make sure we're on the tab holding the box, then reveal + scroll to it.
    goToTab('tab-files');
    const box = document.getElementById('epubErrorBox');
    box.classList.remove('d-none');
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideEpubErrorBox(){
    document.getElementById('epubErrorBox')?.classList.add('d-none');
}


/* ══════════════════════════════════════════════════════════════════════
 * ISBN LOOKUP — goes through our own backend (books.isbn.check) so it
 * isn't affected by the browser's own network/firewall path to openlibrary.org.
 *
 * UPDATED:
 * - Backend now tries 3 sources + caches results, so checks are more
 *   reliable and don't keep re-hitting rate-limited APIs (see PHP file).
 * - The response's `isbn` field is now shown inside the popup message,
 *   so the user can see exactly which ISBN was validated.
 * - The same center-screen "processing overlay" used by Files & DRM /
 *   Media now also shows while the ISBN check is in flight, so the user
 *   gets the same clear "validating..." indication for ISBN checks too.
 * ══════════════════════════════════════════════════════════════════════ */
const bookIsbnCheckUrlTemplate = "{{ route('books.isbn.check', ['isbn' => '__ISBN__']) }}";

let _isbnCheckToken = 0;
let _isbnPopupTimer = null;
let isbnValidationState = { value: null, valid: null }; // valid: null=unchecked/unknown, true, false

function showIsbnPopup(message, type){
    const popup = document.getElementById('isbnPopup');
    const wrap  = document.getElementById('isbnPopupWrap');
    if (!popup || !wrap) return;

    clearTimeout(_isbnPopupTimer);

    const icons = {
        loading: `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`,
        success: `<i class="feather-check-circle"></i>`,
        error:   `<i class="feather-x-circle"></i>`,
        info:    `<i class="feather-alert-circle"></i>`,
    };

    popup.innerHTML = `
        <span class="isbn-popup-icon">${icons[type] || ''}</span>
        <span class="isbn-popup-text">${message}</span>`;
    popup.className = 'isbn-popup isbn-popup-' + type;
    wrap.classList.add('show');

    // ✅ Auto-height: measure the actual rendered content instead of guessing
    // a fixed px value. This is what fixes the clipping/overlap — 1-line and
    // 2-line messages both fit exactly, every time.
    requestAnimationFrame(() => {
        wrap.style.maxHeight = popup.scrollHeight + 12 + 'px';
    });

    if (type === 'loading') return; // stays open until the result replaces it

    const hideAfter = type === 'error' ? 5000 : 3000;
    _isbnPopupTimer = setTimeout(() => {
        wrap.classList.remove('show');
        wrap.style.maxHeight = '0px';
    }, hideAfter);
}

async function checkIsbnAvailability(rawValue){
    const isbn = (rawValue || '').trim().replace(/[-\s]/g, '');
    if (!isbn){
        isbnValidationState = { value: '', valid: null };
        return;
    }

    if (![10, 13].includes(isbn.length)){
        showIsbnPopup(`ISBN ${isbn} must be 10 or 13 digits.`, 'error');
        isbnValidationState = { value: isbn, valid: false };
        return;
    }

    const token = ++_isbnCheckToken;
    const url = bookIsbnCheckUrlTemplate.replace('__ISBN__', encodeURIComponent(isbn));

    // Small in-line popup (as before)...
    showIsbnPopup(`Checking ISBN ${isbn}…`, 'loading');
    // ...PLUS the center-screen overlay, same one used for file uploads,
    // so the user gets the loader design shown in the screenshot for ISBN
    // checks too.
    showProcessingOverlay('Validating ISBN…', `Checking ${isbn} against external catalogs`);

    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (token !== _isbnCheckToken) return;

        const shownIsbn = data.isbn || isbn;

        if (data.status === 'valid'){
            showIsbnPopup(data.title ? `Valid ISBN ${shownIsbn} — matched "${data.title}"` : `Valid ISBN ${shownIsbn}`, 'success');
            isbnValidationState = { value: isbn, valid: true };
        } else if (data.status === 'invalid'){
            showIsbnPopup(data.message || `ISBN ${shownIsbn} was not found. Please double-check it.`, 'error');
            isbnValidationState = { value: isbn, valid: false };
        } else if (data.status === 'checksum_only'){
            // Structurally valid ISBN, but neither OpenLibrary nor Google
            // Books could confirm it right now (rate-limited/unreachable).
            // We don't block the user for this — the number itself is real.
            showIsbnPopup(data.message || `ISBN ${shownIsbn} format is valid. Could not confirm against external catalogs right now, but you can continue.`, 'info');
            isbnValidationState = { value: isbn, valid: true };
        } else {
            showIsbnPopup(`Could not verify ISBN ${shownIsbn} right now — you can still continue.`, 'info');
            isbnValidationState = { value: isbn, valid: null };
        }
    } catch (e){
        if (token !== _isbnCheckToken) return;
        showIsbnPopup(`Could not verify ISBN ${isbn} right now — you can still continue.`, 'info');
        isbnValidationState = { value: isbn, valid: null };
    } finally {
        if (token === _isbnCheckToken) hideProcessingOverlay();
    }
}




document.getElementById('isbn_field')?.addEventListener('blur', function(){
    checkIsbnAvailability(this.value);
});

/* ── Basic Info save wrapper ──
   Checks the two plain required fields (ISBN, Description) via native
   reportValidity(), then confirms the ISBN actually resolved on OpenLibrary
   (through our backend) before saving. An "unknown" (network-down) result
   does NOT block save — only a confirmed "not found" does. */
async function saveBasicInfoAjax(btn){
    const isbnInput = document.getElementById('isbn_field');
    const descInput = document.getElementById('description_field');

    if (!isbnInput.reportValidity()){ isbnInput.focus(); return; }
    if (!descInput.reportValidity()){ descInput.focus(); return; }

    const currentIsbn = (isbnInput.value || '').trim().replace(/[-\s]/g, '');
    if (isbnValidationState.value !== currentIsbn){
        await checkIsbnAvailability(currentIsbn);
    }

    if (isbnValidationState.valid === false){
        isbnInput.focus();
        return; // the popup itself is already showing the error, no need for a second toast
    }

    saveTabAjax('basic', 'tab-formats', btn);
}

/* ── Button loading state (spinner) ──
   Used by every "Save & Continue" button across all tabs so the user gets
   clear feedback that their click registered and the request is in flight. */
function setButtonLoading(btn, isLoading) {
    if (!btn) return;

    if (isLoading) {
        // remember the button's original label so we can restore it exactly
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...`;
    } else {
        btn.disabled = false;
        if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    }
}

/* ── Tabs ── */
function goToTab(tabId){
    document.querySelectorAll('.tab-pane').forEach(el=>el.classList.add('d-none'));
    document.getElementById(tabId).classList.remove('d-none');
    document.querySelectorAll('#bookTabs .nav-link').forEach(el=>el.classList.remove('active'));
    document.querySelector(`#bookTabs .nav-link[data-tab="${tabId}"]`).classList.add('active');
}
document.querySelectorAll('#bookTabs .nav-link').forEach(btn=>btn.addEventListener('click',()=>goToTab(btn.dataset.tab)));

/* ── Category → Subcategory / Publisher → Series (jQuery .on for Select2) ── */
$('#category_id').on('change', function(){
    const id = $(this).val();
    const $sub = $('#subcategory_id');
    $sub.empty().append('<option value=""></option>');
    if(!id){ $sub.trigger('change'); return; }
    const url = categorySubcategoriesUrlTemplate.replace('__CATEGORY_ID__', id);
    fetch(url)
        .then(r => r.json())
        .then(d => {
            d.forEach(s => $sub.append(`<option value="${s.id}">${s.name}</option>`));
            $sub.trigger('change');
        })
        .catch(err => console.error('Subcategory fetch failed:', err));
});

$('#publisher_id').on('change', function(){
    const id = $(this).val();
    const $s = $('#series_id');
    $s.empty().append('<option value=""></option>');
    if(!id){ $s.trigger('change'); return; }
    const url = publisherSeriesUrlTemplate.replace('__PUBLISHER_ID__', id);
    fetch(url)
        .then(r => r.json())
        .then(d => {
            d.forEach(s => $s.append(`<option value="${s.id}">${s.name}</option>`));
            $s.trigger('change');
        })
        .catch(err => console.error('Series fetch failed:', err));
});

/* ── Cover preview ── */
document.getElementById('cover_image')?.addEventListener('change', function(e){
    if(e.target.files[0]) document.getElementById('coverPreview').src = URL.createObjectURL(e.target.files[0]);
});

/* ── Format toggle show/hide fields + badge swap ── */
document.addEventListener('change', function(e){
    if(e.target.classList.contains('format-toggle')){
        const target = document.getElementById(e.target.dataset.target);
        const card = e.target.closest('.format-card');
        target.classList.toggle('d-none', !e.target.checked);
        card.classList.toggle('is-enabled', e.target.checked);

        const badgeDisabled = card.querySelector('.badge-disabled');
        const badgeEnabled  = card.querySelector('.badge-enabled');
        if (badgeDisabled) badgeDisabled.classList.toggle('d-none', e.target.checked);
        if (badgeEnabled)  badgeEnabled.classList.toggle('d-none', !e.target.checked);
    }
});

function getCheckedFormats(){ return Array.from(document.querySelectorAll('.format-toggle:checked')).map(el=>parseInt(el.value)); }

function buildAllDynamicTabs(){ buildFilesTab(); buildPricingTable(); buildInventoryTab(); }

let chapterIdx = 0;

function buildFilesTab(){
    const checked = getCheckedFormats(), c = document.getElementById('filesContainer');
    const digital = checked.filter(id => { const f=FORMATS.find(x=>x.id===id); return f && !f.requires_shipping; });

    if(digital.length===0){
        c.innerHTML='<div class="text-center text-muted py-4"><i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>Enable eBook or Audiobook in <strong>Formats</strong> to upload files here.</div>';
        return;
    }
    c.innerHTML='';

    digital.forEach(id=>{
        const f = FORMATS.find(x=>x.id===id);
        const codeOrName = `${f.code || ''} ${f.name || ''}`.toLowerCase();

        if(codeOrName.includes('audio')){
            c.insertAdjacentHTML('beforeend', buildAudiobookBlock(id, f));
        } else if(codeOrName.includes('ebook') || codeOrName.includes('e-book') || codeOrName.includes('e_book')){
            c.insertAdjacentHTML('beforeend', buildEbookBlock(id, f));
        } else {
            c.insertAdjacentHTML('beforeend', `
            <div class="file-format-block">
                <div class="file-format-header"><i class="${f.icon}"></i> ${f.name}</div>
                <div class="p-3 row g-3">
                    <div class="col-md-4"><label class="form-label fs-12">Main File</label><input type="file" class="form-control form-control-sm" name="files[${id}][epub]"></div>
                </div>
            </div>`);
        }
    });

    document.querySelectorAll('.chapter-add-btn').forEach(btn=>{
        btn.addEventListener('click', function(){ addChapterRow(this.dataset.formatId); });
    });
}

function buildEbookBlock(id, f){
    return `
    <div class="file-format-block">
        <div class="file-format-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="${f.icon}"></i> ${f.name}
                <span class="badge bg-light-success text-success ms-2">Enabled</span>
            </div>
        </div>
        <div class="p-3">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fs-12">Format</label>
                    <select class="form-control form-control-sm" name="settings[${id}][ebook_format]">
                        <option value="epub">EPUB</option>
                        <option value="pdf">PDF</option>
                        <option value="epub_pdf" selected>EPUB + PDF</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">DRM Protection</label>
                    <select class="form-control form-control-sm" name="settings[${id}][drm]">
                        <option value="lcp" selected>LCP DRM</option>
                        <option value="watermark">Watermark</option>
                        <option value="none">No DRM</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">DRM Level</label>
                    <select class="form-control form-control-sm" name="settings[${id}][drm_level]">
                        <option value="standard">Level 1 (Standard)</option>
                        <option value="strict">Level 2 (Strict)</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fs-12">EPUB File</label>
                    <input type="file" class="form-control form-control-sm" name="files[${id}][epub]" accept=".epub">
                </div>
                <div class="col-md-4 d-none">
                    <label class="form-label fs-12">PDF File</label>
                    <input type="file" class="form-control form-control-sm" name="files[${id}][pdf]" accept=".pdf">
                </div>
                <div class="col-md-4 d-none">
                    <label class="form-label fs-12">Cover / Preview</label>
                    <input type="file" class="form-control form-control-sm" name="files[${id}][cover_preview]" accept=".epub,.pdf">
                    <div class="form-text fs-11">This preview will be visible to users.</div>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-12">Download Option</label>
                    <select class="form-control form-control-sm" name="settings[${id}][download_option]">
                        <option value="reader_only" selected>Reader Only</option>
                        <option value="downloadable">Downloadable</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-12">Download Limit (per user)</label>
                    <input type="number" class="form-control form-control-sm" name="settings[${id}][download_limit]" value="2" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-12">Access Period</label>
                    <select class="form-control form-control-sm" name="settings[${id}][access_period]">
                        <option value="unlimited" selected>Unlimited</option>
                        <option value="1_year">1 Year</option>
                        <option value="6_months">6 Months</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="settings[${id}][allow_printing]" id="allow_print_${id}" checked>
                        <label class="form-check-label fs-13" for="allow_print_${id}">Allow users to print</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="settings[${id}][allow_copy]" id="allow_copy_${id}">
                        <label class="form-check-label fs-13" for="allow_copy_${id}">Allow text copy</label>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function buildAudiobookBlock(id, f){
    return `
    <div class="file-format-block">
        <div class="file-format-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="${f.icon}"></i> ${f.name}
                <span class="badge bg-light-success text-success ms-2">Enabled</span>
            </div>
        </div>
        <div class="p-3">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fs-12">Audio Format</label>
                    <select class="form-control form-control-sm" name="settings[${id}][audio_format]">
                        <option value="mp3" selected>MP3</option>
                        <option value="m4b">M4B</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">Audio Quality</label>
                    <select class="form-control form-control-sm" name="settings[${id}][audio_quality]">
                        <option value="128">128 kbps</option>
                        <option value="192">192 kbps</option>
                        <option value="320">320 kbps</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-12">Sample Preview</label>
                    <input type="file" class="form-control form-control-sm" name="files[${id}][sample_audio]" accept="audio/*">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <h6 class="fw-semibold mb-0">Chapters</h6>
                <button type="button" class="btn btn-sm btn-light-brand chapter-add-btn" data-format-id="${id}">
                    <i class="feather-plus me-1"></i> Add Chapter
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="chaptersTable-${id}">
                    <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th>Chapter Title</th>
                            <th>Audio File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="chaptersBody-${id}"></tbody>
                </table>
            </div>
            <div class="text-muted fs-12 mt-2" id="chaptersEmptyHint-${id}">No chapters added yet.</div>
        </div>
    </div>`;
}

function addChapterRow(formatId){
    const body = document.getElementById(`chaptersBody-${formatId}`);
    const hint = document.getElementById(`chaptersEmptyHint-${formatId}`);
    const i = chapterIdx++;
    const rowNum = body.children.length + 1;

    hint.classList.add('d-none');

    body.insertAdjacentHTML('beforeend', `
    <tr id="chapterRow-${i}">
        <td>${rowNum}</td>
        <td><input type="text" class="form-control form-control-sm" name="chapters[${i}][title]" placeholder="e.g. Introduction"></td>
        <td><input type="file" class="form-control form-control-sm" name="chapters[${i}][audio_file]" accept="audio/*"></td>
        <td>
            <button type="button" class="btn btn-sm btn-light-danger" onclick="removeChapterRow(${i}, '${formatId}')">
                <i class="feather-trash-2"></i>
            </button>
        </td>
    </tr>`);
}

function removeChapterRow(i, formatId){
    document.getElementById(`chapterRow-${i}`)?.remove();
    const body = document.getElementById(`chaptersBody-${formatId}`);
    if(body.children.length === 0){
        document.getElementById(`chaptersEmptyHint-${formatId}`)?.classList.remove('d-none');
    }
    Array.from(body.children).forEach((row, idx) => { row.children[0].textContent = idx + 1; });
}

/* ══════════════════════════════════════════════════════════════════════
 * PRICING TABLE — currency + tax aware, with a LIVE "Final Price" column
 * AND a Discount % field that stays in sync with Sale Price both ways.
 *
 * Each row shows:
 *  - the country's mapped currency as a prefix on Price / Sale Price
 *    (e.g. "RM 0.00", "$ 0.00", "₹ 0.00") + a hidden currency_id field
 *  - a Discount % input — typing here auto-fills Sale Price, and typing
 *    a Sale Price directly auto-fills Discount % (see the sync functions
 *    below buildPricingTable). // ← NEW
 *  - a "Show" toggle (maps to is_on_sale) that controls whether the
 *    discount is actually surfaced on the storefront / reflected in the
 *    Final Price preview. // ← NEW
 *  - the country's active tax name/rate (e.g. "SST (6%)") or "No tax set"
 *  - an "Apply" toggle switch (only shown when the country actually has
 *    an active tax) so the admin can exempt a specific book/country
 *  - a live-computed "Final Price" column that instantly recalculates
 *    whenever Price, Sale Price, Discount %, or either toggle changes —
 *    no save/reload needed. Sale price (if filled and "Show" is on)
 *    always wins over regular price, matching what the storefront will
 *    actually charge.
 * ══════════════════════════════════════════════════════════════════════ */
function buildPricingTable(){
    const checked = getCheckedFormats(), container=document.getElementById('pricingContainer');
    if(checked.length===0){
        container.innerHTML='<div class="text-center text-muted py-4"><i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>Go back to <strong>Formats</strong> and select at least one format first.</div>';
        return;
    }
    container.innerHTML='';
    checked.forEach(formatId=>{
        const format=FORMATS.find(f=>f.id===formatId); if(!format) return;
        let rows='';
        COUNTRIES.forEach(country=>{
            const symbol = country.currency_symbol || country.currency_code || '';
            const currencyIdField = country.currency_id
                ? `<input type="hidden" name="prices[${formatId}][${country.id}][currency_id]" value="${country.currency_id}">`
                : '';

            const hasTax = !!country.tax_id;
            const taxRate = hasTax ? parseFloat(country.tax_rate) : 0;
            const taxLabel = hasTax
                ? `${country.tax_name} (${taxRate}%)`
                : `<span class="text-muted">No tax set</span>`;

            const taxIdField = hasTax
                ? `<input type="hidden" name="prices[${formatId}][${country.id}][tax_id]" value="${country.tax_id}">
                   <input type="hidden" name="prices[${formatId}][${country.id}][tax_rate]" value="${taxRate}">`
                : '';

            const rowId = `price-row-${formatId}-${country.id}`;

            rows+=`<tr id="${rowId}" data-tax-rate="${taxRate}" data-has-tax="${hasTax ? 1 : 0}">
                <td>
                    ${country.name}
                    ${country.currency_code ? `<span class="badge bg-light text-muted fw-normal ms-1">${country.currency_code}</span>` : ''}
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        ${symbol ? `<span class="input-group-text">${symbol}</span>` : ''}
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm price-input" name="prices[${formatId}][${country.id}][price]" placeholder="0.00">
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        ${symbol ? `<span class="input-group-text">${symbol}</span>` : ''}
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm sale-price-input" name="prices[${formatId}][${country.id}][sale_price]" placeholder="Optional">
                    </div>
                </td>
               <td class="d-none">
    <div class="input-group input-group-sm">
        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm discount-percent-input" name="prices[${formatId}][${country.id}][discount_percent]" placeholder="0">
        <span class="input-group-text">%</span>
    </div>
</td>
                <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input class="form-check-input is-on-sale-toggle" type="checkbox" name="prices[${formatId}][${country.id}][is_on_sale]" value="1" title="Show this discount on the storefront">
                    </div>
                </td>
                <td class="fs-12">
                    ${taxLabel}
                    ${taxIdField}
                </td>
                <td class="text-center">
                    ${hasTax
                        ? `<div class="form-check form-switch d-flex justify-content-center">
                             <input class="form-check-input apply-tax-toggle" type="checkbox" name="prices[${formatId}][${country.id}][apply_tax]" value="1" checked>
                           </div>`
                        : '—'
                    }
                </td>
                <td class="fs-12 fw-semibold text-end final-price-cell">
                    ${symbol} <span class="final-price-value">0.00</span>
                </td>
                <td class="d-none">${currencyIdField}</td>
            </tr>`;
        });
        container.insertAdjacentHTML('beforeend', `
        <div class="pricing-format-block">
            <div class="pricing-format-header"><i class="${format.icon}"></i> ${format.name}</div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr>
                    <th style="width:20%;">Country</th>
                    <th>Price</th>
                    <th>Sale Price</th>
                    <th style="width:110px;" class="d-none">Discount %</th>
                    <th style="width:70px;" class="text-center">Show</th>
                    <th>Tax</th>
                    <th style="width:70px;" class="text-center">Apply</th>
                    <th style="width:13%;" class="text-end" title="What the customer actually pays: Sale Price (or Price if no Sale Price) plus tax, only when Apply is on and Show is on. Preview only.">Final Price <i class="feather-info fs-11 text-muted"></i></th>
                    <th class="d-none"></th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table></div>
        </div>`);
    });

    // Compute every row's final price once right after building (covers Edit-page prefill too)
    document.querySelectorAll('#pricingContainer tr[data-has-tax]').forEach(recalcRowFinalPrice);
}

/* ── Recalculate ONE pricing row's "Final Price" column ──
   Rule: sale price (if filled AND "Show" is on AND it's actually lower
   than price) wins over regular price, since that's what the storefront
   will actually charge. Tax is only added if the country has an active
   tax AND the Apply toggle for that row is on. // ← UPDATED to respect Show toggle */
/* ── Half-up rounding helper — mirrors PHP's PHP_ROUND_HALF_UP exactly ── */
function roundHalfUp(num, decimals = 2){
    const factor = Math.pow(10, decimals);
    return Math.round((num + Number.EPSILON) * factor) / factor;
}

function recalcRowFinalPrice(row){
    const priceInput     = row.querySelector('.price-input');
    const salePriceInput = row.querySelector('.sale-price-input');
    const applyToggle    = row.querySelector('.apply-tax-toggle');
    const showToggle     = row.querySelector('.is-on-sale-toggle');
    const finalCell      = row.querySelector('.final-price-value');
    if (!finalCell) return;

    const price     = roundHalfUp(parseFloat(priceInput?.value) || 0);
    const salePrice = roundHalfUp(parseFloat(salePriceInput?.value) || 0);
    const showSale  = showToggle ? showToggle.checked : false;

    const base = (showSale && salePrice > 0 && salePrice < price) ? salePrice : price;

    const hasTax   = row.dataset.hasTax === '1';
    const taxRate  = parseFloat(row.dataset.taxRate) || 0;
    const applyTax = applyToggle ? applyToggle.checked : false;

    // ── Exact final price (same formula the backend uses) ──
    let finalPrice = base;
    if (hasTax && applyTax && taxRate > 0){
        finalPrice = base + (base * taxRate / 100);
    }
    finalPrice = roundHalfUp(finalPrice, 2);

    // ── Rounded to nearest whole number (matches final_price_rounded in DB) ──
    const finalPriceRounded = roundHalfUp(finalPrice, 0);

    // ── Round-off adjustment (matches round_off_amount in DB) ──
    const roundOffAmount = roundHalfUp(finalPriceRounded - finalPrice, 2);

    // ── Display: whole number as the headline, exact price + round-off as a small hint ──
    const roundOffLabel = roundOffAmount === 0
        ? ''
        : ` <span class="fs-11 text-muted">(${finalPrice.toFixed(2)} ${roundOffAmount > 0 ? '+' : ''}${roundOffAmount.toFixed(2)})</span>`;

    finalCell.innerHTML = `${finalPriceRounded}${roundOffLabel}`;

    finalCell.closest('.final-price-cell')?.classList.toggle('text-success', hasTax && applyTax && taxRate > 0 && base > 0);
}

/* ── Discount % ↔ Sale Price bidirectional sync ── ← NEW BLOCK
   _syncingDiscount guards against the two input listeners triggering
   each other in an infinite loop when one programmatically updates
   the other's value. */
let _syncingDiscount = false;

function syncSalePriceFromDiscount(row){
    if (_syncingDiscount) return;
    _syncingDiscount = true;

    const priceInput    = row.querySelector('.price-input');
    const discountInput = row.querySelector('.discount-percent-input');
    const saleInput     = row.querySelector('.sale-price-input');

    const price    = parseFloat(priceInput?.value) || 0;
    const discount = parseFloat(discountInput?.value) || 0;

    if (price > 0 && discount > 0){
        saleInput.value = (price - (price * discount / 100)).toFixed(2);
    }

    _syncingDiscount = false;
}

function syncDiscountFromSalePrice(row){
    if (_syncingDiscount) return;
    _syncingDiscount = true;

    const priceInput    = row.querySelector('.price-input');
    const discountInput = row.querySelector('.discount-percent-input');
    const saleInput     = row.querySelector('.sale-price-input');

    const price = parseFloat(priceInput?.value) || 0;
    const sale  = parseFloat(saleInput?.value) || 0;

    if (price > 0 && sale > 0 && sale < price){
        discountInput.value = (((price - sale) / price) * 100).toFixed(2);
    } else if (sale === 0) {
        discountInput.value = '';
    }

    _syncingDiscount = false;
}

/* Live update: typing in Price/Sale Price/Discount %, or flipping either
   toggle, instantly refreshes that row's Final Price — no save/reload
   needed. Also keeps Discount % and Sale Price synced both directions. */
document.addEventListener('input', function(e){
    const row = e.target.closest('tr[data-has-tax]');
    if (!row) return;

    if (e.target.classList.contains('discount-percent-input')){ // ← NEW branch
        syncSalePriceFromDiscount(row);
        recalcRowFinalPrice(row);
    } else if (e.target.classList.contains('sale-price-input')){
        syncDiscountFromSalePrice(row); // ← NEW
        recalcRowFinalPrice(row);
    } else if (e.target.classList.contains('price-input')){
        // Price changed — recompute sale price from the existing discount %, if any // ← NEW
        syncSalePriceFromDiscount(row);
        recalcRowFinalPrice(row);
    }
});

document.addEventListener('change', function(e){
    if (e.target.classList.contains('apply-tax-toggle') || e.target.classList.contains('is-on-sale-toggle')){ // ← UPDATED: added is-on-sale-toggle
        const row = e.target.closest('tr[data-has-tax]');
        if (row) recalcRowFinalPrice(row);
    }
});

function buildInventoryTab(){
    const checked = getCheckedFormats(), c=document.getElementById('inventoryContainer');
    const physical = checked.filter(id=>{ const f=FORMATS.find(x=>x.id===id); return f && f.requires_shipping; });
    if(physical.length===0){ c.innerHTML='<div class="text-center text-muted py-4"><i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>Enable Physical Book in <strong>Formats</strong> to manage inventory.</div>'; return; }
    c.innerHTML='';
    physical.forEach(id=>{
        const f=FORMATS.find(x=>x.id===id);
        c.insertAdjacentHTML('beforeend', `
        <div class="inv-format-block">
            <div class="inv-format-header"><i class="${f.icon}"></i> ${f.name}</div>
            <div class="p-3 row g-3">
                <div class="col-md-4"><label class="form-label fs-12">SKU</label><input type="text" class="form-control form-control-sm" name="inventory[${id}][sku]"></div>
                <div class="col-md-4"><label class="form-label fs-12">Stock Quantity</label><input type="number" class="form-control form-control-sm" name="inventory[${id}][stock_quantity]"></div>
                <div class="col-md-4"><label class="form-label fs-12">Low Stock Threshold</label><input type="number" class="form-control form-control-sm" name="inventory[${id}][low_stock_threshold]"></div>
            </div>
        </div>`);
    });
}

let shipMethodIdx=0;
function addShippingMethodRow(){
    const c=document.getElementById('shippingMethodsContainer'); const i=shipMethodIdx++;
    c.insertAdjacentHTML('beforeend', `
    <div class="row g-2 mb-2 align-items-center">
        <div class="col-md-5"><input type="text" class="form-control form-control-sm" name="shipping_methods[${i}][name]" placeholder="e.g. Standard Shipping"></div>
        <div class="col-md-3"><input type="number" step="0.01" class="form-control form-control-sm" name="shipping_methods[${i}][cost]" placeholder="Cost"></div>
        <div class="col-md-2 form-check form-switch"><input class="form-check-input" type="checkbox" name="shipping_methods[${i}][is_active]" checked></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-light-danger" onclick="this.closest('.row').remove()"><i class="feather-trash-2"></i></button></div>
    </div>`);
}

document.getElementById('metaKeywordsInput')?.addEventListener('blur', function(){
    document.getElementById('metaKeywordsHidden').value = JSON.stringify(this.value.split(',').map(s=>s.trim()).filter(Boolean));
});

/* ── Prefill format settings fields (for Edit page) ── */
function prefillFormatSettings(settingsData){
    if (!settingsData) return;
    Object.entries(settingsData).forEach(([formatId, settings]) => {
        Object.entries(settings || {}).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const cb = document.querySelector(`[name="settings[${formatId}][${key}][]"][value="${v}"]`);
                    if (cb) cb.checked = true;
                });
            } else {
                const el = document.querySelector(`[name="settings[${formatId}][${key}]"]`);
                if (el) el.value = value;
            }
        });
    });
}

function updateBookTitleBanner(){
    const bannerText = document.getElementById('bookTitleBannerText');
    const banner = document.getElementById('bookTitleBanner');
    if (!bannerText) return;

    const titleVal = document.getElementById('title_field')?.value.trim() || '';
    const isbnVal  = document.getElementById('isbn_field')?.value.trim() || '';

    let text = titleVal || 'New Book (untitled)';
    if (isbnVal) text += ` (${isbnVal})`;

    bannerText.textContent = text;
    banner?.classList.toggle('is-empty', !titleVal);
}

document.getElementById('title_field')?.addEventListener('input', updateBookTitleBanner);
document.getElementById('isbn_field')?.addEventListener('input', updateBookTitleBanner);
/* ── AJAX save per tab ──
   Every "Save & Continue" button across all tabs calls this with itself as `btn`,
   so the spinner via setButtonLoading() automatically covers every tab.
   Only "files" shows the center-screen processing overlay now, since only that
   tab (plus Media, handled in saveMediaAjax) actually uploads real files —
   this is why Basic Info / Formats / etc. now feel instant instead of waiting
   on the overlay's mount/animation. */
let _savingTab = false;
async function saveTabAjax(endpoint, nextTab, btn){
    if (_savingTab) return;
    _savingTab = true;
    setButtonLoading(btn, true);

    const showsOverlay = (endpoint === 'files');
    if (showsOverlay) {
        showProcessingOverlay('Uploading & validating file...', 'Please wait, this can take a few seconds for large files');
    }

    const form = document.getElementById('bookForm');
    const fd = new FormData(form);
    const bookId = document.getElementById('book_id_field').value;

    let url;
    if (endpoint === 'basic') {
        url = bookId
            ? bookTabRouteTemplates.basic.withId.replace('__BOOK_ID__', bookId)
            : bookTabRouteTemplates.basic.withoutId;
    } else {
        url = bookTabRouteTemplates[endpoint].replace('__BOOK_ID__', bookId);
    }

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd
        });
        const data = await res.json();

        if (data.status === 'success') {
            if (data.book_id) document.getElementById('book_id_field').value = data.book_id;
            showToast(data.message, 'success');
            if (data.redirect) { window.location.href = data.redirect; return; }
            if (nextTab) goToTab(nextTab);
        } else if (Array.isArray(data.epub_errors) && data.epub_errors.length) {
            // EPUB-specific failure → inline error box instead of a one-line toast
            showEpubErrorModal(data.message, data.epub_errors);
        } else {
            showToast(data.message || 'Please check the form for errors', 'error');
        }
    } catch (e) {
        showToast('Something went wrong!', 'error');
    } finally {
        _savingTab = false;
        setButtonLoading(btn, false);
        if (showsOverlay) hideProcessingOverlay();
    }
}

async function saveRelatedAjax(){
    const bookId = document.getElementById('book_id_field').value;
    if(!bookId) return; // nothing to relate yet if book not created

    const ids = $('#related_books_select').val() || [];
    const url = bookRelatedUrlTemplate.replace('__BOOK_ID__', bookId);
    try {
        await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ related_books: ids })
        });
    } catch (e) {
        console.error('Related books save failed:', e);
    }
}

async function savePublishAjax(btn){
    setButtonLoading(btn, true);
    await saveRelatedAjax();
    await saveTabAjax('publish', null, btn); // saveTabAjax's own finally turns the spinner off
}

/* ═══════════════════════════ Quick-Add (offcanvas) ═══════════════════════════ */

const QUICK_CONFIG = {
    category: {
        url: quickCreateUrls.category,
        select: '#category_id',
        payload: () => ({
            name_en: document.getElementById('qc_category_name_en').value,
            name_ms: document.getElementById('qc_category_name_ms').value,
        }),
        validate: () => document.getElementById('qc_category_name_en').value.trim() !== '',
        offcanvas: '#offcanvasCategory',
    },
    subcategory: {
        url: quickCreateUrls.subcategory,
        select: '#subcategory_id',
        payload: () => ({
            category_id: document.getElementById('qc_subcategory_category_id').value,
            name_en: document.getElementById('qc_subcategory_name_en').value,
            name_ms: document.getElementById('qc_subcategory_name_ms').value,
        }),
        validate: () => document.getElementById('qc_subcategory_category_id').value && document.getElementById('qc_subcategory_name_en').value.trim() !== '',
        offcanvas: '#offcanvasSubcategory',
    },
    series: {
        url: quickCreateUrls.series,
        select: '#series_id',
        payload: () => ({
            publisher_id: document.getElementById('qc_series_publisher_id').value,
            name: document.getElementById('qc_series_name').value,
        }),
        validate: () => document.getElementById('qc_series_publisher_id').value && document.getElementById('qc_series_name').value.trim() !== '',
        offcanvas: '#offcanvasSeries',
    },
    author: {
        url: quickCreateUrls.author,
        select: '#author_id',
        payload: () => ({
            name: document.getElementById('qc_author_name').value,
            email: document.getElementById('qc_author_email').value,
        }),
        validate: () => document.getElementById('qc_author_name').value.trim() !== '' && document.getElementById('qc_author_email').value.trim() !== '',
        offcanvas: '#offcanvasAuthor',
    },
    publisher: {
        url: quickCreateUrls.publisher,
        select: '#publisher_id',
        payload: () => ({
            company_name: document.getElementById('qc_publisher_name').value,
            email: document.getElementById('qc_publisher_email').value,
        }),
        validate: () => document.getElementById('qc_publisher_name').value.trim() !== '' && document.getElementById('qc_publisher_email').value.trim() !== '',
        offcanvas: '#offcanvasPublisher',
    },
};

async function quickCreate(type){
    const cfg = QUICK_CONFIG[type];
    if(!cfg.validate()){
        showToast('Please fill required fields', 'error');
        return;
    }

    try {
        const res = await fetch(cfg.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify(cfg.payload()),
        });
        const data = await res.json();

        if(data.status === 'success'){
            const $select = $(cfg.select);
            const newOption = new Option(data.item.name, data.item.id, true, true);
            $select.append(newOption).trigger('change');

            showToast(`"${data.item.name}" added and selected`, 'success');

            const offEl = document.querySelector(cfg.offcanvas);
            const offInstance = bootstrap.Offcanvas.getInstance(offEl) || new bootstrap.Offcanvas(offEl);
            offInstance.hide();

            offEl.querySelectorAll('input').forEach(el => { el.value = ''; });
        } else {
            showToast(data.message || 'Could not save', 'error');
        }
    } catch (e) {
        showToast('Something went wrong', 'error');
    }
}

function toggleTrailerInput(){
    const type = document.getElementById('trailer_type').value;
    document.getElementById('trailerUploadWrap').style.display = type === 'upload' ? '' : 'none';
    document.getElementById('trailerUrlWrap').style.display = ['youtube','vimeo'].includes(type) ? '' : 'none';
}

/* ── Show count of selected gallery files ── */
function showGalleryFileCount(input){
    const hint = document.getElementById('galleryFileCountHint');
    if(input.files.length === 0){ hint.textContent = ''; return; }
    hint.textContent = input.files.length === 1
        ? `1 image selected: ${input.files[0].name}`
        : `${input.files.length} images selected`;
}

/* ── Save Media tab (trailer + gallery) ── */
async function saveMediaAjax(btn){
    const bookId = document.getElementById('book_id_field').value;
    if(!bookId){
        showToast('Please save Basic Info first', 'error');
        return;
    }

    const fd = new FormData();
    const type = document.getElementById('trailer_type').value;
    fd.append('trailer_type', type);

    if(type === 'upload'){
        const f = document.getElementById('trailer_file').files[0];
        if(f) fd.append('trailer_file', f);
    } else if(type === 'youtube' || type === 'vimeo'){
        fd.append('trailer_url', document.getElementById('trailer_url').value);
    }

    const gallery = document.getElementById('gallery_images').files;
    for(const f of gallery){
        fd.append('gallery_images[]', f);
    }

    setButtonLoading(btn, true);
    showProcessingOverlay('Uploading media...', 'Please wait, this can take a few seconds for large files');

    const url = bookMediaUrlTemplate.replace('__BOOK_ID__', bookId);

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd
        });
        const data = await res.json();

        if(data.status === 'success'){
            showToast(data.message, 'success');

            const preview = document.getElementById('galleryPreview');
            (data.gallery || []).forEach(img => {
                if(document.querySelector(`.gallery-item[data-id="${img.id}"]`)) return;
                preview.insertAdjacentHTML('beforeend', `
                    <div class="col-md-2 gallery-item" data-id="${img.id}">
                        <div class="border rounded-3 p-1 position-relative">
                            <img src="${img.image_url}" class="img-fluid rounded-2" style="height:100px;width:100%;object-fit:cover;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="deleteGalleryImage(${img.id}, this)">✕</button>
                        </div>
                    </div>`);
            });

            document.getElementById('gallery_images').value = '';
            document.getElementById('galleryFileCountHint').textContent = '';

            goToTab('tab-pricing');
        } else {
            showToast(data.message || 'Could not save media', 'error');
        }
    } catch (e) {
        showToast('Something went wrong', 'error');
    } finally {
        setButtonLoading(btn, false);
        hideProcessingOverlay();
    }
}

/* ── Delete a single gallery image ── */
async function deleteGalleryImage(id, btn){
    if(!confirm('Remove this image?')) return;

    const url = galleryDeleteUrlTemplate.replace('__IMAGE_ID__', id);

    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();

        if(data.status === 'success'){
            btn.closest('.gallery-item').remove();
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Could not delete image', 'error');
        }
    } catch (e) {
        showToast('Delete failed', 'error');
    }
}


/* ── Remove an already-uploaded book file (EPUB / PDF / cover preview / sample audio) ── */
async function removeUploadedFile(fileId, btn){
    if(!confirm('Remove this file? You can upload a new one afterward.')) return;

    const url = bookFileDeleteUrlTemplate.replace('__FILE_ID__', fileId);

    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();

        if(data.status === 'success'){
            btn.closest('.uploaded-file-hint')?.remove();
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Could not remove file', 'error');
        }
    } catch(e){
        showToast('Something went wrong', 'error');
    }
}

/* ── Remove a chapter's audio file only ── */
async function removeChapterAudio(chapterId, btn){
    if(!confirm('Remove this chapter audio?')) return;

    const url = chapterAudioDeleteUrlTemplate.replace('__CHAPTER_ID__', chapterId);

    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();

        if(data.status === 'success'){
            btn.closest('.uploaded-file-hint')?.remove();
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Could not remove audio', 'error');
        }
    } catch(e){
        showToast('Something went wrong', 'error');
    }
}


$(function(){
    $('.select2-field').not('#related_books_select').select2({
        width:'100%',
        placeholder:function(){return $(this).data('placeholder');},
        allowClear:true
    });

    $('#related_books_select').select2({
        width: '100%',
        placeholder: 'Search and select related books…',
        allowClear: true,
        ajax: {
            url: bookSearchSelectUrl,
            delay: 250,
            data: params => ({ q: params.term, exclude: document.getElementById('book_id_field').value }),
            processResults: data => ({ results: data })
        },
        minimumInputLength: 1
    });
});



/* ── Profile photo preview ── */
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

    showToast('Photo will be removed when you save.', 'success');
});

/* ── Save profile (name/phone/photo/password) ── */
async function saveProfileAjax(btn) {
    const form = document.getElementById('profileForm');
    const newPassword = document.getElementById('newPasswordInput').value;
    const currentPassword = document.getElementById('currentPasswordInput').value;

    if (newPassword && !currentPassword) {
        showToast('Please enter your current password to set a new one.', 'error');
        return;
    }

    const fd = new FormData(form);
    setButtonLoading(btn, true);

    try {
        const res = await fetch("{{ route('profile.update') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');

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
            showToast(data.message || 'Could not update profile', 'error');
        }
    } catch (e) {
        showToast('Something went wrong', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}
</script>