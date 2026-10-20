<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SparqlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// ==========================================
// AREA PUBLIK (Tampilan User - Tanpa Login)
// ==========================================
Route::get('/', function () {
    return view('user.landing'); 
});

Route::get('/kuis', function () {
    return view('user.kuis'); 
});

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