# Laporan Audit Data Ontologi Aphrodite (A1–A10)

> Dibuat otomatis oleh `scripts/audit_data.py`. Jangan diedit manual, jalankan ulang skripnya.

- **File sumber:** `ontology/skincare_ontology.ttl`
- **MD5 file sumber:** `2b0ea844d72aaa69be133700b747502f` (tidak berubah sebelum/sesudah audit)
- **Waktu audit:** 2026-11-14 01:02:03
- **Jumlah triple:** 1423  ·  **Jumlah produk:** 88
- **Daftar resmi jenis kulit:** Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif

## Ringkasan

| Kode | Anomali | Jumlah temuan | Produk/resource terdampak |
|---|---|---:|---:|
| A1 | Kolom tergeser: jenis kulit berisi nama bahan | 7 | 2 |
| A2 | Jenis kulit belum diisi | 2 | 2 |
| A3 | sourceProductId dobel / URI tidak konsisten | 42 | 18 |
| A4 | Bahan pecah karena split koma dan sisa Excel | 19 | 14 |
| A5 | Klaim/catatan tercampur dengan bahan | 16 | 10 |
| A6 | Produk tanpa kelompok umur | 2 | 2 |
| A7 | Data melanggar Teen Lock | 1 | 1 |
| A8 | Bahan tampak tersalin dari produk lain (heuristik) | 1 | 1 |
| A9 | Kategori dobel dan tidak konsisten | 18 | 18 |
| A10 | Typo dan label kosong | 65 | 64 |
| | **Total** | **173** | |

Catatan: satu produk bisa muncul di beberapa kategori, dan satu nilai bisa memicu lebih dari satu sub-jenis temuan.

## A1 — Kolom tergeser: jenis kulit berisi nama bahan

**Jumlah temuan:** 7

- Jenis kulit di luar daftar resmi: 7

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Jenis kulit di luar daftar resmi | ex:CRY-0003 | 3% Encapsulated Ferulic Acid | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). |
| 2 | Jenis kulit di luar daftar resmi | ex:CRY-0003 | Gold Peptide Crystals | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). Nilai yang sama tercatat sebagai bahan di produk lain. |
| 3 | Jenis kulit di luar daftar resmi | ex:CRY-0003 | Meadowfoam Lipidic Essence | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). |
| 4 | Jenis kulit di luar daftar resmi | ex:CRY-0003 | PHA | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). Nilai yang sama juga tercatat sebagai bahan produk ini. |
| 5 | Jenis kulit di luar daftar resmi | ex:CRY-0004 | Gold Peptide Crystals | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). Nilai yang sama juga tercatat sebagai bahan produk ini. |
| 6 | Jenis kulit di luar daftar resmi | ex:CRY-0004 | YouthGlow Active | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). Nilai yang sama juga tercatat sebagai bahan produk ini. |
| 7 | Jenis kulit di luar daftar resmi | ex:CRY-0004 | dan Triple Hyaluronic Acid | Bukan salah satu dari Berminyak, Kering, Kombinasi, Normal, SemuaJenisKulit, Sensitif; kemungkinan kolom tergeser (berisi nama bahan). |

**Keterbatasan pemeriksaan:** Hanya bisa mendeteksi nilai di luar daftar resmi. Nilai jenis kulit yang BENAR tidak bisa ditentukan otomatis; harus dicek di situs/label resmi.

## A2 — Jenis kulit belum diisi

**Jumlah temuan:** 2

- Jenis kulit masih placeholder: 2

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Jenis kulit masih placeholder | ex:CRY-0001 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |
| 2 | Jenis kulit masih placeholder | ex:CRY-0002 | PerluVerifikasi | Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata. |

**Keterbatasan pemeriksaan:** Placeholder dikenali dari kata kunci ('verifikasi', 'konfirmasi', 'not specified'). Placeholder dengan kata lain bisa terlewat.

