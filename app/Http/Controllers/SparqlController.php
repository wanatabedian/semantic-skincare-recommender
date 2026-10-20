<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;

class SparqlController extends Controller
{
    private $fusekiEndpoint = 'http://localhost:3030/skincare/query';

    public function getRecommendation(Request $request)
    {
        // 1. Validasi Input Kuis
        $validated = $request->validate([
            'category'   => 'required|string',
            'skin_type'  => 'required|string',
            'concerns'   => 'required|array|min:1|max:2',
            'age_group'  => 'required|in:Teen,Adult',
            'ingredient' => 'nullable|string'
        ]);

        // 2. Sanitasi Input & Normalisasi Kapital (Cegah SPARQL Injection & Bug Huruf Kapital)
        $category   = ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['category']));
        $skinType   = ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['skin_type']));
        $ageGroup   = ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['age_group']));
        $ingredient = $validated['ingredient'] ? ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $validated['ingredient'])) : null;

        $concerns = array_map(function ($c) {
            return ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $c));
        }, $validated['concerns']);

        // 3. Eksekusi Plan A (Pencarian Spesifik)
        $queryPlanA = $this->buildSparqlQuery($category, $skinType, $concerns, $ageGroup, $ingredient, true);
        $responseA  = $this->executeQuery($queryPlanA);

        // Jika Server Fuseki Mati
        if (isset($responseA['error'])) {
            return response()->json($responseA, 503);
        }

        $results    = $responseA['data'];
        $isFallback = false;

        // 4. Eksekusi Plan B (Fallback jika Plan A Kosong)
        if (empty($results)) {
            $queryPlanB = $this->buildSparqlQuery($category, $skinType, [$concerns[0]], $ageGroup, null, false);
            $responseB  = $this->executeQuery($queryPlanB);

            if (isset($responseB['error'])) {
                return response()->json($responseB, 503);
            }

            $results    = $responseB['data'];
            $isFallback = true;
        }

        return response()->json([
            'status'      => 'success',
            'is_fallback' => $isFallback,
            'total_data'  => count($results),
            'products'    => $this->formatResults($results)
        ]);
    }

    /**
     * Builder Query SPARQL Dinamis
     */
    private function buildSparqlQuery($category, $skinType, array $concerns, $ageGroup, $ingredient = null, $strictIngredient = true)
    {
        $prefix = "
            PREFIX ex: <http://example.org/beauty#>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        ";

        // Logika OR untuk Keluhan Ganda menggunakan klausa VALUES
        $concernValues = implode(" ", array_map(fn($c) => "'{$c}'", $concerns));

        $whereClause = "
            ?product a ex:Product ;
                     ex:productName ?productName ;
                     ex:hasCategory ex:{$category} ;
                     ex:suitableForSkinType '{$skinType}' ;
                     ex:suitableForAgeGroup ex:{$ageGroup} ;
                     ex:treatsConcern ?concern .

            VALUES ?concern { {$concernValues} }
        ";

        if ($ingredient && $strictIngredient) {
            $whereClause .= " ?product ex:hasIngredient '{$ingredient}' . ";
        }

        // Teen Lock: Proteksi Otomatis Produk Retinol untuk Remaja
        if ($ageGroup === 'Teen') {
            $whereClause .= " FILTER NOT EXISTS { ?product ex:hasIngredient 'Retinol' } ";
        }

        return "
            {$prefix}
            SELECT DISTINCT ?product ?productName ?series
            WHERE {
                {$whereClause}
                OPTIONAL { ?product ex:belongsToSeries ?series . }
            }
        ";
    }

    /**
     * Eksekutor Request Guzzle dengan Proteksi Crash saat Fuseki Offline
     */
    private function executeQuery($sparqlQuery)
    {
        $client = new Client(['timeout' => 5.0]);

        try {
            $response = $client->post($this->fusekiEndpoint, [
                'form_params' => ['query' => $sparqlQuery],
                'headers'     => ['Accept' => 'application/sparql-results+json']
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['data' => $data['results']['bindings'] ?? []];

        } catch (ConnectException $e) {
            return [
                'error'   => true,
                'message' => 'Server Apache Jena Fuseki belum dinyalakan di localhost:3030.'
            ];
        } catch (GuzzleException $e) {
            return [
                'error'   => true,
                'message' => 'Terjadi kesalahan sintaks SPARQL: ' . $e->getMessage()
            ];
        }
    }

    private function formatResults(array $bindings)
    {
        return array_map(function ($item) {
            return [
                'name'   => $item['productName']['value'] ?? '',
                'series' => isset($item['series']['value']) ? basename($item['series']['value']) : null,
                'id'     => isset($item['product']['value']) ? basename($item['product']['value']) : ''
            ];
        }, $bindings);
    }
}