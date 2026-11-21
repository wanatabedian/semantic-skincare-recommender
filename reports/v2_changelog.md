# Changelog Ontologi v2 — Revisi Audit (2026-11-21)

Acuan: *Aphrodite — Kritik, Rencana Pengembangan & Pembagian Tugas 5 Orang* (hal. 2–4 temuan A1–A10/B1–B7, hal. 10–11 tugas Orang 1, hal. 14–15 contoh query).
Revisi ini memeriksa dan memperbaiki draf perubahan Copilot pada `aphrodite-v2.ttl` dan `aphrodite_shapes.ttl`. Tidak ada migrasi ulang; 88 produk tetap.

## Ringkasan

| Item | Nilai |
|---|---|
| Triple commit sebelumnya → sekarang | 1866 → 2074 |
| Triple lama yang dihapus | 1 (`ex:LAB-0069 ex:suitableForAgeGroup ex:Teen`, A7) |
| Jumlah Product | 88 (tidak berubah) |
| pySHACL (tanpa inferensi / RDFS) | 4 pelanggaran / 4 pelanggaran |
| OWL-RL (owlrl 7.6.2) | Tidak ada individu `owl:Nothing`, tidak ada error |
| HermiT (owlready2 0.51) | Konsisten; kemampuan deteksi diuji dengan inkonsistensi sengaja pada salinan sementara |

## Perubahan `ontology/aphrodite-v2.ttl`

### Skema
| # | Target | Perubahan | Alasan |
|---|---|---|---|
| 1 | prefix | Tambah `schema: <https://schema.org/>` | Dibutuhkan `schema:image` |
| 2 | `schema:image` | Deklarasi `owl:ObjectProperty` + label/komentar, tanpa domain, **tanpa nilai** | Wajib di daftar properti v2 (hal. 11); belum ada URL gambar terverifikasi |
| 3 | `ex:conflictsWith` | Hapus `rdfs:domain`/`rdfs:range ex:Ingredient`; tetap `owl:SymmetricProperty` | Spesifikasi memakainya antar-kelas (`Retinoid ↔ Exfoliant`); domain/range lama membuat kelas tersimpulkan sebagai Ingredient |
| 4 | `ex:contraindicatedFor`, `ex:Retinoid` | Tambah komentar: aturan Teen Lock sesuai spesifikasi, bukan klaim medis universal | Keputusan tim |
| 5 | `ex:routineStep` | Dari ObjectProperty (Product → RoutineStep) menjadi DatatypeProperty (Category → `xsd:integer`) | Spesifikasi B4 dan keputusan tim: urutan 1–6 pada kategori |
| 6 | `ex:RoutineStep` | Kelas draf Copilot dihapus | Tergantikan oleh poin 5; belum di-commit dan tidak dipakai kode |
| 7 | `ex:UsageTime` | Tambah individu `ex:PagiUsageTime`, `ex:MalamUsageTime`, `ex:KeduanyaUsageTime` | Spesifikasi B4 (Pagi/Malam/Keduanya). Belum ada produk yang dihubungkan |

### Fakta pengetahuan
| # | Triple | Dasar |
|---|---|---|
| 8 | `ex:Retinoid ex:conflictsWith ex:Exfoliant` + arah sebaliknya | Hal. 4 B3 dan hal. 11. Dua arah ditulis karena Fuseki tanpa reasoner tidak menerapkan simetri |
| 9 | `ex:RetinolIngredient ex:targetsConcern ex:AgingFineLines` | Contoh hal. 11 |
| 10 | Pertahankan `Retinol a Retinoid`, `GlycolicAcid a AHA`, `SalicylicAcid a BHA` | Contoh spesifikasi atau definisi golongan; dibutuhkan uji TAV-0004 + TAV-0002 |
| 11 | **Ditunda:** 13 triple klasifikasi (12 bahan) usulan Copilot | Tidak ada sumber; dicatat di `ontology/mappings/ingredient_class_review_v2.csv`. Individu bahan dan `hasIngredient` tidak disentuh |
| 12 | `ex:NiacinamideIngredient ex:targetsConcern ex:DullnessBrightening` (1 triple, **diterapkan**) | Spesifikasi hal. 4 B3: `ex:targetsConcern (Niacinamide → Kusam)`. Disetujui Knowledge Engineer 2026-11-21. Hanya relasi `targetsConcern`; Niacinamide **tidak** diberi kelas golongan |

