<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\IncompleteOrderController as AdminIncompleteOrderController;
use App\Http\Controllers\Admin\IncompleteOrderConversionController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TrackingController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncompleteOrderController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Dynamic Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/products/{product:slug}', [ProductPageController::class, 'show'])->name('products.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::post('/order', [LandingPageController::class, 'order'])->name('landing.order')->middleware('throttle:10,1');
Route::post('/incomplete-order', [IncompleteOrderController::class, 'store'])->name('landing.incomplete')->middleware('throttle:60,1');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Admin Dashboard Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Orders Management
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');

    // Incomplete orders (checkout forms started but not submitted)
    Route::get('/incomplete-orders', [AdminIncompleteOrderController::class, 'index'])->name('admin.incomplete-orders.index');
    Route::patch('/incomplete-orders/{incompleteOrder}/status', [AdminIncompleteOrderController::class, 'updateStatus'])->name('admin.incomplete-orders.status');
    Route::delete('/incomplete-orders/{incompleteOrder}', [AdminIncompleteOrderController::class, 'destroy'])->name('admin.incomplete-orders.destroy');
    Route::get('/incomplete-orders/{incompleteOrder}/order', [IncompleteOrderConversionController::class, 'create'])->name('admin.incomplete-orders.convert');
    Route::post('/incomplete-orders/{incompleteOrder}/order', [IncompleteOrderConversionController::class, 'store'])->name('admin.incomplete-orders.convert.store');

    // Tracking: Meta Conversions API and campaign results
    Route::get('/tracking', [TrackingController::class, 'index'])->name('admin.tracking.index');
    Route::put('/tracking', [TrackingController::class, 'update'])->name('admin.tracking.update');
    Route::post('/tracking/test', [TrackingController::class, 'test'])->name('admin.tracking.test');

    // Products Management
    Route::resource('products', ProductController::class)->except('show')->names('admin.products');

    // Landing page content
    Route::resource('categories', CategoryController::class)->except(['create', 'show'])->names('admin.categories');
    Route::resource('reviews', ReviewController::class)->except(['create', 'show'])->names('admin.reviews');
    Route::resource('faqs', FaqController::class)->except(['create', 'show'])->names('admin.faqs');

    // Profile & Password
    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password')->middleware('throttle:5,1');

    // Settings & Meta Pixel Management
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update');
});
