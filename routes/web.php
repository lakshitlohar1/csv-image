<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Upload routes
    Route::get('/upload', [App\Http\Controllers\UploadController::class, 'index'])->name('upload.index');
    Route::get('/upload/dashboard', [App\Http\Controllers\UploadController::class, 'dashboard'])->name('upload.dashboard');
    Route::get('/upload/test', function () {
        return view('upload.test');
    });
    Route::post('/api/upload/initialize', [App\Http\Controllers\UploadController::class, 'initializeUpload']);
    Route::post('/api/upload/chunk', [App\Http\Controllers\UploadController::class, 'uploadChunk']);
    Route::post('/api/upload/csv', [App\Http\Controllers\UploadController::class, 'importCsv']);
    Route::post('/api/simple-image-upload', [App\Http\Controllers\UploadController::class, 'simpleImageUpload']);
    Route::get('/api/upload/details/{id}', [App\Http\Controllers\UploadController::class, 'getUploadDetails']);
    
    // Discount routes (Task B)
    Route::prefix('discounts')->name('discounts.')->group(function () {
        Route::get('/', [App\Http\Controllers\DiscountController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\DiscountController::class, 'store'])->name('store');
        Route::post('/assign', [App\Http\Controllers\DiscountController::class, 'assign'])->name('assign');
        Route::post('/revoke', [App\Http\Controllers\DiscountController::class, 'revoke'])->name('revoke');
        Route::post('/eligible', [App\Http\Controllers\DiscountController::class, 'eligible'])->name('eligible');
        Route::post('/apply', [App\Http\Controllers\DiscountController::class, 'apply'])->name('apply');
        Route::get('/user-discounts', [App\Http\Controllers\DiscountController::class, 'userDiscounts'])->name('user-discounts');
    });
});
