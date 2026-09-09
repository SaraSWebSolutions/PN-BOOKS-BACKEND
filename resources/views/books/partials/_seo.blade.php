@php
    $seo = $book?->seo;
    $metaKeywordsArray = is_array($seo?->meta_keywords) ? $seo->meta_keywords : [];
@endphp
<div class="tab-pane d-none" id="tab-seo">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">SEO Title</label>
            <input type="text" name="seo[seo_title]" maxlength="70" class="form-control"
                value="{{ old('seo.seo_title', $seo->seo_title ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">URL Slug</label>
            <input type="text" name="seo[url_slug]" class="form-control"
                value="{{ old('seo.url_slug', $seo->url_slug ?? ($book->slug ?? '')) }}">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Meta Description</label>
            <textarea name="seo[meta_description]" maxlength="160" class="form-control" rows="2">{{ old('seo.meta_description', $seo->meta_description ?? '') }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Meta Keywords (comma separated)</label>
            <input type="text" id="metaKeywordsInput" class="form-control" placeholder="e.g. ponniyin selvan, kalki, tamil books"
                value="{{ old('seo.meta_keywords', implode(', ', $metaKeywordsArray)) }}">
            <input type="hidden" name="seo[meta_keywords]" id="metaKeywordsHidden"
                value="{{ !empty($metaKeywordsArray) ? json_encode($metaKeywordsArray) : '' }}">
        </div>
        <div class="col-md-6 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="seo[allow_index]" {{ old('seo.allow_index', $seo->allow_index ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Allow search engines to index this page</label>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="seo[allow_follow]" {{ old('seo.allow_follow', $seo->allow_follow ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Allow search engines to follow links</label>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="seo[enable_structured_data]" {{ old('seo.enable_structured_data', $seo->enable_structured_data ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Enable structured data (JSON-LD)</label>
        </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-shipping')"><i class="feather-arrow-left me-1"></i> Previous</button>
        <button type="button" class="btn btn-primary" onclick="saveTabAjax('seo','tab-publishing', this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>