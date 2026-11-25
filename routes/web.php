<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SparqlController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RecommendationController;

// ==========================================
// AREA PUBLIK (Tampilan User - Tanpa Login)
// ==========================================
Route::get('/', [\App\Http\Controllers\CatalogController::class, 'landing'])->name('home');

Route::view('/tentang', 'user.tentang')->name('tentang');
Route::get('/kuis', [QuizController::class, 'index'])->name('kuis');
Route::get('/rekomendasi', [RecommendationController::class, 'index'])->name('rekomendasi');
Route::get('/api/quiz-options', [QuizController::class, 'options'])->name('api.quiz-options');
Route::post('/api/quiz/analyze', [QuizController::class, 'submit'])->name('api.quiz.analyze');

// === Orang 3: Mesin Rekomendasi (JSON) ===
// GET untuk link/bookmark/Postman; POST tanpa CSRF supaya bisa dipanggil sebagai API murni.
Route::get('/api/recommendation', [SparqlController::class, 'getRecommendation'])->name('api.recommendation');
Route::post('/api/recommendation', [SparqlController::class, 'getRecommendation'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);


// === Orang 4: Produk & Linked Data ===
Route::get('/produk/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('produk.detail');

// === Orang 5: Eksplorasi Katalog ===
Route::get('/katalog', [\App\Http\Controllers\CatalogController::class, 'index'])->name('katalog');
Route::get('/bahan', [\App\Http\Controllers\IngredientController::class, 'index'])->name('bahan');
Route::get('/bahan/{id}', [\App\Http\Controllers\IngredientController::class, 'show'])->where('id', '[A-Za-z0-9_-]+')->name('bahan.detail');
