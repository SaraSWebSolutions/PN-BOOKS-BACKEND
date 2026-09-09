<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ContactController;





use App\Http\Controllers\DashboardController;



use Illuminate\Support\Facades\File;



use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\BookFormatController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\QuickCreateController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebsiteContentController;


Route::get('/', fn() => redirect()->route('login'));

// Auth Routes
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

// Protected

// routes/web.php

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    Route::resource('users', UserController::class);
 
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');

    Route::prefix('contacts')->name('contacts.')->group(function () {

    // Standard resource routes
    Route::get('/',            [ContactController::class, 'index'])   ->name('index');
    Route::get('/create',      [ContactController::class, 'create'])  ->name('create');
    Route::post('/',           [ContactController::class, 'store'])   ->name('store');
    Route::get('/{contact}',   [ContactController::class, 'show'])    ->name('show');
    Route::get('/{contact}/edit',   [ContactController::class, 'edit'])   ->name('edit');
    Route::put('/{contact}',        [ContactController::class, 'update']) ->name('update');
    Route::delete('/{contact}',     [ContactController::class, 'destroy'])->name('destroy');
        Route::get('/{contact}/ledger', [ContactController::class, 'customerLedger'])->name('customer-ledger');
Route::get('/{contact}/supplier-ledger', [ContactController::class, 'supplierLedger'])->name('supplier-ledger');
    // ── Filtered views ───────────────────────────────────────────────
    // /contacts/customers  → only customer + both types
    Route::get('/filter/customers', [ContactController::class, 'customers'])->name('customers');

    // /contacts/suppliers  → only supplier type
    Route::get('/filter/suppliers', [ContactController::class, 'suppliers'])->name('suppliers');
});

Route::get('contacts/{contact}/json', [ContactController::class, 'json'])
     ->name('contacts.json');

     // web.php
Route::post('sales/{sale}/link-customer', [ContactController::class, 'linkCustomer']);


 Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

 

 
// ── Categories ────────────────────────────────────────────
Route::get   ('categories',                 [CategoryController::class, 'index'])->name('categories.index');
Route::post  ('categories',                 [CategoryController::class, 'store'])->name('categories.store');
Route::put   ('categories/{category}',      [CategoryController::class, 'update'])->name('categories.update');
Route::patch ('categories/{category}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');
Route::delete('categories/{category}',      [CategoryController::class, 'destroy'])->name('categories.destroy');

