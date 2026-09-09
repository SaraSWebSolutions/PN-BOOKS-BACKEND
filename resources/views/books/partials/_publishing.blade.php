@php $selectedBadges = old('badges', $book?->badges ?? []); @endphp
<div class="tab-pane d-none" id="tab-publishing">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-control select2-field">
                <option value="published" {{ old('status', $book?->status) == 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ old('status', $book?->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="private" {{ old('status', $book?->status) == 'private' ? 'selected' : '' }}>Private</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Visibility</label>
            <select name="visibility" class="form-control select2-field">
                <option value="public" {{ old('visibility', $book?->visibility ?? 'public') == 'public' ? 'selected' : '' }}>Public</option>
                <option value="private" {{ old('visibility', $book?->visibility) == 'private' ? 'selected' : '' }}>Private</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Publish</label>
            <select name="publish_type" class="form-control select2-field">
                <option value="immediately" {{ old('publish_type', $book?->publish_type ?? 'immediately') == 'immediately' ? 'selected' : '' }}>Immediately</option>
                <option value="schedule" {{ old('publish_type', $book?->publish_type) == 'schedule' ? 'selected' : '' }}>Schedule for later</option>
            </select>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" {{ old('is_featured', $book?->is_featured) ? 'checked' : '' }}>
            <label class="form-check-label">Featured</label>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_bestseller" {{ old('is_bestseller', $book?->is_bestseller) ? 'checked' : '' }}>
            <label class="form-check-label">Best Seller</label>
        </div>
        <div class="col-md-6 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="is_never_miss_to_read" {{ old('is_never_miss_to_read', $book?->is_never_miss_to_read) ? 'checked' : '' }}>
    <label class="form-check-label">Never Miss To Read</label>
</div>
        <div class="col-12">
            <label class="form-label fw-semibold">Badges</label><br>
            @foreach(['new_release'=>'New Release','bestseller'=>'Bestseller','award_winner'=>'Award Winner','editors_choice'=>"Editor's Choice",'on_sale'=>'On Sale'] as $val=>$label)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="badges[]" value="{{ $val }}" id="badge_{{ $val }}" {{ in_array($val, $selectedBadges ?? []) ? 'checked' : '' }}>
                <label class="form-check-label" for="badge_{{ $val }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>

        {{-- ✅ NEW: Related Books --}}
        <div class="col-12">
            <hr class="my-2">
            <label class="form-label fw-semibold">Related Books</label>
            <select id="related_books_select" name="related_books[]" class="form-control select2-field" multiple data-placeholder="Search and select related books…">
                @if($book)
                    @foreach($book->relatedBooks as $rb)
                        <option value="{{ $rb->id }}" selected>{{ $rb->title }}</option>
                    @endforeach
                @endif
            </select>
            <div class="form-text fs-12">These will show under "You May Also Like" on the book page.</div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light-brand" onclick="goToTab('tab-seo')"><i class="feather-arrow-left me-1"></i> Previous</button>
        <button type="button" class="btn btn-primary" onclick="savePublishAjax(this)"><i class="feather-check me-1"></i> Save Book</button>
    </div>
</div>