## A3 — sourceProductId dobel / URI tidak konsisten

**Jumlah temuan:** 42

- URI terpotong: 6
- URI tidak sama dengan sourceProductId: 18
- sourceProductId dobel: 18

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | sourceProductId dobel | ex:LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5 | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 2 | URI tidak sama dengan sourceProductId | ex:LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5 | LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5 | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 3 | URI terpotong | ex:LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5 | …AcneOilCorrectPhysicalSunscreenSPF5 vs nama "Acne & Oil Correct Physical Sunscreen SPF 50+ PA++++" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 4 | sourceProductId dobel | ex:LAB-0053-AcneProBiomeAcneSpotGel | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 5 | URI tidak sama dengan sourceProductId | ex:LAB-0053-AcneProBiomeAcneSpotGel | LAB-0053-AcneProBiomeAcneSpotGel | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (32 karakter). |
| 6 | sourceProductId dobel | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 7 | URI tidak sama dengan sourceProductId | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT | LAB-0053-AcneProBiomeMicroExfoliatingGentleT | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 8 | URI terpotong | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT | …AcneProBiomeMicroExfoliatingGentleT vs nama "AcnePro Biome Micro Exfoliating Gentle Toner" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 9 | sourceProductId dobel | ex:LAB-0053-AcneProBiomeSebumAcneCleansingMouss | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 10 | URI tidak sama dengan sourceProductId | ex:LAB-0053-AcneProBiomeSebumAcneCleansingMouss | LAB-0053-AcneProBiomeSebumAcneCleansingMouss | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 11 | URI terpotong | ex:LAB-0053-AcneProBiomeSebumAcneCleansingMouss | …AcneProBiomeSebumAcneCleansingMouss vs nama "AcnePro Biome Sebum & Acne Cleansing Mousse" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 12 | sourceProductId dobel | ex:LAB-0053-AntiPigmentationBrighteningPhysical | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 13 | URI tidak sama dengan sourceProductId | ex:LAB-0053-AntiPigmentationBrighteningPhysical | LAB-0053-AntiPigmentationBrighteningPhysical | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 14 | URI terpotong | ex:LAB-0053-AntiPigmentationBrighteningPhysical | …AntiPigmentationBrighteningPhysical vs nama "Anti-Pigmentation Brightening Physical Sunscreen" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 15 | sourceProductId dobel | ex:LAB-0053-BarrierRestorativeAdvancedLotion | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 16 | URI tidak sama dengan sourceProductId | ex:LAB-0053-BarrierRestorativeAdvancedLotion | LAB-0053-BarrierRestorativeAdvancedLotion | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (41 karakter). |
| 17 | sourceProductId dobel | ex:LAB-0053-BiomeProtectPhysicalSunscreenSPF50P | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 18 | URI tidak sama dengan sourceProductId | ex:LAB-0053-BiomeProtectPhysicalSunscreenSPF50P | LAB-0053-BiomeProtectPhysicalSunscreenSPF50P | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 19 | URI terpotong | ex:LAB-0053-BiomeProtectPhysicalSunscreenSPF50P | …BiomeProtectPhysicalSunscreenSPF50P vs nama "BiomeProtect Physical Sunscreen SPF 50+ PA++++" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 20 | sourceProductId dobel | ex:LAB-0053-BiomeRepairBarrierReviveCream | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 21 | URI tidak sama dengan sourceProductId | ex:LAB-0053-BiomeRepairBarrierReviveCream | LAB-0053-BiomeRepairBarrierReviveCream | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (38 karakter). |
| 22 | sourceProductId dobel | ex:LAB-0053-BiomeRepairTopiCalmCream | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 23 | URI tidak sama dengan sourceProductId | ex:LAB-0053-BiomeRepairTopiCalmCream | LAB-0053-BiomeRepairTopiCalmCream | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (33 karakter). |
| 24 | sourceProductId dobel | ex:LAB-0053-GentleBiomeBarrierRepairSerum | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 25 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeBarrierRepairSerum | LAB-0053-GentleBiomeBarrierRepairSerum | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (38 karakter). |
| 26 | sourceProductId dobel | ex:LAB-0053-GentleBiomeHydratingFaceBodyCleanse | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 27 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeHydratingFaceBodyCleanse | LAB-0053-GentleBiomeHydratingFaceBodyCleanse | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (44 karakter). |
| 28 | URI terpotong | ex:LAB-0053-GentleBiomeHydratingFaceBodyCleanse | …GentleBiomeHydratingFaceBodyCleanse vs nama "GentleBiome Hydrating Face & Body Cleanser" | Akhiran URI berhenti di tengah nama produk (terpotong). |
| 29 | sourceProductId dobel | ex:LAB-0053-GentleBiomeHydrationToner | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 30 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeHydrationToner | LAB-0053-GentleBiomeHydrationToner | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (34 karakter). |
| 31 | sourceProductId dobel | ex:LAB-0053-GentleBiomeMildCleanser | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 32 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeMildCleanser | LAB-0053-GentleBiomeMildCleanser | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (32 karakter). |
| 33 | sourceProductId dobel | ex:LAB-0053-GentleBiomeSkinNutritionGel | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 34 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeSkinNutritionGel | LAB-0053-GentleBiomeSkinNutritionGel | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (36 karakter). |
| 35 | sourceProductId dobel | ex:LAB-0053-GentleBiomeThermalSoothingMist | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 36 | URI tidak sama dengan sourceProductId | ex:LAB-0053-GentleBiomeThermalSoothingMist | LAB-0053-GentleBiomeThermalSoothingMist | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (39 karakter). |
| 37 | sourceProductId dobel | ex:LAB-0053-HydratingPlumpingConcentratedSerum | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 38 | URI tidak sama dengan sourceProductId | ex:LAB-0053-HydratingPlumpingConcentratedSerum | LAB-0053-HydratingPlumpingConcentratedSerum | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (43 karakter). |
| 39 | sourceProductId dobel | ex:LAB-0053-IntensiveAcneSerumLactoTargetedSAL | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 40 | URI tidak sama dengan sourceProductId | ex:LAB-0053-IntensiveAcneSerumLactoTargetedSAL | LAB-0053-IntensiveAcneSerumLactoTargetedSAL | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (43 karakter). |
| 41 | sourceProductId dobel | ex:LAB-0053-MultiPigmentationBrighteningSerum | LAB-0053 | ID ini dipakai oleh 18 produk; ID harus unik. |
| 42 | URI tidak sama dengan sourceProductId | ex:LAB-0053-MultiPigmentationBrighteningSerum | LAB-0053-MultiPigmentationBrighteningSerum | Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama (42 karakter). |

