<div class="tab-pane d-none" id="tab-files">
    <p class="text-muted mb-3">Upload files for digital formats and configure DRM protection.</p>

    <div id="filesContainer">
        <div class="text-center text-muted py-4" id="filesEmptyHint">
            <i class="feather-alert-circle fs-30 d-block mb-2 opacity-50"></i>
            Enable eBook or Audiobook in <strong>Formats</strong> to upload files here.
        </div>
    </div>

    {{-- Inline EPUB error box — lives inside this tab, not as a page-level Bootstrap modal.
         Fixes the "flash on page load" bug: this tab is d-none until goToTab() reveals it,
         so the box has no way to render before JS finishes setting up. --}}
    <div class="epub-inline-error d-none" id="epubErrorBox">
        <div class="epub-inline-error-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="text-danger mb-0"><i class="feather-alert-triangle me-2"></i>EPUB Validation Failed</h6>
                <button type="button" class="btn-close" onclick="hideEpubErrorBox()"></button>
            </div>
            <p class="fw-semibold mb-1" id="epubErrorSummary"></p>
            <p class="text-muted fs-13 mb-3" id="epubErrorCount"></p>
            <div id="epubErrorList" style="max-height:280px;overflow-y:auto;"></div>
            <div class="text-center mt-2" id="epubShowMoreWrap">
                <button type="button" class="btn btn-sm btn-light-brand" id="epubShowMoreBtn">Show all errors</button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-formats')"><i class="feather-arrow-left me-1"></i> Previous</button>
        <button type="button" class="btn btn-primary" onclick="saveTabAjax('files','tab-media', this)">Save & Continue</button>
    </div>
</div>