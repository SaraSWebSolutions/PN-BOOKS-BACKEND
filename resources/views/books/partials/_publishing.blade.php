@php $selectedBadges = old('badges', $book?->badges ?? []); @endphp
<div class="tab-pane d-none" id="tab-publishing">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Publishing Status</label>
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
            <select name="publish_type" id="publish_type" class="form-control select2-field" onchange="toggleScheduleFields()">
                <option value="immediately" {{ old('publish_type', $book?->publish_type ?? 'immediately') == 'immediately' ? 'selected' : '' }}>Immediately</option>
                <option value="schedule" {{ old('publish_type', $book?->publish_type) == 'schedule' ? 'selected' : '' }}>Schedule for later</option>
            </select>
        </div>

        {{-- Schedule date/time — only relevant when publish_type = schedule --}}
        <div class="col-md-3" id="publicationDateWrap">
            <label class="form-label fw-semibold">On-sale Date</label>
            <input type="date" name="publication_date" class="form-control" value="{{ old('publication_date', $book?->publication_date?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3" id="publicationTimeWrap">
            <label class="form-label fw-semibold">On-sale Time</label>
            <input type="time" name="publication_time" class="form-control" value="{{ old('publication_time', $book?->publication_time) }}">
        </div>

        {{-- ✅ FIX: hidden "0" fallback before every checkbox below.
             Without this, unchecking a switch simply removes it from the
             request, and $request->boolean($key, true) then falls back to
             the true default instead of saving false. --}}

        <div class="col-md-6 form-check form-switch">
            <input type="hidden" name="show_in_store" value="0">
            <input class="form-check-input" type="checkbox" name="show_in_store" value="1" {{ old('show_in_store', $book?->show_in_store ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">List in Store</label>
        </div>

        <div class="col-md-6 form-check form-switch">
            <input type="hidden" name="is_featured" value="0">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" {{ old('is_featured', $book?->is_featured) ? 'checked' : '' }}>
            <label class="form-check-label">Featured</label>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input type="hidden" name="is_bestseller" value="0">
            <input class="form-check-input" type="checkbox" name="is_bestseller" value="1" {{ old('is_bestseller', $book?->is_bestseller) ? 'checked' : '' }}>
            <label class="form-check-label">Best Seller</label>
        </div>
        <div class="col-md-6 form-check form-switch">
            <input type="hidden" name="is_never_miss_to_read" value="0">
            <input class="form-check-input" type="checkbox" name="is_never_miss_to_read" value="1" {{ old('is_never_miss_to_read', $book?->is_never_miss_to_read) ? 'checked' : '' }}>
            <label class="form-check-label">Never Miss To Read</label>
        </div>

        {{-- Pre-order --}}
        <div class="col-12"><hr class="my-2"></div>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="allow_pre_order" value="0">
            <input class="form-check-input" type="checkbox" id="allow_pre_order" name="allow_pre_order" value="1" onchange="togglePreOrderFields()" {{ old('allow_pre_order', $book?->allow_pre_order) ? 'checked' : '' }}>
            <label class="form-check-label">Allow Pre-Order</label>
        </div>
        <div class="col-md-4" id="preOrderStartWrap">
            <label class="form-label fw-semibold">Pre-Order Start</label>
            <input type="date" name="pre_order_start_date" class="form-control" value="{{ old('pre_order_start_date', $book?->pre_order_start_date?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4" id="preOrderEndWrap">
            <label class="form-label fw-semibold">Pre-Order End</label>
            <input type="date" name="pre_order_end_date" class="form-control" value="{{ old('pre_order_end_date', $book?->pre_order_end_date?->format('Y-m-d')) }}">
        </div>

        {{-- Engagement settings — this is where the reported bug lived --}}
        <div class="col-12"><hr class="my-2"></div>
        <label class="form-label fw-semibold">Engagement Settings</label>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="allow_reviews" value="0">
            <input class="form-check-input" type="checkbox" name="allow_reviews" value="1" {{ old('allow_reviews', $book?->allow_reviews ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Allow Reviews</label>
        </div>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="enable_wishlist" value="0">
            <input class="form-check-input" type="checkbox" name="enable_wishlist" value="1" {{ old('enable_wishlist', $book?->enable_wishlist ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Enable Wishlist</label>
        </div>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="enable_share" value="0">
            <input class="form-check-input" type="checkbox" name="enable_share" value="1" {{ old('enable_share', $book?->enable_share ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Enable Share</label>
        </div>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="enable_compare" value="0">
            <input class="form-check-input" type="checkbox" name="enable_compare" value="1" {{ old('enable_compare', $book?->enable_compare) ? 'checked' : '' }}>
            <label class="form-check-label">Enable Compare</label>
        </div>
        <div class="col-md-4 form-check form-switch">
            <input type="hidden" name="send_email_notification" value="0">
            <input class="form-check-input" type="checkbox" name="send_email_notification" value="1" {{ old('send_email_notification', $book?->send_email_notification ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Send Email Notification</label>
        </div>

        <div class="col-12"><hr class="my-2"></div>
        <div class="col-12">
            <label class="form-label fw-semibold">Badges</label><br>
            @foreach(['new_release'=>'New Release','bestseller'=>'Bestseller','award_winner'=>'Award Winner','editors_choice'=>"Editor's Choice",'on_sale'=>'On Sale'] as $val=>$label)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="badges[]" value="{{ $val }}" id="badge_{{ $val }}" {{ in_array($val, $selectedBadges ?? []) ? 'checked' : '' }}>
                <label class="form-check-label" for="badge_{{ $val }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>

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

<script>
function toggleScheduleFields(){
    const isSchedule = document.getElementById('publish_type').value === 'schedule';
    document.getElementById('publicationDateWrap').style.display = isSchedule ? '' : 'none';
    document.getElementById('publicationTimeWrap').style.display = isSchedule ? '' : 'none';
}
function togglePreOrderFields(){
    const on = document.getElementById('allow_pre_order').checked;
    document.getElementById('preOrderStartWrap').style.display = on ? '' : 'none';
    document.getElementById('preOrderEndWrap').style.display = on ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', function(){
    toggleScheduleFields();
    togglePreOrderFields();
});
</script>