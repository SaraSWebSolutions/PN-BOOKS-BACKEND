{{-- resources/views/books/_form.blade.php
     Included by both create.blade.php and edit.blade.php.
     $book is null on create. --}}

<div class="row g-3">

    {{-- Title --}}
    <div class="col-md-8">
        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $book->title ?? '') }}" required>
        <div class="field-error" id="titleError"></div>
    </div>

    {{-- Book Type --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Book Type <span class="text-danger">*</span></label>
        <select name="book_type" id="book_type" class="form-control" required>
            @foreach(['physical' => 'Physical Book', 'ebook' => 'eBook', 'audiobook' => 'Audiobook'] as $val => $label)
                <option value="{{ $val }}" {{ old('book_type', $book->book_type ?? 'physical') == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="field-error" id="book_typeError"></div>
    </div>

    {{-- ISBN / SKU --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">ISBN</label>
        <input type="text" name="isbn" class="form-control" value="{{ old('isbn', $book->isbn ?? '') }}">
        <div class="field-error" id="isbnError"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">SKU</label>
        <input type="text" name="sku" class="form-control" value="{{ old('sku', $book->sku ?? '') }}">
        <div class="field-error" id="skuError"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Language</label>
        <input type="text" name="language" class="form-control" value="{{ old('language', $book->language ?? 'English') }}">
        <div class="field-error" id="languageError"></div>
    </div>

    {{-- Category / Subcategory / Series --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
        <select name="category_id" id="category_id" class="form-control" required>
            <option value="">Select category…</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ old('category_id', $book->category_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <div class="field-error" id="category_idError"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Subcategory</label>
        <select name="subcategory_id" id="subcategory_id" class="form-control">
            <option value="">Select subcategory…</option>
            @foreach($subcategories as $sc)
                <option value="{{ $sc->id }}" data-category="{{ $sc->category_id }}"
                    {{ old('subcategory_id', $book->subcategory_id ?? '') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
            @endforeach
        </select>
        <div class="field-error" id="subcategory_idError"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Series</label>
        <select name="series_id" class="form-control">
            <option value="">— None —</option>
            @foreach($seriesList as $s)
                <option value="{{ $s->id }}" {{ old('series_id', $book->series_id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <div class="field-error" id="series_idError"></div>
    </div>

    {{-- Author / Publisher (users with role author/publisher) --}}
    <div class="col-md-6">
        <label class="form-label fw-semibold">Author <span class="text-danger">*</span></label>
        <select name="author_id" class="form-control" required>
            <option value="">Select author…</option>
            @foreach($authors as $a)
                <option value="{{ $a->id }}" {{ old('author_id', $book->author_id ?? '') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
            @endforeach
        </select>
        <div class="field-error" id="author_idError"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Publisher</label>
        <select name="publisher_id" class="form-control">
            <option value="">— None —</option>
            @foreach($publishers as $p)
                <option value="{{ $p->id }}" {{ old('publisher_id', $book->publisher_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <div class="field-error" id="publisher_idError"></div>
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $book->description ?? '') }}</textarea>
        <div class="field-error" id="descriptionError"></div>
    </div>

    {{-- Pages / Duration --}}
    <div class="col-md-3" id="pagesWrap">
        <label class="form-label fw-semibold">Pages</label>
        <input type="number" name="pages" min="0" class="form-control" value="{{ old('pages', $book->pages ?? '') }}">
        <div class="field-error" id="pagesError"></div>
    </div>
    <div class="col-md-3" id="durationWrap">
        <label class="form-label fw-semibold">Duration (minutes)</label>
        <input type="number" name="duration_minutes" min="0" class="form-control" value="{{ old('duration_minutes', $book->duration_minutes ?? '') }}">
        <div class="field-error" id="duration_minutesError"></div>
    </div>

    {{-- Price / Discount --}}
    <div class="col-md-3">
        <label class="form-label fw-semibold">Price (₹) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $book->price ?? '') }}" required>
        <div class="field-error" id="priceError"></div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Discount Price (₹)</label>
        <input type="number" step="0.01" min="0" name="discount_price" class="form-control" value="{{ old('discount_price', $book->discount_price ?? '') }}">
        <div class="field-error" id="discount_priceError"></div>
    </div>

    {{-- Stock (physical only) --}}
    <div class="col-md-3" id="stockWrap">
        <label class="form-label fw-semibold">Stock Quantity</label>
        <input type="number" min="0" name="stock_quantity" id="stock_quantity" class="form-control" value="{{ old('stock_quantity', $book->stock_quantity ?? 0) }}">
        <div class="field-error" id="stock_quantityError"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Publication Date</label>
        <input type="date" name="publication_date" class="form-control" value="{{ old('publication_date', isset($book->publication_date) ? $book->publication_date->format('Y-m-d') : '') }}">
        <div class="field-error" id="publication_dateError"></div>
    </div>

    {{-- Files --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Cover Image</label>
        <input type="file" name="cover_image" class="form-control" accept="image/*">
        @if(!empty($book?->cover_image))
            <img src="{{ asset($book->cover_image) }}" class="mt-2" style="width:70px;height:90px;object-fit:cover;border-radius:6px;">
        @endif
        <div class="field-error" id="cover_imageError"></div>
    </div>
    <div class="col-md-4" id="digitalFileWrap">
        <label class="form-label fw-semibold">Digital File (ebook/audiobook)</label>
        <input type="file" name="digital_file" class="form-control" accept=".pdf,.epub,.mp3,.zip">
        @if(!empty($book?->digital_file_path))
            <small class="text-muted d-block mt-1">Current file uploaded ✔</small>
        @endif
        <div class="field-error" id="digital_fileError"></div>
    </div>
    <div class="col-md-4" id="sampleFileWrap">
        <label class="form-label fw-semibold">Sample/Preview File</label>
        <input type="file" name="sample_file" class="form-control" accept=".pdf,.epub,.mp3">
        <div class="field-error" id="sample_fileError"></div>
    </div>

    {{-- Status / Featured --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-control" required>
            @foreach(['draft'=>'Draft','published'=>'Published','inactive'=>'Inactive'] as $val=>$label)
                <option value="{{ $val }}" {{ old('status', $book->status ?? 'draft') == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="field-error" id="statusError"></div>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                   {{ old('is_featured', $book->is_featured ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_featured">Featured Book</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Show/hide fields based on book_type
function toggleBookTypeFields() {
    const type = document.getElementById('book_type').value;
    document.getElementById('stockWrap').style.display        = type === 'physical' ? '' : 'none';
    document.getElementById('pagesWrap').style.display         = type === 'audiobook' ? 'none' : '';
    document.getElementById('durationWrap').style.display      = type === 'audiobook' ? '' : 'none';
    document.getElementById('digitalFileWrap').style.display   = type === 'physical' ? 'none' : '';
    document.getElementById('sampleFileWrap').style.display    = type === 'physical' ? 'none' : '';
}
document.getElementById('book_type').addEventListener('change', toggleBookTypeFields);
document.addEventListener('DOMContentLoaded', toggleBookTypeFields);

// Filter subcategory options by selected category
function filterSubcategories() {
    const catId = document.getElementById('category_id').value;
    const subSelect = document.getElementById('subcategory_id');
    Array.from(subSelect.options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden = catId && opt.dataset.category !== catId;
    });
    if (subSelect.selectedOptions[0]?.hidden) subSelect.value = '';
}
document.getElementById('category_id').addEventListener('change', filterSubcategories);
document.addEventListener('DOMContentLoaded', filterSubcategories);
</script>
@endpush
