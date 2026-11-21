# Kontrak Orang 3 — Mesin Rekomendasi

Pemilik: Orang 3 (Recommendation Engine). Perubahan kontrak lewat PR yang disetujui Orang 3 dan Orang 1.

## 1. `App\Services\SparqlService` (dipakai semua orang)

| Method | Fungsi |
|---|---|
| `select(string $body, bool $useCache = true): array` | Jalankan `SELECT`, kembalikan `results.bindings` |
| `ask(string $body, bool $useCache = true): bool` | Jalankan `ASK` |
| `iri(string $local): string` | `OilySkinType` → `<https://example.org/skincare-ontology#OilySkinType>` (menolak karakter berbahaya) |
| `SparqlService::literal(string $v)` | Literal string yang sudah di-escape (untuk pencarian teks) |
| `SparqlService::local(string $iri)` | IRI → nama lokal |
| `SparqlService::value(array $row, string $var)` | Ambil nilai binding atau `null` |

- Prefix `ex: rdf: rdfs: owl: xsd: skos: schema:` ditempel otomatis — **jangan** tulis `PREFIX` di query.
- Hasil di-cache 10 menit (`SPARQL_CACHE_TTL` di `.env`, isi `0` untuk mematikan saat debugging).
- Kalau Fuseki mati / query salah → `App\Services\SparqlException` (turunan `RuntimeException`) dengan pesan ramah berbahasa Indonesia.
- `FusekiService` lama tetap ada supaya kode teman lain tidak rusak; untuk kode baru pakai `SparqlService`.

Contoh:

```php
$rows = app(SparqlService::class)->select('SELECT ?p WHERE { ?p ex:hasBrand '.$sparql->iri($brandId).' }');
```

## 2. Parameter kuis

| Parameter | Isi | Contoh |
|---|---|---|
| `age` | nama lokal `ex:AgeGroup` | `Teen`, `Adult` |
| `skin` | nama lokal `ex:SkinType` | `OilySkinType` |
| `category` | nama lokal `ex:Category` | `SerumCategory` |
| `ingredient` | nama lokal `ex:Ingredient` | `NiacinamideIngredient` |
| `concern[]` | nama lokal `ex:SkinConcern`, **maks 2** | `Acne`, `ExcessOil` |
| `price_min`, `price_max` | bilangan bulat ≥ 0 | `50000` |

Semua opsional. Nilai yang tidak ada di ontologi ditolak (whitelist diambil dari ontologi, bukan hardcode).
Alias lama tetap diterima: `age_group`, `skin_type`, `concerns`.

## 3. Endpoint

| Route | Keterangan |
|---|---|
| `GET /rekomendasi?...` | Halaman hasil (Blade). 422 kalau input tidak valid, 503 kalau Fuseki mati |
| `GET /api/recommendation?...` | JSON |
| `POST /api/recommendation` | JSON, body sama dengan parameter di atas (tanpa CSRF) |

## 4. Format JSON

```json
{
  "status": "success",
  "plan": "B",                         // "A" semua kriteria terpenuhi, "B" dilonggarkan, "none" tidak ada produk lolos filter keras
  "criteria": { "age": "Teen", "skin": "DrySkinType", "concern": ["Acne"] },
  "max_score": 5,
  "relaxed": ["jenis kulit"],          // label kriteria yang dilonggarkan (hanya Plan B)
  "relaxed_keys": ["skin"],            // skin | category | ingredient | price | concern:<Id>
  "teen_lock": { "active": true, "hidden_count": 1, "message": "1 produk ber-retinol disembunyikan untuk usia remaja." },
  "notices": ["Retinol tidak disarankan untuk usia remaja, jadi kriteria bahan diabaikan."],
  "total": 12,
  "products": [{
    "id": "EMI-0029", "uri": "https://example.org/skincare-ontology#EMI-0029",
    "name": "Bright Stuff for Acne Prone Skin Face Wash", "price": 25000,
    "score": 3, "max_score": 5, "match_percent": 60,
    "reasons": ["Mengatasi Jerawat"],
    "missed": ["jenis kulit"], "missed_keys": ["skin"],
    "brand": "Emina", "brand_id": "EminaBrand", "series": "Bright Stuff Acne Prone", "series_id": "...",
    "category": "Cleanser", "image": null, "verification": "...",
    "skin_types": ["Berminyak"], "concerns": ["Jerawat"], "ingredients": ["Salicylic Acid"]
  }]
}
```

Error: `422 { "status": "error", "message": "...", "errors": { "concern": ["Keluhan maksimal 2."] } }`, `503 { "status": "error", "message": "Server Fuseki tidak dapat dihubungi..." }`.

## 5. Aturan skor

| Kriteria (kalau diisi) | Poin | Cara cocok di SPARQL |
|---|---|---|
| Keluhan (per keluhan) | 3 | `ex:treatsConcern`, atau bahan yang `ex:targetsConcern` keluhan itu |
| Jenis kulit | 2 | `ex:suitableForSkinType` pilihan user **atau** `ex:AllSkinType` |
| Kategori | 2 | `ex:hasCategory/skos:broader*` (sub-kategori ikut) |
| Bahan | 2 | `ex:hasIngredient` individu itu, atau bahan bertipe golongan itu |
| Harga | 1 | `ex:estimatedPrice` dalam rentang |

- **Filter keras (tidak pernah dilonggarkan):** kelompok umur dan Teen Lock.
- **Plan A** = semua produk dengan skor = skor maksimal. **Plan B** = 12 produk skor tertinggi.
- Bobot, batas Plan B, dan daftar cadangan Teen Lock ada di `config/recommendation.php`.

## 6. Teen Lock

Berlaku di Plan A **dan** Plan B (satu query yang sama). Produk dibuang kalau punya bahan yang:
1. bertipe `ex:Retinoid` (atau subclass-nya) — ontologi v2 (B2),
2. atau bahan/golongannya `ex:contraindicatedFor ex:Teen` — ontologi v2 (B3),
3. atau ada di daftar cadangan `teen_lock.fallback_ingredients` (sementara, selama ontologi belum punya kelas `ex:Retinoid`).

Fungsi `RecommendationEngine::retinoidPattern('?var')` bisa dipakai Orang 4 untuk Complete Your Segment supaya Teen Lock sama persis.

## 7. Titik sambung ke orang lain

- **Orang 1:** begitu `ex:Retinoid`, `ex:contraindicatedFor`, `ex:targetsConcern`, `skos:broader` ada di TTL, engine otomatis memakainya tanpa ubah kode. Daftar cadangan di config bisa dihapus setelah itu.
- **Orang 2:** kuis cukup redirect ke `/rekomendasi?...` (sudah). Opsi kuis = nama lokal di atas.
- **Orang 4:** tombol "Lengkapi rutinitas" membuka `/produk/{id}?age=...#lengkapi-rutinitas` dan menyimpan ID ke `localStorage['aphrodite-routine']` (array ID). Tambahkan `id="lengkapi-rutinitas"` di bagian Complete Your Segment.
