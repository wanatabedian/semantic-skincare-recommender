use App\Http\Controllers\SparqlController;

Route::post('/recommendation', [SparqlController::class, 'getRecommendation']);