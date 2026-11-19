# Laporan Audit Data Ontologi Aphrodite (A1–A10)

> Dibuat otomatis oleh `scripts/audit_data.py`. Jangan diedit manual, jalankan ulang skripnya.

- **File sumber:** `ontology/aphrodite-v1.5.ttl`
- **MD5 file sumber:** `8deb7b8f5d63022de315bea074a7ca1f` (tidak berubah sebelum/sesudah audit)
- **Waktu audit:** 2026-11-14 10:24:12
- **Jumlah triple:** 1513  ·  **Jumlah produk:** 88
- **Daftar resmi jenis kulit:** Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif

## Ringkasan

| Kode | Anomali | Jumlah temuan | Produk/resource terdampak |
|---|---|---:|---:|
| A1 | Kolom tergeser: jenis kulit berisi nama bahan | 0 | 0 |
| A2 | Jenis kulit belum diisi | 4 | 4 |
| A3 | sourceProductId dobel / URI tidak konsisten | 0 | 0 |
| A4 | Bahan pecah karena split koma dan sisa Excel | 1 | 1 |
| A5 | Klaim/catatan tercampur dengan bahan | 0 | 0 |
| A6 | Produk tanpa kelompok umur | 2 | 2 |
| A7 | Data melanggar Teen Lock | 1 | 1 |
| A8 | Bahan tampak tersalin dari produk lain (heuristik) | 1 | 1 |
| A9 | Kategori dobel dan tidak konsisten | 16 | 16 |
| A10 | Typo dan label kosong | 1 | 1 |
| | **Total** | **26** | |

Catatan: satu produk bisa muncul di beberapa kategori, dan satu nilai bisa memicu lebih dari satu sub-jenis temuan.

## A1 — Kolom tergeser: jenis kulit berisi nama bahan

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Hanya bisa mendeteksi nilai di luar daftar resmi. Nilai jenis kulit yang BENAR tidak bisa ditentukan otomatis; harus dicek di situs/label resmi.

## A2 — Jenis kulit belum diisi

**Jumlah temuan:** 4

- Jenis kulit masih placeholder: 4

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Jenis kulit masih placeholder | ex:CRY-0001 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |
| 2 | Jenis kulit masih placeholder | ex:CRY-0002 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |
| 3 | Jenis kulit masih placeholder | ex:CRY-0003 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |
| 4 | Jenis kulit masih placeholder | ex:CRY-0004 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |

**Keterbatasan pemeriksaan:** Placeholder dikenali dari kata kunci ('verifikasi', 'konfirmasi', 'not specified'). Placeholder dengan kata lain bisa terlewat.

## A3 — sourceProductId dobel / URI tidak konsisten

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Deteksi URI terpotong membandingkan akhiran URI dengan nama produk; jika nama produk sendiri berbeda dari URI, potongan tidak terdeteksi.

## A4 — Bahan pecah karena split koma dan sisa Excel

**Jumlah temuan:** 1

- Kemungkinan huruf awal hilang: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Kemungkinan huruf awal hilang | ex:LAB-0053 | accharide Isomerate | Kata 'accharide' kemungkinan terpotong dari 'saccharide'. |

**Keterbatasan pemeriksaan:** Berbasis pola (panjang < 3, '+G', kurung tidak seimbang, kata penghubung, kemiripan nama). Typo yang tidak punya 'pasangan' benar di dataset tidak bisa dideteksi.

## A5 — Klaim/catatan tercampur dengan bahan

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Berbasis daftar kata kunci; klaim dengan kata lain tidak terdeteksi.

## A6 — Produk tanpa kelompok umur

**Jumlah temuan:** 2

- Tidak punya kelompok umur: 2

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Tidak punya kelompok umur | ex:KAH-0047 | "Bright Fresh Sunscreen Serum SPF 35 PA++++" | Tidak punya suitableForAgeGroup; produk hilang setiap kali user memilih umur. |
| 2 | Tidak punya kelompok umur | ex:LAB-0060 | "Barrier Restorative Advanced Lotion" | Tidak punya suitableForAgeGroup; produk hilang setiap kali user memilih umur. |

**Keterbatasan pemeriksaan:** Dapat diperiksa penuh secara otomatis.

## A7 — Data melanggar Teen Lock

**Jumlah temuan:** 1

- Retinoid ditandai Teen: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Retinoid ditandai Teen | ex:LAB-0069 | "Micro Encapsulation Retinol"; umur: Adult, Teen | Bahan mengandung 'retinol' (golongan retinoid) tetapi produk ditandai ex:Teen; melanggar Teen Lock. |

**Keterbatasan pemeriksaan:** Retinoid dikenali dari kata kunci nama (retinol, retinal, retinyl, dsb.) karena bahan masih berupa teks. Setelah ontologi v2 (kelas ex:Retinoid) pemeriksaan ini bisa berbasis kelas.

## A8 — Bahan tampak tersalin dari produk lain (heuristik)

**Jumlah temuan:** 1

- Bahan menyebut teknologi seri lain: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Bahan menyebut teknologi seri lain | ex:LAB-0062 | "acnepro biome™ technology"; seri produk: BighteningSeries | Nama bahan memuat 'AcneProSeries' padahal produk bukan bagian seri itu; kemungkinan tersalin dari produk lain. Wajib dicek manual di situs resmi. |

**Keterbatasan pemeriksaan:** TIDAK bisa dipastikan otomatis. Skrip hanya menandai bahan yang menyebut nama seri/teknologi lain. Kebenaran komposisi harus dicek manual di situs resmi brand.

## A9 — Kategori dobel dan tidak konsisten

**Jumlah temuan:** 16

