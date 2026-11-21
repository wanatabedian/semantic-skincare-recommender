<?php

namespace App\Http\Controllers;

use App\Services\Recommendation\Criteria;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\SparqlException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API JSON mesin rekomendasi (Orang 3).
 *
 *   GET  /api/recommendation?age=Teen&skin=OilySkinType&concern[]=Acne
 *   POST /api/recommendation   (body JSON / form dengan parameter yang sama)
 *
 * Format respons: lihat docs/KONTRAK-orang3.md.
 */
class SparqlController extends Controller
{
    public function __construct(private RecommendationEngine $engine) {}

    public function getRecommendation(Request $request): JsonResponse
    {
        try {
            $criteria = Criteria::fromInput($request->all(), $this->engine->vocabulary());

            return response()->json(['status' => 'success'] + $this->engine->recommend($criteria));
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Input kuis tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (SparqlException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }
    }
}
