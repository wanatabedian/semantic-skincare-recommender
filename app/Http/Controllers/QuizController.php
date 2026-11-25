<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuizSubmissionRequest;
use App\Services\FusekiService;
use App\Services\Recommendation\RecommendationEngine;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QuizController extends Controller
{
    public function __construct(
        private FusekiService $fuseki,
        private RecommendationEngine $engine,
    ) {}

    public function index() { return view('user.kuis-v2'); }

    /**
     * Pilihan kuis + matriks ketersediaan produk.
     * 'availability' dipakai browser untuk menonaktifkan opsi yang tidak punya
     * kombinasi produk di ontologi (termasuk aturan Teen Lock) sebelum user menekan Analisis.
     */
    public function options(): JsonResponse
    {
        try {
            return response()->json($this->fuseki->options() + [
                'availability' => $this->engine->availability(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    public function submit(QuizSubmissionRequest $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'query' => $request->validated()]);
    }
}
