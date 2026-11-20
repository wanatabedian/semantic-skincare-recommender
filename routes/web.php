<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SparqlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ProductComparisonController;
use App\Http\Controllers\RecommendationController;

// ==========================================
// AREA PUBLIK (Tampilan User - Tanpa Login)
// ==========================================
Route::get('/', function () {
    return view('user.landing'); 
});

Route::get('/kuis', [QuizController::class, 'index'])->name('kuis');
Route::get('/bandingkan', [ProductComparisonController::class, 'index'])->name('bandingkan');
Route::get('/rekomendasi', [RecommendationController::class, 'index'])->name('rekomendasi');
Route::get('/api/quiz-options', [QuizController::class, 'options'])->name('api.quiz-options');
Route::get('/api/products/compare', [ProductComparisonController::class, 'products'])->name('api.products.compare');
Route::get('/api/products', [ProductComparisonController::class, 'catalog'])->name('api.products');
Route::post('/api/quiz/analyze', [QuizController::class, 'submit'])->name('api.quiz.analyze');

// Endpoint untuk sistem semantik SPARQL
Route::post('/api/recommendation', [SparqlController::class, 'getRecommendation']);


// ==========================================
// AREA AUTENTIKASI ADMIN
// ==========================================
Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// AREA PRIVAT (Tampilan Admin - Wajib Login)
// ==========================================
Route::middleware(['auth'])->prefix('admin')->group(function () {
    
    // Halaman Dashboard khusus Admin
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    
});

// === Orang 4: Produk & Linked Data ===
Route::get('/produk/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('produk.detail');
