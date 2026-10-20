<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SparqlController;
use Illuminate\Http\Request;

// 1. Halaman Utama
Route::get('/', function () {
    return view('welcome');
});

// 2. Endpoint Kuis (Bisa diakses oleh Front-End/AJAX)
Route::post('/api/recommendation', [SparqlController::class, 'getRecommendation']);

// 3. Rute Tes Kuis Langsung di Browser
Route::get('/tes-kuis', function () {
    $controller = new SparqlController();
    $request = Request::create('/api/recommendation', 'POST', [
        'category'   => 'Moisturizer',
        'skin_type'  => 'Kering',
        'concerns'   => ['GarisHalus'],
        'age_group'  => 'Adult',
        'ingredient' => 'Retinol'
    ]);
    return $controller->getRecommendation($request);
});