# Katalog `/katalog` — search, filter, dan dynamic facet counts

Pemilik: Orang 5 (Eksplorasi Katalog). Implementasi: `app/Http/Controllers/CatalogController.php`.
Semua query dikirim lewat `App\Services\SparqlService` (PREFIX ditempel otomatis, hasil di-cache).

| File | Isi |
|---|---|
| `katalog-opsi-filter.rq` | Opsi filter + label (sekaligus whitelist) dan total produk |
| `katalog-search-filter.rq` | Daftar produk dengan search + filter |
| `katalog-facet-counts.rq` | Facet per kelompok (relasi & harga) |
| `katalog-daftar-produk.rq` | Query daftar produk tahap 2 (tanpa filter), dibiarkan sebagai arsip |

## Kelompok filter dan property ontologi

Diverifikasi terhadap dataset `aphrodite` di Fuseki (88 `ex:Product`).

| Parameter URL | Property | Kardinalitas | Logika |
|---|---|---|---|
| `q` | `ex:productName` (literal) | 1 | `CONTAINS(LCASE(STR(?nm)), LCASE("…"))` |
| `kategori[]` | `ex:hasCategory` → `ex:Category` | 1 | OR dalam kelompok |
| `kulit[]` | `ex:suitableForSkinType` → `ex:SkinType` | 1–3 | OR dalam kelompok |
| `concern[]` | `ex:treatsConcern` → `ex:SkinConcern` | 1–4 | OR dalam kelompok |
| `brand[]` | `ex:hasBrand` → `ex:Brand` | 1 | OR dalam kelompok |
| `harga[]` | `ex:estimatedPrice` (`xsd:integer`) | 1 | OR antar rentang |

Antar kelompok berlaku **AND**; search selalu ikut. Contoh URL:

```
/katalog?q=serum&kulit[]=Oily&brand[]=EminaBrand&brand[]=WardahBrand&harga[]=50-100
```

Urutan parameter di URL selalu sama (q, kategori, kulit, concern, brand, harga) dan nilai dalam
satu kelompok diurutkan mengikuti daftar opsi, sehingga satu kombinasi filter = satu URL.

## Rentang harga

Setengah terbuka `[min, max)`, jadi tidak tumpang tindih. Harga tepat 50.000 / 100.000 / 150.000
hanya masuk satu rentang.

| Kunci | Kondisi | Label |
|---|---|---|
| `lt50` | `< 50000` | Di bawah Rp50.000 |
| `50-100` | `>= 50000 && < 100000` | Rp50.000 – Rp99.999 |
| `100-150` | `>= 100000 && < 150000` | Rp100.000 – Rp149.999 |
| `gte150` | `>= 150000` | Rp150.000 ke atas |

`isNumeric()` dipakai di filter maupun facet: produk tanpa harga (atau harga bukan angka)
**tidak** dianggap 0. Produk seperti itu tidak lolos filter harga mana pun dan tidak dihitung di
facet harga, tetapi tetap tampil bila filter harga tidak dipakai.

## Logika facet count

Facet kelompok **X** = jumlah produk per opsi X yang cocok dengan **search + semua filter kelompok
lain**, **tanpa** filter X sendiri (`filterBlock($state, except: 'X')`).

Contoh: saat brand Wardah & Emina dipilih bersama jenis kulit Berminyak,

- facet **brand** dihitung dengan filter jenis kulit + harga + search (brand dilepas), sehingga
  angka di samping "Kahf" berarti "jumlah produk bila Kahf ikut dicentang";
- facet **jenis kulit** dihitung dengan filter brand + harga + search (jenis kulit dilepas);
- facet **harga** dihitung dengan semua filter kecuali harga.

Opsi yang tidak muncul di hasil facet ditampilkan dengan angka 0 dan checkbox-nya dinonaktifkan,
kecuali opsi itu sedang dipilih (supaya tetap bisa dilepas).

Jumlah query per halaman: total, opsi, produk, dan 5 facet = 8 SELECT (semuanya di-cache oleh
`SparqlService`, default 10 menit).

## Cara menghindari duplikasi

1. **Filter memakai `FILTER EXISTS`, bukan join.** `FILTER EXISTS { ?p ex:treatsConcern ?f . FILTER(?f IN (…)) }`
   hanya menguji keberadaan. Produk dengan dua keluhan yang sama-sama dipilih tetap satu baris.
2. **Daftar produk memakai `GROUP BY ?p`** dengan `SAMPLE` / `GROUP_CONCAT(DISTINCT …)` untuk
   atribut bernilai banyak, jadi satu baris = satu produk.
3. **Facet memakai `COUNT(DISTINCT ?p)` per `?opt`**, jadi satu produk dihitung sekali per opsi.
4. **Opsi filter memakai `GROUP BY ?group ?opt` + `SAMPLE(label)`**, sehingga opsi dengan beberapa
   label (mis. `@id` dan `@en`) tidak muncul dua kali.
5. **Facet harga memetakan setiap harga ke tepat satu kunci** dengan `BIND(IF(…))` yang kondisinya
   tidak tumpang tindih.

## Keamanan (anti SPARQL injection)

- Nilai `kategori/kulit/concern/brand` hanya diterima bila nama lokalnya ada di hasil
  `katalog-opsi-filter.rq` (whitelist dari ontologi). IRI disusun dengan `SparqlService::iri()`,
  yang juga menolak nama lokal berkarakter tidak aman.
- `harga` hanya menerima kunci di `CatalogController::PRICE_RANGES`; angka batas berasal dari
  konstanta, tidak pernah dari input.
- `q`: hanya string; karakter kontrol (`\p{C}`) diganti spasi, spasi dirapikan, maksimal 100
  karakter, UTF-8 tidak valid → kosong; lalu di-escape dengan `SparqlService::literal()`.
- Nilai yang tidak valid dibuang diam-diam (tidak membuat error dan tidak muncul sebagai chip).

## Catatan dataset

Dataset yang saat ini dimuat di Fuseki berisi jenis kulit `Dry`, `Normal`, `Oily` dan 8 kategori.
File `ontology/aphrodite-v2.ttl` juga mempunyai `ex:AllSkinType` dan subkategori (mis.
`MicellarCleanserCategory`). Karena opsi dibangun dari data, keduanya akan otomatis muncul sebagai
opsi tersendiri bila v2 dimuat. Katalog **tidak** melakukan inferensi tambahan (mis. menganggap
`AllSkinType` cocok untuk filter "Berminyak"); filter mengikuti relasi yang tertulis di ontologi.
