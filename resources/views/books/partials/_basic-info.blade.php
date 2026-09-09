<div class="tab-pane" id="tab-basic">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Book Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title_field" required class="form-control" value="{{ old('title', $book->title ?? '') }}" placeholder="e.g. Ponniyin Selvan - Part 1">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $book->subtitle ?? '') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Author <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-quick-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAuthor" title="Add new author">
                            <i class="feather-plus"></i>
                        </button>
                    </label>
                    <select name="author_id" id="author_id" class="form-control select2-field" data-placeholder="Select author…">
                        <option value=""></option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" {{ old('author_id', $book->author_id ?? '') == $author->id ? 'selected' : '' }}>
                                {{ $author->pen_name ?: optional($author->user)->name ?? 'Unnamed Author' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Publisher <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-quick-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasPublisher" title="Add new publisher">
                            <i class="feather-plus"></i>
                        </button>
                    </label>
                    <select name="publisher_id" id="publisher_id" class="form-control select2-field" data-placeholder="Select publisher…">
                        <option value=""></option>
                        @foreach($publishers as $publisher)
                            <option value="{{ $publisher->id }}" {{ old('publisher_id', $book->publisher_id ?? '') == $publisher->id ? 'selected' : '' }}>
                                {{ $publisher->company_name ?: optional($publisher->user)->name ?? 'Unnamed Publisher' }}
                            </option>
                        @endforeach
                    </select>
                </div>

              <div class="col-md-6">
    <label class="form-label fw-semibold">ISBN <span class="text-danger">*</span></label>
    <input type="text" name="isbn" id="isbn_field" required class="form-control" value="{{ old('isbn', $book->isbn ?? '') }}" placeholder="e.g. 9781976556227">
    {{-- Reserves real layout space when shown, so it pushes Subcategory
         down instead of floating over it --}}
    <div class="isbn-popup-wrap" id="isbnPopupWrap">
        <div class="isbn-popup" id="isbnPopup"></div>
    </div>
</div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Category <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-quick-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategory" title="Add new category">
                            <i class="feather-plus"></i>
                        </button>
                    </label>
                    <select name="category_id" id="category_id" class="form-control select2-field" data-placeholder="Select category…">
                        <option value=""></option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $book->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name_en }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Subcategory</span>
                        <button type="button" class="btn btn-sm btn-quick-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSubcategory" title="Add new subcategory">
                            <i class="feather-plus"></i>
                        </button>
                    </label>
                    <select name="subcategory_id" id="subcategory_id" class="form-control select2-field" data-placeholder="Select subcategory…">
                        @if(isset($book) && $book->subcategory)
                            <option value="{{ $book->subcategory->id }}" selected>{{ $book->subcategory->name_en }}</option>
                        @else
                            <option value=""></option>
                        @endif
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Series</span>
                        <button type="button" class="btn btn-sm btn-quick-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSeries" title="Add new series">
                            <i class="feather-plus"></i>
                        </button>
                    </label>
                    <select name="series_id" id="series_id" class="form-control select2-field" data-placeholder="Select series (optional)…">
                        @if(isset($book) && $book->series)
                            <option value="{{ $book->series->id }}" selected>{{ $book->series->name }}</option>
                        @else
                            <option value=""></option>
                        @endif
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Language(s) <span class="text-danger">*</span></label>
                    <select name="languages[]" class="form-control select2-field" data-placeholder="Select language(s)…" multiple>
                        @php
                            $selectedLangs = old('languages', isset($book) ? $book->languages->pluck('id')->toArray() : []);
                        @endphp
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}" {{ in_array($lang->id, $selectedLangs) ? 'selected' : '' }}>
                                {{ $lang->name }}{{ $lang->native_name ? ' ('.$lang->native_name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Short Description</label>
                    <textarea name="short_description" maxlength="160" class="form-control" rows="2">{{ old('short_description', $book->short_description ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description_field" required class="form-control" rows="6">{{ old('description', $book->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

      <div class="col-md-4">
    <label class="form-label fw-semibold">Book Cover</label>
    <div class="border rounded-3 p-3 text-center">
        <img id="coverPreview"
             src="{{ $book->cover_image_url ?? 'https://placehold.co/220x300?text=Book+Cover' }}"
             class="img-fluid rounded-3 mb-3"
             style="max-height:280px; cursor:pointer;"
             onclick="document.getElementById('cover_image').click()"
             title="Click to change cover">
        <input type="file" name="cover_image" id="cover_image" accept="image/*" class="form-control">
    </div>
</div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        {{-- ✅ Now calls saveBasicInfoAjax(), which checks ISBN + Description before saving --}}
        <button type="button" class="btn btn-primary" onclick="saveBasicInfoAjax(this)">Save & Continue <i class="feather-arrow-right ms-1"></i></button>
    </div>
</div>

{{-- ═══════════════════════════ Quick-Add Offcanvas Panels ═══════════════════════════ --}}

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCategory">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label">Category Name (English) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="qc_category_name_en" placeholder="e.g. Fiction">
        </div>
        <div class="mb-3">
            <label class="form-label">Category Name (Malay)</label>
            <input type="text" class="form-control" id="qc_category_name_ms">
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="quickCreate('category')">
            <i class="feather-save me-1"></i> Save & Select
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSubcategory">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add Subcategory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label">Parent Category <span class="text-danger">*</span></label>
            <select class="form-control" id="qc_subcategory_category_id">
                <option value="">Select category…</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Subcategory Name (English) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="qc_subcategory_name_en" placeholder="e.g. Sci-Fi">
        </div>
        <div class="mb-3">
            <label class="form-label">Subcategory Name (Malay)</label>
            <input type="text" class="form-control" id="qc_subcategory_name_ms">
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="quickCreate('subcategory')">
            <i class="feather-save me-1"></i> Save & Select
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSeries">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add Series</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label">Publisher <span class="text-danger">*</span></label>
            <select class="form-control" id="qc_series_publisher_id">
                <option value="">Select publisher…</option>
                @foreach($publishers as $publisher)
                    <option value="{{ $publisher->id }}">{{ $publisher->company_name ?? optional($publisher->user)->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Series Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="qc_series_name" placeholder="e.g. Harry Potter">
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="quickCreate('series')">
            <i class="feather-save me-1"></i> Save & Select
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAuthor">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add Author</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label">Full Name / Pen Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="qc_author_name" placeholder="e.g. J. Smith">
        </div>
        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="qc_author_email" placeholder="author@example.com">
        </div>
        <div class="form-text mb-3">A temporary password will be generated. Full profile details can be edited later from Authors.</div>
        <button type="button" class="btn btn-primary w-100" onclick="quickCreate('author')">
            <i class="feather-save me-1"></i> Save & Select
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPublisher">
    <div class="offcanvas-header">
        <h5 class="mb-0">Add Publisher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <label class="form-label">Company Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="qc_publisher_name" placeholder="e.g. Kalki Publications">
        </div>
        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="qc_publisher_email" placeholder="publisher@example.com">
        </div>
        <div class="form-text mb-3">A temporary password will be generated. Full company details can be edited later from Publishers.</div>
        <button type="button" class="btn btn-primary w-100" onclick="quickCreate('publisher')">
            <i class="feather-save me-1"></i> Save & Select
        </button>
    </div>
</div>