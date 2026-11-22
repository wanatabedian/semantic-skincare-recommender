<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SparqlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ProductComparisonController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\OntologyController;

// ==========================================
// AREA PUBLIK (Tampilan User - Tanpa Login)
// ==========================================
Route::get('/', function () {
    return view('user.landing'); 
});

Route::view('/tentang', 'user.tentang')->name('tentang');
Route::get('/kuis', [QuizController::class, 'index'])->name('kuis');
Route::get('/bandingkan', [ProductComparisonController::class, 'index'])->name('bandingkan');
Route::get('/rekomendasi', [RecommendationController::class, 'index'])->name('rekomendasi');
Route::get('/api/quiz-options', [QuizController::class, 'options'])->name('api.quiz-options');
Route::get('/api/products/compare', [ProductComparisonController::class, 'products'])->name('api.products.compare');
Route::get('/api/products', [ProductComparisonController::class, 'catalog'])->name('api.products');
Route::post('/api/quiz/analyze', [QuizController::class, 'submit'])->name('api.quiz.analyze');

// === Orang 3: Mesin Rekomendasi (JSON) ===
// GET untuk link/bookmark/Postman; POST tanpa CSRF supaya bisa dipanggil sebagai API murni.
Route::get('/api/recommendation', [SparqlController::class, 'getRecommendation'])->name('api.recommendation');
Route::post('/api/recommendation', [SparqlController::class, 'getRecommendation'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);


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

// === Orang 5: Eksplorasi Katalog ===
Route::get('/katalog', [\App\Http\Controllers\CatalogController::class, 'index'])->name('katalog');
Route::get('/bahan', [\App\Http\Controllers\IngredientController::class, 'index'])->name('bahan');
Route::get('/bahan/{id}', [\App\Http\Controllers\IngredientController::class, 'show'])->where('id', '[A-Za-z0-9_-]+')->name('bahan.detail');

// === Ontology Explorer (Stage 5)
Route::get('/ontologi', [OntologyController::class, 'index'])->name('ontology.dashboard');
Route::get('/ontologi/download', [OntologyController::class, 'download'])->name('ontology.download');
Route::post('/ontologi/sparql', [OntologyController::class, 'sparql'])->name('ontology.sparql');
// Class explorer
Route::get('/ontologi/kelas/{class}', [OntologyController::class, 'classExplorer'])->name('ontology.class');
// Individual detail (IRI will be URL‑encoded)
Route::get('/ontologi/individu/{iri}', [OntologyController::class, 'individualDetail'])->name('ontology.individual')->where('iri', '.*');
// Graph data endpoint
Route::get('/ontologi/graph', [OntologyController::class, 'graphData'])->name('ontology.graph');