**Keterbatasan pemeriksaan:** Deteksi URI terpotong membandingkan akhiran URI dengan nama produk; jika nama produk sendiri berbeda dari URI, potongan tidak terdeteksi.

## A4 — Bahan pecah karena split koma dan sisa Excel

**Jumlah temuan:** 19

- Beberapa bahan dalam satu nilai: 3
- Diawali angka-tanda hubung: 1
- Diawali kata penghubung: 7
- Kemungkinan huruf awal hilang: 1
- Kemungkinan typo pada nama bahan: 1
- Klaim SPF/PA di kolom bahan: 1
- Kurung tidak seimbang: 2
- Mengandung sisa Excel (+G): 2
- Panjang < 3 karakter: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Beberapa bahan dalam satu nilai | ex:CRY-0001 | Gold-Peptide Crystals dan Amino Ectoin | Mengandung 'dan/and' di tengah; kemungkinan dua bahan belum dipisah. |
| 2 | Diawali kata penghubung | ex:CRY-0004 | dan MatrixKey Molecule | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 3 | Diawali kata penghubung | ex:CRY-0005 | dan MatrixKey Molecule | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 4 | Beberapa bahan dalam satu nilai | ex:CRY-0007 | Gold Peptide Crystale dan YouthGlow Active | Mengandung 'dan/and' di tengah; kemungkinan dua bahan belum dipisah. |
| 5 | Beberapa bahan dalam satu nilai | ex:CRY-0008 | youthglow active dan tujuh minyak tanaman | Mengandung 'dan/and' di tengah; kemungkinan dua bahan belum dipisah. |
| 6 | Diawali kata penghubung | ex:CRY-0009 | dan Advanced Micro Peptide | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 7 | Diawali kata penghubung | ex:CRY-0010 | dan SPF 35 PA+++ | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 8 | Klaim SPF/PA di kolom bahan | ex:CRY-0010 | dan SPF 35 PA+++ | SPF/PA adalah tingkat perlindungan, bukan nama bahan. |
| 9 | Mengandung sisa Excel (+G) | ex:EMI-0040 | summer plum extract+G42 | Ada pola '+G<angka>' yang berasal dari sisa rumus/sel Excel. |
| 10 | Panjang < 3 karakter | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT (sourceProductId LAB-0053) | 1 | Hanya 1 karakter; kemungkinan potongan nama bahan (mis. '1,2-hexanediol' terpecah). |
| 11 | Diawali angka-tanda hubung | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT (sourceProductId LAB-0053) | 2-hexanediol | Kemungkinan sisa nama kimia yang terpotong (mis. '1,2-…'). |
| 12 | Diawali kata penghubung | ex:LAB-0053-AntiPigmentationBrighteningPhysical (sourceProductId LAB-0053) | and Glycyrrhiza Glabra (Licorice) Root Extract | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 13 | Diawali kata penghubung | ex:LAB-0053-HydratingPlumpingConcentratedSerum (sourceProductId LAB-0053) | and ferment lysates | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 14 | Kurung tidak seimbang | ex:TAV-0006 | Superberries (Goji | Jumlah '(' dan ')' tidak sama; bahan terpecah di tengah kurung karena split koma. |
| 15 | Kurung tidak seimbang | ex:TAV-0006 | dan Japanese Beauty Berry) + Amino Acid Complex | Jumlah '(' dan ')' tidak sama; bahan terpecah di tengah kurung karena split koma. |
| 16 | Diawali kata penghubung | ex:TAV-0006 | dan Japanese Beauty Berry) + Amino Acid Complex | Diawali 'dan/and/serta'; sisa pemecahan kalimat. |
| 17 | Mengandung sisa Excel (+G) | ex:WAR-0012 | UV filters+G25 | Ada pola '+G<angka>' yang berasal dari sisa rumus/sel Excel. |
| 18 | Kemungkinan typo pada nama bahan | ex:CRY-0007 | Gold Peptide Crystale dan YouthGlow Active | Kata 'Crystale' hanya muncul sekali dan mirip 'crystals' (muncul 6x di data). |
| 19 | Kemungkinan huruf awal hilang | ex:LAB-0053-GentleBiomeMildCleanser (sourceProductId LAB-0053) | accharide Isomerate | Kata 'accharide' kemungkinan terpotong dari 'saccharide'. |

