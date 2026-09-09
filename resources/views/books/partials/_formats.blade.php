<div class="tab-pane d-none" id="tab-formats">
    <p class="text-muted mb-4">Select the formats this book will be available in.</p>

    @php
        $enabledFormatIds = $book?->formats->pluck('id')->toArray() ?? [];
        $formatSettingsMap = [];
        $formatSkuMap = [];
        if ($book) {
            foreach ($book->formats as $f) {
                $formatSettingsMap[$f->id] = json_decode($f->pivot->settings ?? '{}', true) ?? [];
                $formatSkuMap[$f->id] = $f->pivot->sku;
            }
        }
    @endphp

    <div class="d-flex flex-column gap-3">
        @foreach($formats as $format)
        @php
            $isEnabled = in_array($format->id, $enabledFormatIds);
            $s   = $formatSettingsMap[$format->id] ?? [];
            $sku = $formatSkuMap[$format->id] ?? '';
        @endphp
        <div class="card border format-card {{ $isEnabled ? 'is-enabled' : '' }}" data-format-id="{{ $format->id }}">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="format-icon-box"><i class="{{ $format->icon }}"></i></div>
                        <div>
                            <div class="fw-semibold">{{ $format->name }}</div>
                            <span class="format-badge badge-disabled {{ $isEnabled ? 'd-none' : '' }}">Disabled</span>
                            <span class="format-badge badge-enabled {{ $isEnabled ? '' : 'd-none' }}">Enabled</span>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input format-toggle" type="checkbox" name="formats[]"
                               value="{{ $format->id }}" data-target="format-fields-{{ $format->id }}"
                               {{ $isEnabled ? 'checked' : '' }}>
                    </div>
                </div>

                <div id="format-fields-{{ $format->id }}" class="format-fields {{ $isEnabled ? '' : 'd-none' }} mt-3 pt-3 border-top">

                    @if($format->code === 'physical')
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fs-12">SKU</label>
                                <input type="text" class="form-control form-control-sm" name="sku[{{ $format->id }}]" value="{{ $sku }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">Edition</label>
                                <input type="text" class="form-control form-control-sm" name="settings[{{ $format->id }}][edition]" value="{{ $s['edition'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">Pages</label>
                                <input type="number" class="form-control form-control-sm" name="settings[{{ $format->id }}][pages]" value="{{ $s['pages'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">Publication Date</label>
                                <input type="date" class="form-control form-control-sm" name="settings[{{ $format->id }}][publication_date]" value="{{ $s['publication_date'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12">Height (cm)</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="settings[{{ $format->id }}][height]" value="{{ $s['height'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12">Width (cm)</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="settings[{{ $format->id }}][width]" value="{{ $s['width'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12">Thickness (cm)</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="settings[{{ $format->id }}][thickness]" value="{{ $s['thickness'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">Weight (gm)</label>
                                <input type="number" class="form-control form-control-sm" name="settings[{{ $format->id }}][weight]" value="{{ $s['weight'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">ISBN (Print)</label>
                                <input type="text" class="form-control form-control-sm" name="settings[{{ $format->id }}][isbn_print]" value="{{ $s['isbn_print'] ?? '' }}">
                            </div>
                        </div>

                    @elseif($format->code === 'ebook')
                        @php $readerAccess = $s['reader_access'] ?? ['web','android','ios']; @endphp
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fs-12">Format</label>
                                <select class="form-control form-control-sm" name="settings[{{ $format->id }}][ebook_format]">
                                    <option value="epub_pdf" {{ ($s['ebook_format'] ?? '') == 'epub_pdf' ? 'selected' : '' }}>EPUB + PDF</option>
                                    <option value="epub" {{ ($s['ebook_format'] ?? '') == 'epub' ? 'selected' : '' }}>EPUB only</option>
                                    <option value="pdf" {{ ($s['ebook_format'] ?? '') == 'pdf' ? 'selected' : '' }}>PDF only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12">DRM Protection</label>
                                <select class="form-control form-control-sm" name="settings[{{ $format->id }}][drm]">
                                    <option value="lcp" {{ ($s['drm'] ?? '') == 'lcp' ? 'selected' : '' }}>LCP DRM</option>
                                    <option value="watermark" {{ ($s['drm'] ?? '') == 'watermark' ? 'selected' : '' }}>Watermark</option>
                                    <option value="none" {{ ($s['drm'] ?? '') == 'none' ? 'selected' : '' }}>No DRM</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 d-block">Reader Access</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="settings[{{ $format->id }}][reader_access][]" value="web" {{ in_array('web', $readerAccess) ? 'checked' : '' }}>
                                    <label class="form-check-label fs-13">Web Reader</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="settings[{{ $format->id }}][reader_access][]" value="android" {{ in_array('android', $readerAccess) ? 'checked' : '' }}>
                                    <label class="form-check-label fs-13">Android App</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="settings[{{ $format->id }}][reader_access][]" value="ios" {{ in_array('ios', $readerAccess) ? 'checked' : '' }}>
                                    <label class="form-check-label fs-13">iOS App</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12">Download Option</label>
                                <select class="form-control form-control-sm" name="settings[{{ $format->id }}][download_option]">
                                    <option value="reader_only" {{ ($s['download_option'] ?? '') == 'reader_only' ? 'selected' : '' }}>Reader Only</option>
                                    <option value="downloadable" {{ ($s['download_option'] ?? '') == 'downloadable' ? 'selected' : '' }}>Downloadable</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12">Download Limit (per user)</label>
                                <input type="number" class="form-control form-control-sm" name="settings[{{ $format->id }}][download_limit]" value="{{ $s['download_limit'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12">Access Period</label>
                                <select class="form-control form-control-sm" name="settings[{{ $format->id }}][access_period]">
                                    <option value="unlimited" {{ ($s['access_period'] ?? '') == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                    <option value="1_year" {{ ($s['access_period'] ?? '') == '1_year' ? 'selected' : '' }}>1 Year</option>
                                    <option value="6_months" {{ ($s['access_period'] ?? '') == '6_months' ? 'selected' : '' }}>6 Months</option>
                                </select>
                            </div>
                        </div>

                    @elseif($format->code === 'audiobook')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fs-12">Narrator</label>
                                <input type="text" class="form-control form-control-sm" name="settings[{{ $format->id }}][narrator]" value="{{ $s['narrator'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12">Total Duration</label>
                                <input type="text" class="form-control form-control-sm" name="settings[{{ $format->id }}][total_duration]" value="{{ $s['total_duration'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12">Audio Format</label>
                                <select class="form-control form-control-sm" name="settings[{{ $format->id }}][audio_format]">
                                    <option value="mp3" {{ ($s['audio_format'] ?? '') == 'mp3' ? 'selected' : '' }}>MP3</option>
                                    <option value="m4b" {{ ($s['audio_format'] ?? '') == 'm4b' ? 'selected' : '' }}>M4B</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mt-2">Upload the actual sample audio + chapter files in <strong>Files & DRM</strong> tab.</div>

                    @else
                        <div class="col-12">
                            <label class="form-label fs-12">SKU</label>
                            <input type="text" class="form-control form-control-sm" name="sku[{{ $format->id }}]" value="{{ $sku }}">
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-basic')"><i class="feather-arrow-left me-1"></i> Previous</button>
        <button type="button" class="btn btn-primary" onclick="buildAllDynamicTabs(); saveTabAjax('formats','tab-files', this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>