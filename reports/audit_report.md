# Laporan Audit Data Ontologi Aphrodite (A1–A10)

> Dibuat otomatis oleh `scripts/audit_data.py`. Jangan diedit manual, jalankan ulang skripnya.

- **File sumber:** `ontology/skincare_ontology_clean.ttl`
- **MD5 file sumber:** `1eca73b9131624457ae97b85c5d8ae50` (tidak berubah sebelum/sesudah audit)
- **Waktu audit:** 2026-11-22 06:25:45
- **Jumlah triple:** 1517  ·  **Jumlah produk:** 88
- **Daftar resmi jenis kulit:** ex:Dry (Kering), ex:Normal (Normal), ex:Oily (Berminyak)

## Ringkasan

| Kode | Anomali | Jumlah temuan | Produk/resource terdampak |
|---|---|---:|---:|
| A1 | Kolom tergeser: jenis kulit berisi nama bahan | 0 | 0 |
| A2 | Jenis kulit belum diisi | 0 | 0 |
| A3 | sourceProductId dobel / URI tidak konsisten | 0 | 0 |
| A4 | Bahan pecah karena split koma dan sisa Excel | 0 | 0 |
| A5 | Klaim/catatan tercampur dengan bahan | 0 | 0 |
| A6 | Produk tanpa kelompok umur | 0 | 0 |
| A7 | Data melanggar Teen Lock | 0 | 0 |
| A8 | Bahan tampak tersalin dari produk lain (heuristik) | 0 | 0 |
| A9 | Kategori dobel dan tidak konsisten | 0 | 0 |
| A10 | Typo dan label kosong | 9 | 9 |
| | **Total** | **9** | |

Catatan: satu produk bisa muncul di beberapa kategori, dan satu nilai bisa memicu lebih dari satu sub-jenis temuan.

## A1 — Kolom tergeser: jenis kulit berisi nama bahan

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Hanya bisa mendeteksi nilai di luar daftar resmi. Nilai jenis kulit yang BENAR tidak bisa ditentukan otomatis; harus dicek di situs/label resmi.

## A2 — Jenis kulit belum diisi

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Placeholder dikenali dari kata kunci ('verifikasi', 'konfirmasi', 'not specified'). Placeholder dengan kata lain bisa terlewat.

## A3 — sourceProductId dobel / URI tidak konsisten

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Deteksi URI terpotong membandingkan akhiran URI dengan nama produk; jika nama produk sendiri berbeda dari URI, potongan tidak terdeteksi.

## A4 — Bahan pecah karena split koma dan sisa Excel

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Berbasis pola (panjang < 3, '+G', kurung tidak seimbang, kata penghubung, kemiripan nama). Typo yang tidak punya 'pasangan' benar di dataset tidak bisa dideteksi.

## A5 — Klaim/catatan tercampur dengan bahan

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Berbasis daftar kata kunci; klaim dengan kata lain tidak terdeteksi.

## A6 — Produk tanpa kelompok umur

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Dapat diperiksa penuh secara otomatis.

## A7 — Data melanggar Teen Lock

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Retinoid dikenali dari kata kunci nama (retinol, retinal, retinyl, dsb.) karena bahan masih berupa teks. Setelah ontologi v2 (kelas ex:Retinoid) pemeriksaan ini bisa berbasis kelas.

## A8 — Bahan tampak tersalin dari produk lain (heuristik)

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** TIDAK bisa dipastikan otomatis. Skrip hanya menandai bahan yang menyebut nama seri/teknologi lain. Kebenaran komposisi harus dicek manual di situs resmi brand.

## A9 — Kategori dobel dan tidak konsisten

**Jumlah temuan:** 0

_Tidak ada temuan._

**Keterbatasan pemeriksaan:** Duplikat beda kapital pasti terdeteksi. Sinonim dengan nama berbeda total (mis. 'MicellarCleanser' vs 'Micellarwater') hanya muncul sebagai KANDIDAT; keputusan menyatukan/hierarki tetap manual.

## A10 — Typo dan label kosong

**Jumlah temuan:** 9

- ProductSeries tanpa rdfs:label: 9

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | ProductSeries tanpa rdfs:label | ex:Altero6MWHyaluSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 2 | ProductSeries tanpa rdfs:label | ex:BrightBlemishSolutionSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 3 | ProductSeries tanpa rdfs:label | ex:BrightStuffAcneProneSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 4 | ProductSeries tanpa rdfs:label | ex:BrighteningSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 5 | ProductSeries tanpa rdfs:label | ex:DermalabRevolutionaireSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 6 | ProductSeries tanpa rdfs:label | ex:FaceCareSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 7 | ProductSeries tanpa rdfs:label | ex:FirstCleanserSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 8 | ProductSeries tanpa rdfs:label | ex:SerumActiveCareSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 9 | ProductSeries tanpa rdfs:label | ex:SlowAgingSignsSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |

**Keterbatasan pemeriksaan:** Label kosong dapat diperiksa penuh. Typo hanya terdeteksi untuk nama seri yang katanya mirip dengan kata di nama produk anggotanya.

## Informasi tambahan: varian penulisan bahan (bukan bagian A1–A10)

Nama bahan yang sama ditulis berbeda (kapital, kadar %, tunggal/jamak). Ini bukan kesalahan data, tetapi bahan untuk `skos:altLabel` saat migrasi ke ontologi v2 (temuan B1). Tidak dihitung di ringkasan.

_Tidak ada._

## Catatan umum

- Audit ini hanya **mendeteksi**; tidak ada data yang diperbaiki atau dihapus.
- Nilai pengganti (jenis kulit yang benar, komposisi yang benar, ID baru) **tidak** dibuat oleh skrip ini.
- Temuan bertanda *kandidat* / *kemungkinan* perlu dicek manusia sebelum dijadikan perbaikan di data v1.5.