**Keterbatasan pemeriksaan:** Berbasis pola (panjang < 3, '+G', kurung tidak seimbang, kata penghubung, kemiripan nama). Typo yang tidak punya 'pasangan' benar di dataset tidak bisa dideteksi.

## A5 — Klaim/catatan tercampur dengan bahan

**Jumlah temuan:** 16

- Catatan verifikasi di kolom bahan: 4
- Klaim produk di kolom bahan: 12

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Catatan verifikasi di kolom bahan | ex:CRY-0002 | Perlu verifikasi dari label resmi | Mengandung 'perlu verifikasi': ini catatan proses, seharusnya di verificationStatus. |
| 2 | Catatan verifikasi di kolom bahan | ex:CRY-0003 | Ferulic Acid (perlu konfirmasi label) | Mengandung 'perlu konfirmasi': ini catatan proses, seharusnya di verificationStatus. |
| 3 | Catatan verifikasi di kolom bahan | ex:EMI-0035 | cek INCI resmi | Mengandung 'cek inci': ini catatan proses, seharusnya di verificationStatus. |
| 4 | Catatan verifikasi di kolom bahan | ex:EMI-0038 | cek INCI | Mengandung 'cek inci': ini catatan proses, seharusnya di verificationStatus. |
| 5 | Klaim produk di kolom bahan | ex:LAB-0053-AcneProBiomeSebumAcneCleansingMouss (sourceProductId LAB-0053) | Free From | Mengandung 'free from': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 6 | Klaim produk di kolom bahan | ex:WAR-0003 | Alcohol Free | Mengandung 'alcohol free': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 7 | Klaim produk di kolom bahan | ex:WAR-0003 | Tanpa Pewangi | Mengandung 'tanpa': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 8 | Klaim produk di kolom bahan | ex:WAR-0004 | Alcohol Free | Mengandung 'alcohol free': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 9 | Klaim produk di kolom bahan | ex:WAR-0004 | Tanpa Pewangi | Mengandung 'tanpa': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 10 | Klaim produk di kolom bahan | ex:WAR-0006 | Tanpa Paraben | Mengandung 'tanpa': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 11 | Klaim produk di kolom bahan | ex:WAR-0007 | Alcohol Free | Mengandung 'alcohol free': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 12 | Klaim produk di kolom bahan | ex:WAR-0007 | Organik | Mengandung 'organik': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 13 | Klaim produk di kolom bahan | ex:WAR-0007 | Tanpa Pewangi | Mengandung 'tanpa': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 14 | Klaim produk di kolom bahan | ex:WAR-0008 | Alcohol Free | Mengandung 'alcohol free': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 15 | Klaim produk di kolom bahan | ex:WAR-0008 | Organik | Mengandung 'organik': ini klaim, bukan bahan (usulan: ex:hasClaim). |
| 16 | Klaim produk di kolom bahan | ex:WAR-0008 | Tanpa Pewangi | Mengandung 'tanpa': ini klaim, bukan bahan (usulan: ex:hasClaim). |

