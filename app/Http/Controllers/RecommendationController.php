<?php

namespace App\Http\Controllers;

use App\Services\Recommendation\Criteria;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\SparqlException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Halaman Hasil Rekomendasi: GET /rekomendasi?age=Teen&skin=OilySkinType&concern[]=Acne ...
 * Parameter ada di URL supaya hasil bisa di-bookmark dan dibagikan.
 */
class RecommendationController extends Controller
{
    public function __construct(private RecommendationEngine $engine) {}

    public function index(Request $request)
    {
        $base = ['result' => null, 'validationErrors' => [], 'error' => null, 'labels' => []];

        try {
            $vocabulary = $this->engine->vocabulary();
            $criteria = Criteria::fromInput($request->query(), $vocabulary);
            $result = $this->engine->recommend($criteria);

            return view('user.rekomendasi', [
                'result' => $result,
                'labels' => $this->criteriaLabels($criteria, $vocabulary),
            ] + $base);
        } catch (ValidationException $e) {
            return response()->view('user.rekomendasi', [
                'validationErrors' => collect($e->errors())->flatten()->all(),
            ] + $base, 422);
        } catch (SparqlException $e) {
            return response()->view('user.rekomendasi', ['error' => $e->getMessage()] + $base, 503);
        }
    }

    /** Ringkasan kriteria berlabel (untuk chip "Kriteria kamu"). */
    private function criteriaLabels(Criteria $c, $vocabulary): array
    {
        $labels = [];
        if ($c->age) {
            $labels[] = 'Usia: '.$vocabulary->label('age', $c->age);
        }
        if ($c->skin) {
            $labels[] = 'Kulit: '.$vocabulary->label('skin', $c->skin);
        }
        foreach ($c->concerns as $concern) {
            $labels[] = 'Keluhan: '.$vocabulary->label('concern', $concern);
        }
        if ($c->category) {
            $labels[] = 'Kategori: '.$vocabulary->label('category', $c->category);
        }
        if ($c->ingredient) {
            $labels[] = 'Bahan: '.$vocabulary->label('ingredient', $c->ingredient);
        }
        if ($c->hasPrice()) {
            $labels[] = 'Harga: '.RecommendationEngine::priceRange($c);
        }

        return $labels;
    }
}