// ── Subcategories ─────────────────────────────────────────
Route::get   ('subcategories',                    [SubcategoryController::class, 'index'])->name('subcategories.index');
Route::get   ('subcategories/by-category/{category}', [SubcategoryController::class, 'byCategory'])->name('subcategories.byCategory');
Route::post  ('subcategories',                    [SubcategoryController::class, 'store'])->name('subcategories.store');
Route::put   ('subcategories/{subcategory}',      [SubcategoryController::class, 'update'])->name('subcategories.update');
Route::patch ('subcategories/{subcategory}/toggle', [SubcategoryController::class, 'toggleStatus'])->name('subcategories.toggle');
Route::delete('subcategories/{subcategory}',      [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

// ── Series ─────────────────────────────────────────────────
Route::get   ('series',            [SeriesController::class, 'index'])->name('series.index');
Route::post  ('series',            [SeriesController::class, 'store'])->name('series.store');
Route::put   ('series/{series}',   [SeriesController::class, 'update'])->name('series.update');
Route::patch ('series/{series}/toggle', [SeriesController::class, 'toggleStatus'])->name('series.toggle');
Route::delete('series/{series}',   [SeriesController::class, 'destroy'])->name('series.destroy');

// ── Books ──────────────────────────────────────────────────
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
Route::patch('/books/{book}/featured', [BookController::class, 'toggleFeatured'])->name('books.featured');

// Wizard save endpoints (AJAX, one per tab)
Route::post('/books/basic', [BookController::class, 'storeBasic'])->name('books.basic.store');
Route::post('/books/{book}/basic', [BookController::class, 'storeBasic'])->name('books.basic.update');
Route::post('/books/{book}/formats', [BookController::class, 'storeFormats'])->name('books.formats.store');
Route::post('/books/{book}/files', [BookController::class, 'storeFiles'])->name('books.files.store');
Route::post('/books/{book}/pricing', [BookController::class, 'storePricing'])->name('books.pricing.store');
Route::post('/books/{book}/inventory', [BookController::class, 'storeInventory'])->name('books.inventory.store');
Route::post('/books/{book}/shipping', [BookController::class, 'storeShipping'])->name('books.shipping.store');
Route::post('/books/{book}/seo', [BookController::class, 'storeSeo'])->name('books.seo.store');
Route::post('/books/{book}/publish', [BookController::class, 'publish'])->name('books.publish');
Route::get('/books/search-select', [BookController::class, 'searchForSelect'])->name('books.searchSelect');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// AJAX dropdown helpers
Route::get('/categories/{category}/subcategories', [BookController::class, 'subcategoriesByCategory'])->name('categories.subcategories');
Route::get('/publishers/{publisher}/series', [BookController::class, 'seriesByPublisher'])->name('publishers.series');

Route::post('/books/{book}/media', [BookController::class, 'storeMedia'])->name('books.media.store');


Route::delete('/books/gallery/{image}', [BookController::class, 'deleteGalleryImage'])->name('books.gallery.delete');
Route::post('/books/{book}/related', [BookController::class, 'storeRelated'])->name('books.related.store');




// ── Quick-create (offcanvas) endpoints ──────────────────────
Route::post('/quick/category', [QuickCreateController::class, 'category'])->name('quick.category');
Route::post('/quick/subcategory', [QuickCreateController::class, 'subcategory'])->name('quick.subcategory');
Route::post('/quick/series', [QuickCreateController::class, 'series'])->name('quick.series');
Route::post('/quick/author', [QuickCreateController::class, 'author'])->name('quick.author');
Route::post('/quick/publisher', [QuickCreateController::class, 'publisher'])->name('quick.publisher');


 // Author Management
    Route::get('authors', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('authors/create', [AuthorController::class, 'create'])->name('authors.create');
    Route::post('authors', [AuthorController::class, 'store'])->name('authors.store');
    Route::get('authors/{author}', [AuthorController::class, 'show'])->name('authors.show');
    Route::get('authors/{author}/edit', [AuthorController::class, 'edit'])->name('authors.edit');
    Route::put('authors/{author}', [AuthorController::class, 'update'])->name('authors.update');
    Route::delete('authors/{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');

    // Publisher Management
    Route::get('publishers', [PublisherController::class, 'index'])->name('publishers.index');
    Route::get('publishers/create', [PublisherController::class, 'create'])->name('publishers.create');
    Route::post('publishers', [PublisherController::class, 'store'])->name('publishers.store');
    Route::get('publishers/{publisher}', [PublisherController::class, 'show'])->name('publishers.show');
    Route::get('publishers/{publisher}/edit', [PublisherController::class, 'edit'])->name('publishers.edit');
    Route::put('publishers/{publisher}', [PublisherController::class, 'update'])->name('publishers.update');
    Route::delete('publishers/{publisher}', [PublisherController::class, 'destroy'])->name('publishers.destroy');

    // Customer Management
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::patch('customers/{customer}/toggle-status', [App\Http\Controllers\CustomerController::class, 'toggleStatus'])
    ->name('customers.toggle-status');
    

Route::get('/currencies',              [CurrencyController::class, 'index'])->name('currencies.index');
Route::post('/currencies',             [CurrencyController::class, 'store'])->name('currencies.store');
Route::put('/currencies/{currency}',   [CurrencyController::class, 'update'])->name('currencies.update');
Route::patch('/currencies/{currency}/toggle', [CurrencyController::class, 'toggleStatus'])->name('currencies.toggle');
Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

 
Route::prefix('book-formats')->name('book-formats.')->group(function () {
    Route::get('/', [BookFormatController::class, 'index'])->name('index');
    Route::post('/', [BookFormatController::class, 'store'])->name('store');
    Route::put('/{bookFormat}', [BookFormatController::class, 'update'])->name('update');
    Route::patch('/{bookFormat}/toggle', [BookFormatController::class, 'toggleStatus'])->name('toggle');
    Route::delete('/{bookFormat}', [BookFormatController::class, 'destroy'])->name('destroy');
});

 Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
    Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
    Route::put('/countries/{country}', [CountryController::class, 'update'])->name('countries.update');
    Route::patch('/countries/{country}/toggle', [CountryController::class, 'toggleStatus'])->name('countries.toggle');
    Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
    Route::get('/countries-active-list', [CountryController::class, 'activeList'])->name('countries.active-list');

  Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
    Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store');
    Route::put('/taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
    Route::patch('/taxes/{tax}/toggle', [TaxController::class, 'toggleStatus'])->name('taxes.toggle');
    Route::delete('/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');

Route::middleware(['auth'])->prefix('admin/website')->name('website.')->group(function () {
    Route::get('/',                          [WebsiteContentController::class, 'index'])->name('index');
    Route::get('/settings',                  [WebsiteContentController::class, 'settings'])->name('settings');
    Route::post('/settings',                 [WebsiteContentController::class, 'storeSettings'])->name('settings.store');
    Route::get('/{page:page_key}',           [WebsiteContentController::class, 'show'])->name('show');

    Route::post('/{page}/banners',           [WebsiteContentController::class, 'storeBanner'])->name('banners.store');
    Route::patch('/banners/{banner}/toggle', [WebsiteContentController::class, 'toggleBanner'])->name('banners.toggle');
    Route::post('/banners/reorder',          [WebsiteContentController::class, 'reorderBanners'])->name('banners.reorder');
    Route::delete('/banners/{banner}',       [WebsiteContentController::class, 'destroyBanner'])->name('banners.destroy');
Route::post('/pages', [WebsiteContentController::class, 'storePage'])->name('pages.store');
Route::post('/sections/{section}/items', [WebsiteContentController::class, 'storeSectionItem'])->name('sections.items.store');
Route::delete('/section-items/{item}', [WebsiteContentController::class, 'destroySectionItem'])->name('sections.items.destroy');
    Route::post('/{page}/sections',          [WebsiteContentController::class, 'storeSection'])->name('sections.store');
    Route::delete('/sections/{section}',     [WebsiteContentController::class, 'destroySection'])->name('sections.destroy');
});


Route::get('books/isbn-check/{isbn}', [App\Http\Controllers\BookController::class, 'checkIsbn'])
    ->name('books.isbn.check');
     
Route::prefix('languages')->name('languages.')->group(function () {
    Route::get('/',              [LanguageController::class, 'index'])->name('index');
    Route::get('/active-list',   [LanguageController::class, 'activeList'])->name('active-list');
    Route::post('/',             [LanguageController::class, 'store'])->name('store');
    Route::put('/{language}',    [LanguageController::class, 'update'])->name('update');
    Route::patch('/{language}/toggle', [LanguageController::class, 'toggleStatus'])->name('toggle');
    Route::delete('/{language}', [LanguageController::class, 'destroy'])->name('destroy');
});

  Route::get('/uploads/{folder}/{filename}', function ($folder, $filename) {
    $path = base_path("uploads/{$folder}/{$filename}");
    abort_unless(file_exists($path), 404);
    return response()->file($path);
})->where('filename', '.*')->name('uploads.serve');



Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('updateStatus');
});






    













































 







 












  
});