### `routineStep` per kategori
| Step | Kategori |
|---|---|
| 1 cleanser | Cleanser, MicellarCleanser, Micellarwater |
| 2 toner | Toner, ExfoliatingToner, Essencetoner, Facespraytoner |
| 3 serum | Serum, OilSerum |
| 4 moisturizer | Moisturizer, Lotion, NightCream, Nightmoisturizer |
| 5 treatment | SpotTreatment |
| 6 sunscreen | Sunscreen, Sunscreentinted |
| *(belum diisi)* | Essence, FaceMist, EyeCare, Eyecream, DaymoisturizerSPF, Sunscreenmoisturizer |

### Data produk
| Produk | Perubahan | Bukti / status |
|---|---|---|
| LAB-0069 | Hapus `suitableForAgeGroup ex:Teen`; `ex:Adult` tetap; tambah `verificationNote` | A7: "Hapus ex:Teen dari produk ber-retinol" |
| CRY-0001 | Tambah `suitableForSkinType ex:AllSkinType` + `verificationNote` | `reports/crystallure_verification.md`: sumber resmi, status Verified, klaim "For All Skin Type". URL di laporan tampak terpotong; situs tidak dapat diakses ulang |
| CRY-0002 | Tambah `ex:AllSkinType` dengan catatan **SEMENTARA, belum terverifikasi** | Opsi spesifikasi A2 |
| CRY-0003, CRY-0004 | Hanya tambah `verificationNote` hasil pencarian | Halaman resmi tidak dapat diakses; nilai tetap kosong |
| KAH-0047 | Hanya tambah `verificationNote` (umur + selisih SPF 35 vs 50 di retailer) | Halaman resmi tidak ditemukan; nama produk **tidak** diubah |
| LAB-0060 | Hanya tambah `verificationNote` | Hanya sumber sekunder untuk versi relaunch; belum dipastikan formulanya sama |

`sourceSkinTypeText` dan `sourceIngredientText` tidak diubah.

## Perubahan `ontology/shapes/aphrodite_shapes.ttl`

| # | Shape | Perubahan |
|---|---|---|
| 1 | prefix | Tambah `schema:` |
| 2 | ProductShape | Hapus constraint `routineStep`/`RoutineStep` (pindah ke Category) |
| 3 | ProductShape | `usageTime`: tambah `sh:in` (Pagi/Malam/Keduanya) |
| 4 | ProductShape | `schema:image` opsional, `sh:nodeKind sh:IRI` |
| 5 | CategoryShape (baru) | `routineStep` opsional, maksimal 1, `xsd:integer` 1–6 |
| 6 | TeenLockShape (baru) | Constraint SPARQL: produk ber-Teen tidak boleh mengandung bahan dari kelas yang `contraindicatedFor ex:Teen`. Teruji menangkap LAB-0069 jika Teen dikembalikan |

Constraint wajib (tepat 1 Brand, minimal 1 Category/SkinType/AgeGroup, harga integer ≥ 1) **tidak dilonggarkan**.

## Pelanggaran SHACL yang tersisa (4) — menunggu bukti

| Produk | Path | Yang dibutuhkan |
|---|---|---|
| CRY-0003 | `suitableForSkinType` | Jenis kulit dari situs resmi/kemasan |
| CRY-0004 | `suitableForSkinType` | Jenis kulit dari situs resmi/kemasan |
| KAH-0047 | `suitableForAgeGroup` | Kelompok umur dari sumber resmi (sumber lama "19" ambigu) |
| LAB-0060 | `suitableForAgeGroup` | Kelompok umur dari sumber resmi untuk produk/formula yang sama |

## Catatan teknis
- Uji query konflik spesifikasi (hal. 15): TAV-0004 + TAV-0002 terdeteksi di kedua urutan (Retinol × Glycolic Acid → Retinoid × Exfoliant).
- Uji query Teen Lock spesifikasi (hal. 14): tidak ada produk Teen yang mengandung Retinoid.
- HermiT dijalankan lewat owlready2 0.51 (di `.venv` proyek) dengan Java 21. owlready2 tidak menampilkan individu karena data tidak memakai `owl:NamedIndividual`, tetapi data tetap diproses HermiT. Ini dibuktikan oleh uji inkonsistensi sengaja yang terdeteksi.