**Keterbatasan pemeriksaan:** Berbasis daftar kata kunci; klaim dengan kata lain tidak terdeteksi.

## A6 — Produk tanpa kelompok umur

**Jumlah temuan:** 2

- Tidak punya kelompok umur: 2

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Tidak punya kelompok umur | ex:KAH-0047 | "Bright Fresh Sunscreen Serum SPF 35 PA++++" | Tidak punya suitableForAgeGroup; produk hilang setiap kali user memilih umur. |
| 2 | Tidak punya kelompok umur | ex:LAB-0053-BarrierRestorativeAdvancedLotion (sourceProductId LAB-0053) | "Barrier Restorative Advanced Lotion" | Tidak punya suitableForAgeGroup; produk hilang setiap kali user memilih umur. |

**Keterbatasan pemeriksaan:** Dapat diperiksa penuh secara otomatis.

## A7 — Data melanggar Teen Lock

**Jumlah temuan:** 1

- Retinoid ditandai Teen: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Retinoid ditandai Teen | ex:LAB-0053-AcneProBiomeAcneSpotGel (sourceProductId LAB-0053) | "Micro Encapsulation Retinol"; umur: Adult, Teen | Bahan mengandung 'retinol' (golongan retinoid) tetapi produk ditandai ex:Teen; melanggar Teen Lock. |

**Keterbatasan pemeriksaan:** Retinoid dikenali dari kata kunci nama (retinol, retinal, retinyl, dsb.) karena bahan masih berupa teks. Setelah ontologi v2 (kelas ex:Retinoid) pemeriksaan ini bisa berbasis kelas.