- Kandidat duplikat/sinonim (awalan nama sama): 5
- Kandidat sub-kategori (nama satu memuat nama lain): 11

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Kandidat sub-kategori (nama satu memuat nama lain) | CleanserCategory ↔ MicellarCleanserCategory | CleanserCategory (19 produk), MicellarCleanserCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 2 | Kandidat sub-kategori (nama satu memuat nama lain) | DaymoisturizerSPFCategory ↔ MoisturizerCategory | DaymoisturizerSPFCategory (1 produk), MoisturizerCategory (9 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 3 | Kandidat sub-kategori (nama satu memuat nama lain) | EssenceCategory ↔ EssencetonerCategory | EssenceCategory (1 produk), EssencetonerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 4 | Kandidat sub-kategori (nama satu memuat nama lain) | EssencetonerCategory ↔ TonerCategory | EssencetonerCategory (1 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 5 | Kandidat sub-kategori (nama satu memuat nama lain) | ExfoliatingTonerCategory ↔ TonerCategory | ExfoliatingTonerCategory (3 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 6 | Kandidat duplikat/sinonim (awalan nama sama) | EyeCareCategory ↔ EyecreamCategory | EyeCareCategory (1 produk), EyecreamCategory (2 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 7 | Kandidat duplikat/sinonim (awalan nama sama) | FaceMistCategory ↔ FacespraytonerCategory | FaceMistCategory (3 produk), FacespraytonerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 8 | Kandidat sub-kategori (nama satu memuat nama lain) | FacespraytonerCategory ↔ TonerCategory | FacespraytonerCategory (1 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 9 | Kandidat duplikat/sinonim (awalan nama sama) | MicellarCleanserCategory ↔ MicellarwaterCategory | MicellarCleanserCategory (1 produk), MicellarwaterCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 10 | Kandidat sub-kategori (nama satu memuat nama lain) | MoisturizerCategory ↔ NightmoisturizerCategory | MoisturizerCategory (9 produk), NightmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 11 | Kandidat sub-kategori (nama satu memuat nama lain) | MoisturizerCategory ↔ SunscreenmoisturizerCategory | MoisturizerCategory (9 produk), SunscreenmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 12 | Kandidat duplikat/sinonim (awalan nama sama) | NightCreamCategory ↔ NightmoisturizerCategory | NightCreamCategory (1 produk), NightmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 13 | Kandidat sub-kategori (nama satu memuat nama lain) | OilSerumCategory ↔ SerumCategory | OilSerumCategory (1 produk), SerumCategory (15 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 14 | Kandidat sub-kategori (nama satu memuat nama lain) | SunscreenCategory ↔ SunscreenmoisturizerCategory | SunscreenCategory (18 produk), SunscreenmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 15 | Kandidat sub-kategori (nama satu memuat nama lain) | SunscreenCategory ↔ SunscreentintedCategory | SunscreenCategory (18 produk), SunscreentintedCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 16 | Kandidat duplikat/sinonim (awalan nama sama) | SunscreenmoisturizerCategory ↔ SunscreentintedCategory | SunscreenmoisturizerCategory (1 produk), SunscreentintedCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |

**Keterbatasan pemeriksaan:** Duplikat beda kapital pasti terdeteksi. Sinonim dengan nama berbeda total (mis. 'MicellarCleanser' vs 'Micellarwater') hanya muncul sebagai KANDIDAT; keputusan menyatukan/hierarki tetap manual.

## A10 — Typo dan label kosong

**Jumlah temuan:** 1

- Kemungkinan typo pada nama seri: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Kemungkinan typo pada nama seri | ex:BighteningSeries | 'Bightening' vs 'brightening' (nama produk: "Multi-Pigmentation Brightening Serum") | Kata pada URI seri berbeda tipis dengan kata di nama produk anggotanya. |

**Keterbatasan pemeriksaan:** Label kosong dapat diperiksa penuh. Typo hanya terdeteksi untuk nama seri yang katanya mirip dengan kata di nama produk anggotanya.

## Informasi tambahan: varian penulisan bahan (bukan bagian A1–A10)

Nama bahan yang sama ditulis berbeda (kapital, kadar %, tunggal/jamak). Ini bukan kesalahan data, tetapi bahan untuk `skos:altLabel` saat migrasi ke ontologi v2 (temuan B1). Tidak dihitung di ringkasan.

| # | Varian yang ditemukan | Jumlah produk |
|---:|---|---:|
| 1 | AcnePro Biome™ Technology · acnepro biome™ technology | 2 |
| 2 | Alpha-arbutin · alpha-arbutin | 2 |
| 3 | Ceramide · Ceramides | 2 |
| 4 | CICA · Cica | 4 |
| 5 | Glycerin · glycerin | 3 |
| 6 | Gold Peptide Crystals · Gold-Peptide Crystals · gold peptide crystals | 7 |
| 7 | Hyaluronic Acid · hyaluronic acid | 6 |
| 8 | 10% niacinamide · Niacinamide · Niacinamide 10% · Niacinamide 5% · niacinamide · niacinamide 5% | 15 |
| 9 | Salicylic Acid (BHA) · salicylic acid (bha) | 2 |
| 10 | Sodium Hyaluronate · sodium hyaluronate | 2 |
| 11 | Summer plum extract · summer plum extract | 5 |
| 12 | YouthGlow Active · youthglow active | 5 |
| 13 | Zinc · zinc | 2 |

## Catatan umum

- Audit ini hanya **mendeteksi**; tidak ada data yang diperbaiki atau dihapus.
- Nilai pengganti (jenis kulit yang benar, komposisi yang benar, ID baru) **tidak** dibuat oleh skrip ini.
- Temuan bertanda *kandidat* / *kemungkinan* perlu dicek manusia sebelum dijadikan perbaikan di data v1.5.
