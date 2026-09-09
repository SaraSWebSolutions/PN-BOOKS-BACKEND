<div class="tab-pane d-none" id="tab-media">
    <p class="text-muted mb-4">Upload a trailer video and additional gallery images for this book.</p>

    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Trailer Type</label>
            <select class="form-control" id="trailer_type" onchange="toggleTrailerInput()">
                <option value="">No trailer</option>
                <option value="upload" {{ ($book->trailer_type ?? '') == 'upload' ? 'selected' : '' }}>Upload Video File</option>
                <option value="youtube" {{ ($book->trailer_type ?? '') == 'youtube' ? 'selected' : '' }}>YouTube URL</option>
                <option value="vimeo" {{ ($book->trailer_type ?? '') == 'vimeo' ? 'selected' : '' }}>Vimeo URL</option>
            </select>
        </div>

        <div class="col-md-6" id="trailerUploadWrap" style="{{ ($book->trailer_type ?? '') == 'upload' ? '' : 'display:none' }}">
            <label class="form-label fw-semibold">Trailer File (mp4/mov/webm, max 100MB)</label>
            <input type="file" class="form-control" id="trailer_file" accept="video/mp4,video/quicktime,video/webm">
            @if(($book->trailer_type ?? '') == 'upload' && ($book->trailer_video_url ?? null))
                <div class="form-text text-success">
                    Current: <a href="{{ $book->trailer_video_url }}" target="_blank">view trailer</a>
                </div>
            @endif
        </div>

        <div class="col-md-6" id="trailerUrlWrap" style="{{ in_array($book->trailer_type ?? '', ['youtube','vimeo']) ? '' : 'display:none' }}">
            <label class="form-label fw-semibold">Trailer URL</label>
            <input type="url" class="form-control" id="trailer_url"
                   value="{{ in_array($book->trailer_type ?? '', ['youtube','vimeo']) ? ($book->trailer_video ?? '') : '' }}"
                   placeholder="https://youtube.com/watch?v=...">
        </div>
    </div>

    <hr class="my-4">

    <label class="form-label fw-semibold">Additional / Detail Images (Gallery)</label>
    <input type="file" class="form-control mb-2" id="gallery_images" accept="image/*" multiple onchange="showGalleryFileCount(this)">
    <div class="form-text fs-12 mb-3" id="galleryFileCountHint"></div>

    <div class="row g-3" id="galleryPreview">
        @if($book)
            @foreach($book->galleryImages as $img)
            <div class="col-md-2 gallery-item" data-id="{{ $img->id }}">
                <div class="border rounded-3 p-1 position-relative">
                    <img src="{{ $img->image_url }}" class="img-fluid rounded-2" style="height:100px;width:100%;object-fit:cover;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="deleteGalleryImage({{ $img->id }}, this)">✕</button>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-files')">
            <i class="feather-arrow-left me-1"></i> Previous
        </button>
        <button type="button" class="btn btn-primary" onclick="saveMediaAjax(this)">
            Save & Continue <i class="feather-arrow-right ms-1"></i>
        </button>
    </div>
</div>