## A8 — Bahan tampak tersalin dari produk lain (heuristik)

**Jumlah temuan:** 1

- Bahan menyebut teknologi seri lain: 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Bahan menyebut teknologi seri lain | ex:LAB-0053-MultiPigmentationBrighteningSerum (sourceProductId LAB-0053) | "acnepro biome™ technology"; seri produk: BighteningSeries | Nama bahan memuat 'AcneProSeries' padahal produk bukan bagian seri itu; kemungkinan tersalin dari produk lain. Wajib dicek manual di situs resmi. |

**Keterbatasan pemeriksaan:** TIDAK bisa dipastikan otomatis. Skrip hanya menandai bahan yang menyebut nama seri/teknologi lain. Kebenaran komposisi harus dicek manual di situs resmi brand.

## A9 — Kategori dobel dan tidak konsisten

**Jumlah temuan:** 18

- Kandidat duplikat/sinonim (awalan nama sama): 6
- Kandidat sub-kategori (nama satu memuat nama lain): 11
- Kategori dobel (beda huruf besar/kecil): 1

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Kategori dobel (beda huruf besar/kecil) | FaceMistCategory, FacemistCategory | FaceMistCategory dipakai 1 produk; FacemistCategory dipakai 2 produk | Nama sama, hanya beda kapitalisasi; harus disatukan. |
| 2 | Kandidat sub-kategori (nama satu memuat nama lain) | CleanserCategory ↔ MicellarCleanserCategory | CleanserCategory (19 produk), MicellarCleanserCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 3 | Kandidat sub-kategori (nama satu memuat nama lain) | DaymoisturizerSPFCategory ↔ MoisturizerCategory | DaymoisturizerSPFCategory (1 produk), MoisturizerCategory (9 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 4 | Kandidat sub-kategori (nama satu memuat nama lain) | EssenceCategory ↔ EssencetonerCategory | EssenceCategory (1 produk), EssencetonerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 5 | Kandidat sub-kategori (nama satu memuat nama lain) | EssencetonerCategory ↔ TonerCategory | EssencetonerCategory (1 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 6 | Kandidat sub-kategori (nama satu memuat nama lain) | ExfoliatingTonerCategory ↔ TonerCategory | ExfoliatingTonerCategory (3 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 7 | Kandidat duplikat/sinonim (awalan nama sama) | EyeCareCategory ↔ EyecreamCategory | EyeCareCategory (1 produk), EyecreamCategory (2 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 8 | Kandidat duplikat/sinonim (awalan nama sama) | FaceMistCategory ↔ FacespraytonerCategory | FaceMistCategory (1 produk), FacespraytonerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 9 | Kandidat duplikat/sinonim (awalan nama sama) | FacemistCategory ↔ FacespraytonerCategory | FacemistCategory (2 produk), FacespraytonerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 10 | Kandidat sub-kategori (nama satu memuat nama lain) | FacespraytonerCategory ↔ TonerCategory | FacespraytonerCategory (1 produk), TonerCategory (5 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 11 | Kandidat duplikat/sinonim (awalan nama sama) | MicellarCleanserCategory ↔ MicellarwaterCategory | MicellarCleanserCategory (1 produk), MicellarwaterCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 12 | Kandidat sub-kategori (nama satu memuat nama lain) | MoisturizerCategory ↔ NightmoisturizerCategory | MoisturizerCategory (9 produk), NightmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 13 | Kandidat sub-kategori (nama satu memuat nama lain) | MoisturizerCategory ↔ SunscreenmoisturizerCategory | MoisturizerCategory (9 produk), SunscreenmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 14 | Kandidat duplikat/sinonim (awalan nama sama) | NightCreamCategory ↔ NightmoisturizerCategory | NightCreamCategory (1 produk), NightmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 15 | Kandidat sub-kategori (nama satu memuat nama lain) | OilSerumCategory ↔ SerumCategory | OilSerumCategory (1 produk), SerumCategory (15 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 16 | Kandidat sub-kategori (nama satu memuat nama lain) | SunscreenCategory ↔ SunscreenmoisturizerCategory | SunscreenCategory (18 produk), SunscreenmoisturizerCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 17 | Kandidat sub-kategori (nama satu memuat nama lain) | SunscreenCategory ↔ SunscreentintedCategory | SunscreenCategory (18 produk), SunscreentintedCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |
| 18 | Kandidat duplikat/sinonim (awalan nama sama) | SunscreenmoisturizerCategory ↔ SunscreentintedCategory | SunscreenmoisturizerCategory (1 produk), SunscreentintedCategory (1 produk) | Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader). |

**Keterbatasan pemeriksaan:** Duplikat beda kapital pasti terdeteksi. Sinonim dengan nama berbeda total (mis. 'MicellarCleanser' vs 'Micellarwater') hanya muncul sebagai KANDIDAT; keputusan menyatukan/hierarki tetap manual.

## A10 — Typo dan label kosong

**Jumlah temuan:** 65

- AgeGroup tanpa rdfs:label: 2
- Brand tanpa rdfs:label: 6
- Category tanpa rdfs:label: 23
- Kemungkinan typo pada nama seri: 1
- ProductSeries tanpa rdfs:label: 33

| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |
|---:|---|---|---|---|
| 1 | Kemungkinan typo pada nama seri | ex:BighteningSeries | 'Bightening' vs 'brightening' (nama produk: "Multi-Pigmentation Brightening Serum") | Kata pada URI seri berbeda tipis dengan kata di nama produk anggotanya. |
| 2 | Brand tanpa rdfs:label | ex:CrystallureBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 3 | Brand tanpa rdfs:label | ex:EminaBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 4 | Brand tanpa rdfs:label | ex:KahfBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 5 | Brand tanpa rdfs:label | ex:LABOREBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 6 | Brand tanpa rdfs:label | ex:TAVIBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 7 | Brand tanpa rdfs:label | ex:WardahBrand | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 8 | ProductSeries tanpa rdfs:label | ex:AcneProSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 9 | ProductSeries tanpa rdfs:label | ex:Altero6MWHyaluSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 10 | ProductSeries tanpa rdfs:label | ex:BighteningSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 11 | ProductSeries tanpa rdfs:label | ex:BiomeProtectSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 12 | ProductSeries tanpa rdfs:label | ex:BiomeRepairSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 13 | ProductSeries tanpa rdfs:label | ex:BrightBlemishSolutionSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 14 | ProductSeries tanpa rdfs:label | ex:BrightStuffAcneProneSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 15 | ProductSeries tanpa rdfs:label | ex:BrightStuffSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 16 | ProductSeries tanpa rdfs:label | ex:CDefenseSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 17 | ProductSeries tanpa rdfs:label | ex:CrystalSecretSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 18 | ProductSeries tanpa rdfs:label | ex:DayNightSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 19 | ProductSeries tanpa rdfs:label | ex:DermalabRevolutionaireSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 20 | ProductSeries tanpa rdfs:label | ex:DoubleTheMoistSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 21 | ProductSeries tanpa rdfs:label | ex:FaceCareSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 22 | ProductSeries tanpa rdfs:label | ex:FirstCleanserSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 23 | ProductSeries tanpa rdfs:label | ex:GentleBiomeSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 24 | ProductSeries tanpa rdfs:label | ex:HydraRoseSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 25 | ProductSeries tanpa rdfs:label | ex:LightSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 26 | ProductSeries tanpa rdfs:label | ex:MoistureBoostSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 27 | ProductSeries tanpa rdfs:label | ex:MoistureRichSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 28 | ProductSeries tanpa rdfs:label | ex:NatureDailySeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 29 | ProductSeries tanpa rdfs:label | ex:RESQ10Series | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 30 | ProductSeries tanpa rdfs:label | ex:RadiantChargeSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 31 | ProductSeries tanpa rdfs:label | ex:RenewYouSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 32 | ProductSeries tanpa rdfs:label | ex:RetextureSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 33 | ProductSeries tanpa rdfs:label | ex:SerumActiveCareSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 34 | ProductSeries tanpa rdfs:label | ex:SlowAgingSignsSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 35 | ProductSeries tanpa rdfs:label | ex:SunBattleSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 36 | ProductSeries tanpa rdfs:label | ex:SupremeSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 37 | ProductSeries tanpa rdfs:label | ex:UVShieldSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 38 | ProductSeries tanpa rdfs:label | ex:UrbanShieldSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 39 | ProductSeries tanpa rdfs:label | ex:WaterBrightSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 40 | ProductSeries tanpa rdfs:label | ex:WhiteSecretSeries | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 41 | Category tanpa rdfs:label | ex:CleanserCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 42 | Category tanpa rdfs:label | ex:DaymoisturizerSPFCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 43 | Category tanpa rdfs:label | ex:EssenceCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 44 | Category tanpa rdfs:label | ex:EssencetonerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 45 | Category tanpa rdfs:label | ex:ExfoliatingTonerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 46 | Category tanpa rdfs:label | ex:EyeCareCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 47 | Category tanpa rdfs:label | ex:EyecreamCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 48 | Category tanpa rdfs:label | ex:FaceMistCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 49 | Category tanpa rdfs:label | ex:FacemistCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 50 | Category tanpa rdfs:label | ex:FacespraytonerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 51 | Category tanpa rdfs:label | ex:LotionCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 52 | Category tanpa rdfs:label | ex:MicellarCleanserCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 53 | Category tanpa rdfs:label | ex:MicellarwaterCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 54 | Category tanpa rdfs:label | ex:MoisturizerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 55 | Category tanpa rdfs:label | ex:NightCreamCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 56 | Category tanpa rdfs:label | ex:NightmoisturizerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 57 | Category tanpa rdfs:label | ex:OilSerumCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 58 | Category tanpa rdfs:label | ex:SerumCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 59 | Category tanpa rdfs:label | ex:SpotTreatmentCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 60 | Category tanpa rdfs:label | ex:SunscreenCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 61 | Category tanpa rdfs:label | ex:SunscreenmoisturizerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 62 | Category tanpa rdfs:label | ex:SunscreentintedCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 63 | Category tanpa rdfs:label | ex:TonerCategory | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 64 | AgeGroup tanpa rdfs:label | ex:Adult | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |
| 65 | AgeGroup tanpa rdfs:label | ex:Teen | (tidak ada label) | Tidak ada rdfs:label "…"@id; UI terpaksa menampilkan nama URI mentah. |

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
| 6 | Gold Peptide Crystals · Gold-Peptide Crystals · gold peptide crystals | 5 |
| 7 | Hyaluronic Acid · hyaluronic acid | 6 |
| 8 | 10% niacinamide · Niacinamide · Niacinamide 10% · Niacinamide 5% · niacinamide · niacinamide 5% | 15 |
| 9 | Salicylic Acid (BHA) · salicylic acid (bha) | 2 |
| 10 | Sodium Hyaluronate · sodium hyaluronate | 2 |
| 11 | Summer plum extract · summer plum extract | 4 |
| 12 | Zinc · zinc | 2 |

## Catatan umum

- Audit ini hanya **mendeteksi**; tidak ada data yang diperbaiki atau dihapus.
- Nilai pengganti (jenis kulit yang benar, komposisi yang benar, ID baru) **tidak** dibuat oleh skrip ini.
- Temuan bertanda *kandidat* / *kemungkinan* perlu dicek manusia sebelum dijadikan perbaikan di data v1.5.
