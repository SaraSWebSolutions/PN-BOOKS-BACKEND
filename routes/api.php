<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CountryApiController;
use App\Http\Controllers\Api\LanguageApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\PublicWebsiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Api\WishlistApiController;
use App\Http\Controllers\Api\BookReviewApiController;
use App\Http\Controllers\Api\StoreStatsApiController;
use App\Http\Controllers\Api\ContentPreferenceApiController;
use App\Http\Controllers\Api\FaqApiController;
use App\Http\Controllers\Api\SupportTicketApiController;
use App\Http\Controllers\Api\SupportSubjectApiController;
use App\Http\Controllers\Api\SeriesApiController;



/* ───────────── Public routes (no login required) ───────────── */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

Route::get('/categories', [CategoryApiController::class, 'index']);

// NEW — dropdown master data for register / profile forms
Route::get('/countries', [CountryApiController::class, 'index']);
Route::get('/languages', [LanguageApiController::class, 'index']);

Route::get('/books/stats', [BookApiController::class, 'stats']);  
Route::get('/books/recommended', [BookApiController::class, 'recommended']);
Route::get('/books/formats', [BookApiController::class, 'formats']); // ⚠️ above {idOrSlug}
Route::get('/books', [BookApiController::class, 'index']);           // ✅ ONE endpoint, all tabs
Route::get('/books/{idOrSlug}', [BookApiController::class, 'show']);


Route::get('/website/page/{key}',        [PublicWebsiteController::class, 'page']);
Route::get('/website/banners/{pageKey}', [PublicWebsiteController::class, 'banners']);
Route::get('/website/banner/{id}',       [PublicWebsiteController::class, 'bannerDetail']);
Route::get('/website/settings',          [PublicWebsiteController::class, 'settings']);
Route::get('/home', [HomeApiController::class, 'index']);

Route::get('/search', [SearchApiController::class, 'index']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
Route::get('/books/{book}/reviews', [BookReviewApiController::class, 'index']);

Route::get('/website/stats/glance', [StoreStatsApiController::class, 'glance']);
Route::get('/faqs', [FaqApiController::class, 'index']);
Route::get('/support/subjects', [SupportSubjectApiController::class, 'index']);
Route::post('/support/tickets', [SupportTicketApiController::class, 'store']);
Route::get('/series', [SeriesApiController::class, 'index']);
Route::get('/series/{idOrSlug}', [SeriesApiController::class, 'show']);


/* ───────────── Authenticated routes (any logged-in role) ───────────── */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // NEW — customer profile update (name/phone/address/gender/dob/country/language/photo)
    // Route::post('/profile', [AuthController::class, 'updateProfile']);
    // Route::put('/profile', [AuthController::class, 'updateProfile']);
Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
    // Cart
    Route::get('/cart', [CartApiController::class, 'index']);
    Route::post('/cart/items', [CartApiController::class, 'addItem']);
    Route::patch('/cart/items/{item}', [CartApiController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartApiController::class, 'removeItem']);
    Route::delete('/cart', [CartApiController::class, 'clear']);
 
    // Orders & checkout
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::get('/orders/{order}', [OrderApiController::class, 'show']);
    Route::post('/checkout', [OrderApiController::class, 'checkout']);
    // inside the auth:sanctum group, near your other /orders routes
Route::get('/my-books', [OrderApiController::class, 'myBooks']);

Route::post('/change-password', [AuthController::class, 'changePassword']);
    // Wishlist
    Route::get('/wishlist', [WishlistApiController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistApiController::class, 'toggle']);
    Route::get('/wishlist/check/{book}', [WishlistApiController::class, 'check']);
    Route::delete('/wishlist/{book}', [WishlistApiController::class, 'destroy']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // inside your authenticated admin/staff route group
Route::patch('/orders/{order}/mark-cod-paid', [OrderApiController::class, 'markCodAsPaid']);// creates order + Atome checkout_url

   Route::post('/buy-now', [OrderApiController::class, 'buyNow']);


 Route::post('/books/{book}/reviews', [BookReviewApiController::class, 'store']);
    Route::post('/reviews/{review}/helpful', [BookReviewApiController::class, 'markHelpful']);


    Route::get('/profile/content-preferences',  [ContentPreferenceApiController::class, 'show']);
Route::match(['put', 'post'], '/profile/content-preferences', [ContentPreferenceApiController::class, 'update']);
    
});