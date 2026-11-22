# ONTOLOGY DESIGN REPORT
## Aphrodite — Ontologi Produk Skincare

### 1. Pendahuluan

Aphrodite merupakan proyek sistem rekomendasi produk perawatan kulit (*skincare*) yang menerapkan teknologi Web Semantik. Dalam proyek ini, ontologi digunakan untuk merepresentasikan informasi produk secara terstruktur dan menghubungkan berbagai informasi yang berkaitan dengan produk.

Ontologi CLEAN menjadi dasar representasi pengetahuan yang digunakan dalam pengembangan Aphrodite. Struktur ontologi ini mencakup produk, merek, kategori, seri produk, kelompok usia, jenis kulit, masalah kulit, dan kandungan.

Laporan ini mendokumentasikan rancangan ontologi CLEAN, meliputi kelas, properti, hubungan antarentitas, contoh individu, serta hasil validasi.

### 2. Identitas dan Ruang Lingkup Ontologi

Ontologi yang didokumentasikan dalam laporan ini adalah:

| Informasi | Keterangan |
|---|---|
| Nama ontologi | `SkincareOntology` |
| File | `ontology/skincare_ontology_clean.ttl` |
| Namespace | `https://example.org/skincare-ontology#` |
| Format | Turtle (TTL) |
| Jumlah triple | 1.517 |
| Jumlah produk | 88 |
| Cakupan merek | Wardah, Emina, Kahf, LABORE, Crystallure, TAVI |

Ontologi berfokus pada representasi informasi produk skincare dan hubungan antara produk dengan karakteristik serta kebutuhan kulit.

### 3. Perancangan Kelas (Class Design)

Ontologi CLEAN memiliki delapan kelas utama.

| Kelas | Deskripsi |
|---|---|
| `Product` | Merepresentasikan produk skincare. |
| `Brand` | Merepresentasikan merek produk. |
| `Category` | Merepresentasikan kategori produk. |
| `ProductSeries` | Merepresentasikan seri atau rangkaian produk. |
| `AgeGroup` | Merepresentasikan kelompok usia pengguna. |
| `SkinType` | Merepresentasikan jenis kulit. |
| `SkinConcern` | Merepresentasikan masalah atau kebutuhan kulit. |
| `Ingredient` | Merepresentasikan kandungan produk. |

Kelas `Product` berperan sebagai pusat hubungan karena setiap produk dapat dikaitkan dengan beberapa informasi pendukung, seperti merek, kategori, kandungan, jenis kulit, dan kebutuhan kulit.

Pada ontologi CLEAN, kedelapan kelas tersebut belum memiliki hubungan hierarki `rdfs:subClassOf` yang didefinisikan secara eksplisit.

### 4. Perancangan Properti (Property Design)

Properti digunakan untuk menghubungkan individu atau menyimpan informasi tambahan mengenai individu.

#### 4.1 Object Properties

Terdapat tujuh *object properties* dalam ontologi CLEAN.

| Properti | Domain | Range | Keterangan |
|---|---|---|---|
| `belongsToSeries` | Product | ProductSeries | Menghubungkan produk dengan seri produknya. |
| `hasBrand` | Product | Brand | Menghubungkan produk dengan mereknya. |
| `hasCategory` | Product | Category | Menghubungkan produk dengan kategorinya. |
| `hasIngredient` | Product | Ingredient | Menghubungkan produk dengan kandungannya. |
| `suitableForAgeGroup` | Product | AgeGroup | Menghubungkan produk dengan kelompok usia yang sesuai. |
| `suitableForSkinType` | Product | SkinType | Menghubungkan produk dengan jenis kulit yang sesuai. |
| `treatsConcern` | Product | SkinConcern | Menghubungkan produk dengan kebutuhan atau masalah kulit yang dituju. |

#### 4.2 Datatype Properties

Terdapat empat *datatype properties*.

