<?php

namespace App\Http\Controllers;

use App\Services\FusekiService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Orang 5 — Eksplorasi Katalog.
 *
 * Tahap 1: fondasi halaman katalog. Daftar produk, filter, sorting, dan
 * pagination baru disambungkan ke ontologi pada tahap berikutnya.
 */
class CatalogController extends Controller
{
    public function __construct(private FusekiService $fuseki) {}

    public function index(Request $request)
    {
        return view('user.katalog', [
            'productCount' => $this->productCount(),
            'search' => trim((string) $request->query('q', '')),
        ]);
    }

    /**
     * Jumlah produk asli di ontologi untuk hero. Null jika Fuseki tidak dapat
     * diakses, agar halaman tetap terbuka tanpa mengarang angka.
     */
    private function productCount(): ?int
    {
        try {
            $rows = $this->fuseki->query('SELECT (COUNT(DISTINCT ?product) AS ?total) WHERE { ?product a ex:Product ; ex:productName ?name }');
        } catch (RuntimeException) {
            return null;
        }

        return isset($rows[0]['total']['value']) ? (int) $rows[0]['total']['value'] : null;
    }
}