| Properti | Domain | Range | Keterangan |
|---|---|---|---|
| `estimatedPrice` | Product | `xsd:integer` | Menyimpan perkiraan harga produk. |
| `productName` | Product | `xsd:string` | Menyimpan nama produk. |
| `sourceProductId` | Product | `xsd:string` | Menyimpan identitas produk dari sumber data. |
| `verificationStatus` | Product | `xsd:string` | Menyimpan status verifikasi informasi produk. |

#### 4.3 Keputusan Desain Properti

Terdapat dua keputusan desain yang didokumentasikan melalui komentar pada ontologi:

1. `hasIngredient` digunakan untuk merepresentasikan bahan aktif utama dari kosakata bahan yang telah distandardisasi. Komentarnya menyebut maksimal tiga bahan. Namun, batas maksimal tersebut belum dinyatakan sebagai batasan formal pada deklarasi properti yang diperiksa.
2. `suitableForSkinType` menggunakan hubungan eksplisit ke `Dry`, `Normal`, dan `Oily` untuk produk yang ditujukan bagi semua jenis kulit. Pendekatan ini memudahkan proses penyaringan berdasarkan jenis kulit.

### 5. Perancangan Individu (Individual Design)

Individu merupakan objek konkret yang menjadi anggota suatu kelas.

Beberapa contoh individu dalam ontologi CLEAN adalah:

| Kelas | Contoh Individu | Label |
|---|---|---|
| AgeGroup | `Teen` | Remaja |
| AgeGroup | `Adult` | Dewasa |
| SkinType | `Dry` | Kering |
| SkinType | `Normal` | Normal |
| SkinType | `Oily` | Berminyak |
| Brand | `CrystallureBrand` | Crystallure |
| Brand | `EminaBrand` | Emina |
| Category | `CleanserCategory` | Pembersih Wajah (Cleanser) |
| SkinConcern | `Acne` | Jerawat |
| Ingredient | `AloeVera` | Aloe Vera |

Enam individu merek yang tersedia adalah Crystallure, Emina, Kahf, LABORE, TAVI, dan Wardah.

### 6. Contoh Representasi Hubungan Produk

Salah satu contoh individu produk dalam ontologi adalah `CRY-0001`, yaitu Crystallure Moisture Rich Cleansing Foam.

Produk tersebut direpresentasikan melalui beberapa hubungan:

| Properti | Nilai |
|---|---|
| `belongsToSeries` | MoistureRichSeries |
| `hasBrand` | CrystallureBrand |
| `hasCategory` | CleanserCategory |
| `hasIngredient` | Ectoin, Peptide |
| `suitableForAgeGroup` | Adult |
| `suitableForSkinType` | Dry, Normal, Oily |
| `treatsConcern` | Cleansing, HydrationMoisture |

Informasi tambahan produk disimpan melalui:

- `productName`: Crystallure Moisture Rich Cleansing Foam
- `estimatedPrice`: 70000
- `sourceProductId`: CRY-0001
- `verificationStatus`: Nama dan bahan utama terverifikasi di situs resmi

Contoh tersebut memperlihatkan bahwa satu individu produk dapat memiliki beberapa hubungan ke individu dari kelas yang berbeda.

### 7. Diagram Kelas Ontologi

Diagram kelas menggambarkan delapan kelas utama dan hubungan antar kelas melalui *object properties*.

Dalam rancangan CLEAN, `Product` menjadi kelas pusat yang terhubung dengan tujuh kelas lainnya:

- Product → ProductSeries
- Product → Brand
- Product → Category
- Product → Ingredient
- Product → AgeGroup
- Product → SkinType
- Product → SkinConcern

Sementara itu, empat *datatype properties* digunakan untuk menyimpan atribut literal pada kelas Product.

Diagram visual disertakan sebagai bagian dari dokumentasi desain.

### 8. Validasi dan Pemeriksaan Ontologi

Validasi dilakukan untuk memastikan struktur dan data ontologi memenuhi ketentuan yang diterapkan.

#### 8.1 Validasi SHACL

Validasi dilakukan menggunakan `ontology/shapes/aphrodite_shapes.ttl`.

Hasil pemeriksaan yang sebelumnya diperoleh:

`Conforms: True`

Hasil tersebut menunjukkan bahwa data yang diperiksa memenuhi batasan SHACL yang diterapkan pada saat pemeriksaan tersebut. Hasil ini belum dijalankan ulang dalam peninjauan dokumen terbaru.

#### 8.2 Pemeriksaan Konsistensi HermiT

Pemeriksaan dilakukan menggunakan reasoner HermiT terhadap ontologi CLEAN.

Hasil pemeriksaan yang sebelumnya diperoleh:

`ONTOLOGI CLEAN KONSISTEN`

Artinya, HermiT tidak menemukan inkonsistensi logis pada ontologi yang diperiksa. Pemeriksaan ini juga belum dijalankan ulang dalam peninjauan dokumen terbaru.

#### 8.3 Audit Kualitas Data

Audit dilakukan menggunakan `scripts/audit_data.py` dengan sumber `ontology/skincare_ontology_clean.ttl`.

Hasil audit:

- A1 sampai A9: tidak ditemukan temuan.
- A10: ditemukan sembilan individu `ProductSeries` tanpa `rdfs:label`.

Temuan tersebut meliputi:

1. `Altero6MWHyaluSeries`
2. `BrightBlemishSolutionSeries`
3. `BrightStuffAcneProneSeries`
4. `BrighteningSeries`
5. `DermalabRevolutionaireSeries`
6. `FaceCareSeries`
7. `FirstCleanserSeries`
8. `SerumActiveCareSeries`
9. `SlowAgingSignsSeries`

Temuan A10 menjadi catatan perbaikan agar individu seri produk dapat ditampilkan dengan label yang lebih mudah dipahami.

### 9. Catatan Pengembangan

Berdasarkan pemeriksaan ontologi CLEAN dan berkas SHACL, terdapat beberapa hal yang dapat menjadi perhatian untuk pengembangan selanjutnya:

1. Melengkapi label pada sembilan individu `ProductSeries` yang ditemukan dalam audit A10.
2. Mempertimbangkan penambahan hierarki kelas apabila kebutuhan sistem memerlukannya.
3. Memastikan aturan batas jumlah kandungan maksimal tiga diterapkan secara formal apabila memang menjadi ketentuan data.
4. Meninjau kesesuaian antara properti yang dirujuk oleh SHACL dan properti yang tersedia dalam ontologi CLEAN. Beberapa properti yang dirujuk dalam SHACL tetapi belum ditemukan dalam ontologi CLEAN yang diperiksa meliputi `routineStep`, `usageTime`, `sourceIngredientText`, `sourceSkinTypeText`, `schema:image`, `skos:altLabel`, dan `contraindicatedFor`.
5. Mempertahankan konsistensi struktur kelas, properti, dan individu ketika data produk dikembangkan.

Catatan tersebut merupakan bahan evaluasi pengembangan dan tidak mengubah ontologi CLEAN sebagai acuan utama.

### 10. Kesimpulan

Ontologi CLEAN merupakan dasar representasi pengetahuan pada proyek Aphrodite. Ontologi ini memiliki delapan kelas utama, tujuh *object properties*, dan empat *datatype properties* yang digunakan untuk merepresentasikan produk serta hubungan dengan informasi pendukungnya.

Hasil pemeriksaan sebelumnya menunjukkan bahwa ontologi memenuhi validasi SHACL yang dijalankan dan dinyatakan konsisten oleh HermiT. Audit kualitas data menunjukkan bahwa pemeriksaan A1 sampai A9 tidak menemukan temuan, sementara A10 mengidentifikasi sembilan individu `ProductSeries` yang belum memiliki label.

Dokumentasi ini diharapkan dapat menjadi acuan dalam memahami struktur, hubungan, dan keputusan desain ontologi CLEAN pada proyek Aphrodite.

---

### Referensi Berkas

1. `ontology/skincare_ontology_clean.ttl`
2. `ontology/shapes/aphrodite_shapes.ttl`
3. `scripts/audit_data.py`
4. `reports/audit_report.md`
