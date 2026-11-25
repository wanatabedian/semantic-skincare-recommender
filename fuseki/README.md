# Fuseki untuk Aphrodite

Panduan ini menjelaskan cara menyiapkan Apache Jena Fuseki agar semua anggota tim memakai data ontologi yang sama, yaitu `ontology/skincare_ontology_clean.ttl` yang dimuat ke dataset **`aphrodite`**.

> **Catatan (25 November 2026):** aplikasi (kuis, katalog, rekomendasi, detail produk) ditulis untuk IRI di `skincare_ontology_clean.ttl` (misalnya `ex:Oily`, `ex:Teen`). File `aphrodite-v2.ttl` memakai IRI lain (misalnya `ex:OilySkinType`), jadi **jangan dimuat ke dataset `aphrodite`** kecuali aplikasinya ikut disesuaikan. Dataset `aphrodite` di laptop pengembang sudah disinkronkan dengan file clean secara tertarget (1 triple dihapus, 39 ditambah) setelah backup ke `~/aphrodite-fuseki-backups/`; patch `*.sync.ru` dan `*.rollback.ru` disimpan di folder yang sama.

| Komponen | Versi / nilai |
|---|---|
| Apache Jena Fuseki | 5.1.0 (paket `apache-jena-fuseki-5.1.0`) |
| Java | 17 atau lebih baru |
| Dataset | `aphrodite` |
| Konfigurasi generik (`fuseki/aphrodite-config.ttl`) | TDB2 **tanpa reasoner**, lokasi database **relatif** |
| Konfigurasi runtime di laptop Knowledge Engineer | TDB2 dengan **`OWLFBRuleReasoner`**, lokasi database **absolut** |
| Endpoint query aplikasi | `http://127.0.0.1:3030/aphrodite/query` |
| Python + rdflib | Dibutuhkan `scripts/load.sh` untuk verifikasi RDF |

> ## ⚠️ Peringatan sebelum memakai
>
> 1. **Ada dua konfigurasi yang berbeda.** Dataset `aphrodite` yang sedang berjalan di laptop Knowledge Engineer (konfigurasi *runtime*) memakai `OWLFBRuleReasoner` dan lokasi TDB2 absolut. Konfigurasi generik `fuseki/aphrodite-config.ttl` memakai TDB2 polos tanpa reasoner dengan lokasi relatif. Isi dataset runtime masih data v2 sebelum commit `398429e`. Lihat bagian 2.
> 2. **Jangan menjalankan `scripts/load.sh` terhadap dataset utama `aphrodite` sebelum Fase 4 mendapat persetujuan khusus.** Skrip menolak dataset yang sudah berisi data, kecuali diberi `CONFIRM_OVERWRITE=yes`. **Jangan memberi konfirmasi itu untuk dataset utama** sebelum Fase 4 disetujui.
> 3. **Backup fisik dataset utama sudah dibuat pada 21 November 2026**, saat Fuseki dihentikan, dan diverifikasi identik dengan sumbernya melalui manifest SHA-256. Namun **pemulihan dataset ber-reasoner belum diuji**, jadi belum terbukti bahwa backup itu bisa dipulihkan dengan benar.
> 4. **Backup logis dari dataset ber-reasoner bukan salinan persis kondisi dasar.** Ekspornya mencampur triple asli dengan triple hasil inferensi, dan keduanya tidak bisa dipisahkan lagi. Salinan persis hanya bisa didapat dari backup fisik (lihat bagian 4e).
> 5. **Lokasi TDB2 relatif dihitung dari *working directory* proses Fuseki**, bukan dari lokasi file konfigurasi. Hal ini teruji saat dataset dibuat ketika Fuseki sedang berjalan. Ini berlaku untuk konfigurasi generik dan dataset yang dibuat darinya (misalnya `aphrodite_tmp`), **bukan** untuk konfigurasi runtime yang memakai lokasi absolut. **Perkiraan (belum diuji):** menjalankan Fuseki dari folder lain dapat membuat **folder database baru yang kosong**, sehingga data tampak hilang padahal masih tersimpan di lokasi lama. Lihat bagian 3.
> 6. **Restart Fuseki dengan konfigurasi runtime berhasil diuji pada 21 November 2026** setelah konfigurasi Shiro final diterapkan. Pengujian ini **tidak** membuktikan perilaku restart konfigurasi generik `aphrodite-config.ttl` yang memakai lokasi relatif.
> 7. **Endpoint `/aphrodite` dibatasi ke localhost.** Aturan terakhir `/**=anon` tetap membuka semua path lain yang tidak cocok dengan aturan di atasnya. Aturan itu **tidak** melindungi seluruh dataset. Lihat bagian 7.

## 1. File di folder ini

| File | Fungsi |
|---|---|
| `aphrodite-config.ttl` | Konfigurasi generik dataset `aphrodite`. Dipakai `scripts/load.sh` hanya jika dataset belum ada |
| `README.md` | Panduan ini |

Skrip pemuatan ada di `scripts/load.sh`.

## 2. Konfigurasi generik dan konfigurasi runtime

### 2a. Perbedaan keduanya

| Aspek | Generik (`fuseki/aphrodite-config.ttl`) | Runtime (laptop Knowledge Engineer) |
|---|---|---|
| Penyimpanan | TDB2 | TDB2 |
| Reasoner | Tidak ada | `OWLFBRuleReasoner` |
| Lokasi database | Relatif: `run/databases/aphrodite` | Absolut |
| Jumlah triple yang terlihat lewat query | Sama persis dengan file ontologi | Dapat berbeda dari file ontologi karena triple hasil inferensi dan versi data runtime yang lebih lama |
| Isi data | Dimuat dari `ontology/skincare_ontology_clean.ttl` melalui `load.sh` | Sama dengan `skincare_ontology_clean.ttl` (disinkronkan 25 November 2026) |
| Restart Fuseki | Belum diuji | Berhasil diuji (21 November 2026) |

`load.sh` hanya memakai konfigurasi generik **jika dataset belum ada**. Jika dataset sudah ada, konfigurasinya tidak diubah. Artinya, menjalankan `load.sh` terhadap dataset runtime tidak menghapus reasoner-nya.

### 2b. Isi konfigurasi generik `aphrodite-config.ttl`

- **TDB2 polos.** Tidak memakai `ja:InfModel` atau `OWLFBRuleReasoner`, sehingga jumlah triple di dataset **sama persis** dengan jumlah triple file ontologi. Query yang membutuhkan hierarki kelas harus menulis `rdfs:subClassOf*` sendiri, misalnya Teen Lock: `?i a/rdfs:subClassOf* ex:Retinoid`.
- **Lokasi database relatif:** `run/databases/aphrodite`. Path ini dibaca relatif terhadap **working directory proses Fuseki**, bukan relatif terhadap file konfigurasi (Fuseki menyimpan salinan konfigurasi di `run/configuration/`). Dalam uji Fase 2, database terbentuk di `<folder-fuseki>/run/databases/<dataset>` karena Fuseki dijalankan dari folder instalasinya.
  - Yang sudah teruji hanya saat dataset **dibuat** ketika Fuseki sedang berjalan.
  - **Perilaku setelah restart untuk lokasi relatif belum diuji**: apakah path dihitung ulang dari working directory saat Fuseki dinyalakan kembali, dan apa yang terjadi jika working directory-nya berbeda. Uji restart 21 November 2026 memakai konfigurasi runtime dengan lokasi absolut, jadi tidak menjawab pertanyaan ini.
  - Demi keamanan, **selalu jalankan Fuseki dari folder instalasinya** (lihat bagian 3).
- **Endpoint:**

| Endpoint | Operasi | Keterangan |
|---|---|---|
| `/aphrodite/query`, `/aphrodite/sparql`, `/aphrodite` | SPARQL Query | Baca |
| `/aphrodite/get` | Graph Store Protocol (baca) | Baca / ekspor |
| `/aphrodite/data` | Graph Store Protocol (baca-tulis) | Hanya untuk `scripts/load.sh` |
| `update`, `upload` | — | **Sengaja tidak dibuka** |

## 3. Menjalankan Fuseki

Ganti `/path/ke/apache-jena-fuseki-5.1.0` dengan **lokasi instalasi Fuseki yang sebenarnya** di laptop Anda, misalnya folder tempat arsip Fuseki diekstrak. Biarkan tanda kutipnya agar path yang mengandung spasi tetap terbaca dengan benar.

```bash
cd "/path/ke/apache-jena-fuseki-5.1.0"
./fuseki-server
```

- Tunggu sampai log menampilkan `Started` dan port `3030`.
- Hentikan dengan `Ctrl+C` di terminal yang sama.
- Cek dari terminal lain: `curl http://127.0.0.1:3030/$/ping`.

**Status uji restart (21 November 2026):**

- Fuseki 5.1.0 **berhasil dijalankan kembali** dengan konfigurasi runtime (reasoner `OWLFBRuleReasoner`, lokasi TDB2 absolut) setelah konfigurasi Shiro final diterapkan.
- Setelah restart, permintaan lokal ke `/$/status`, `/$/server`, `/$/metrics`, `/$/ping`, dan `/aphrodite` menghasilkan HTTP 200.
- Restart dengan konfigurasi generik (lokasi relatif) **belum diuji**.

> Jangan menjalankan `./fuseki-server` dari folder lain. **Perkiraan (belum diuji):** untuk dataset dengan lokasi relatif, path bisa menunjuk ke `<folder-lain>/run/databases/<dataset>`, dan Fuseki dapat membuat **database baru yang kosong** di sana. Data lama kemungkinan tetap ada di lokasi semula, tetapi tidak terbaca sampai Fuseki dijalankan lagi dari folder instalasinya. Lokasi absolut pada konfigurasi runtime tidak bergantung pada working directory.

## 4. Memuat data dengan `scripts/load.sh`

### 4a. Dataset baru atau kosong

Tanpa `DATASET=...`, skrip memakai `DATASET=aphrodite`. Jika dataset itu **sudah berisi data**, pengaman skrip **berhenti sebelum backup dan sebelum PUT**, jadi tidak ada yang diubah.

```bash
# dari root repo (atau folder mana saja)
scripts/load.sh
```

### 4b. Menimpa dataset yang sudah berisi data (butuh konfirmasi eksplisit)

> ⚠️ **Baca dulu sebelum menyalin perintah di bawah.** `CONFIRM_OVERWRITE=yes` mengizinkan skrip **mencadangkan lalu menimpa default graph** dataset yang dituju. Jika `DATASET=` tidak ditulis, yang ditimpa adalah **dataset utama `aphrodite`**, yang di laptop Knowledge Engineer masih memakai reasoner. **Jangan arahkan perintah ini ke `aphrodite` sebelum Fase 4 disetujui.** Backup fisik dataset utama sudah tersedia, tetapi pemulihannya belum diuji. Selalu tulis `DATASET=` secara eksplisit.

Bentuk umum. Ganti `<nama-dataset>` sebelum menjalankan, dan biarkan tanda kutipnya:

```bash
CONFIRM_OVERWRITE=yes DATASET='<nama-dataset>' scripts/load.sh
```

Tanda kutip **wajib**. Tanpa kutip, shell membaca `<` dan `>` sebagai pengalihan file. Dalam uji lokal, hal itu bisa **mengosongkan file `scripts/load.sh`** jika kebetulan ada file bernama `nama-dataset` di folder aktif. Dengan kutip, jika placeholder tersalin apa adanya, skrip langsung menolak nama `<nama-dataset>` di langkah validasi, sebelum menghubungi Fuseki.

### 4c. Contoh aman dengan dataset sementara `aphrodite_tmp`

Polanya sama dengan uji Fase 2. Semua perintah di blok ini menyebut `aphrodite_tmp` secara eksplisit, jadi menyalin seluruh blok sekaligus tidak menyentuh dataset utama.

```bash
# 1. Salinan konfigurasi dengan nama & lokasi aphrodite_tmp (di luar repo; jalankan dari root repo)
sed -e 's/fuseki:name "aphrodite"/fuseki:name "aphrodite_tmp"/' \
    -e 's|run/databases/aphrodite"|run/databases/aphrodite_tmp"|' \
    fuseki/aphrodite-config.ttl > /tmp/aphrodite_tmp-config.ttl

# 2. Pemuatan pertama: dataset sementara dibuat otomatis (tanpa konfirmasi)
DATASET=aphrodite_tmp FUSEKI_CONFIG=/tmp/aphrodite_tmp-config.ttl scripts/load.sh

# 3. Pemuatan ulang ke dataset sementara yang sudah berisi data
CONFIRM_OVERWRITE=yes DATASET=aphrodite_tmp scripts/load.sh

# 4. Bersihkan. DELETE juga menghapus file database dataset ini. Nama harus tetap aphrodite_tmp.
curl -X DELETE 'http://127.0.0.1:3030/$/datasets/aphrodite_tmp'
```

**Jangan pernah** mengubah langkah 4 menjadi `aphrodite`. Jika langkah 1 gagal, misalnya karena tidak dijalankan dari root repo, langkah 2 dan 3 akan berhenti di pemeriksaan nama konfigurasi tanpa menyentuh dataset lain.

Perintah di atas memakai admin API (`/$/...`) dan endpoint `/aphrodite_tmp`, yang keduanya dibatasi ke localhost oleh konfigurasi Shiro final (bagian 7). Jalankan dari laptop yang sama dengan Fuseki.

### 4d. Yang dilakukan skrip

1. Memvalidasi `DATASET` (hanya huruf, angka, `_`, dan `-`), lalu memeriksa `curl`, Python + `rdflib`, file ontologi, dan status Fuseki.
2. Jika dataset **belum ada**, membuatnya dari `fuseki/aphrodite-config.ttl`. Jika **sudah ada**, konfigurasinya tidak diubah.
3. **Pengaman `CONFIRM_OVERWRITE`:** jika dataset sudah berisi data, skrip **berhenti sebelum backup dan sebelum PUT**, kecuali `CONFIRM_OVERWRITE=yes` diset. Skrip menampilkan jumlah triple saat ini dan cara memberi konfirmasi. Tidak ada nilai default, jadi konfirmasi tidak pernah aktif dengan sendirinya. Dataset baru atau kosong diproses tanpa konfirmasi.
4. Setelah dikonfirmasi, skrip membuat **backup logis** (N-Quads) ke `~/aphrodite-fuseki-backups/<waktu>-<PID>-<dataset>-logical.nq`. Backup diverifikasi dengan tiga cara: kode keluar curl dan HTTP 200, parser rdflib (jumlah quad harus sama dengan isi server), dan checksum SHA-256 (`shasum -a 256` atau `sha256sum`). Jika salah satu gagal, pemuatan dibatalkan.
5. Memuat ontologi dengan **HTTP PUT ke default graph** (`/data?default`). PUT ini **hanya mengganti default graph**; named graph di dataset tidak disentuh. Skrip aman dijalankan berulang tanpa menggandakan triple.
6. Memverifikasi:
   - jumlah triple sama dengan jumlah triple file;
   - jumlah produk 88;
   - `ex:ActiveIngredient`, `ex:routineStep`, dan `ex:NiacinamideIngredient ex:targetsConcern ex:DullnessBrightening` ada;
   - tidak ada `ex:LAB-0069 ex:suitableForAgeGroup ex:Teen`.

Skrip **tidak pernah menghapus dataset** dan tidak mengubah pengaturan keamanan.

Variabel opsional:

| Variabel | Default |
|---|---|
| `CONFIRM_OVERWRITE` | *(kosong)*. Harus `yes` untuk menimpa dataset yang berisi data |
| `FUSEKI_URL` | `http://127.0.0.1:3030` |
| `DATASET` | `aphrodite` |
| `FUSEKI_CONFIG` | `fuseki/aphrodite-config.ttl` |
| `TTL_FILE` | `ontology/skincare_ontology_clean.ttl` |
| `BACKUP_DIR` | `~/aphrodite-fuseki-backups` (wajib di luar repo) |
| `EXPECTED_PRODUCTS` | `88` |
| `PYTHON` | `.venv/bin/python`, lalu `python3` |

Jika `rdflib` belum terpasang, jalankan `python3 -m pip install rdflib`, atau buat `.venv` di root repo.

### 4e. Backup logis dan backup fisik

Keduanya berbeda:

| Jenis | Cara membuat | Isi | Catatan |
|---|---|---|---|
| Backup logis | Otomatis oleh `load.sh` (ekspor N-Quads) | Triple yang terlihat lewat query | Cukup untuk memulihkan **isi** dataset, tetapi **bukan salinan persis** database TDB2 atau konfigurasinya. Pada dataset ber-reasoner, triple hasil inferensi ikut tercampur |
| Backup fisik | Menyalin folder database TDB2 saat Fuseki **dihentikan** | Salinan persis file database | Satu-satunya cara mendapatkan salinan persis kondisi dasar |

**Status backup fisik dataset utama:** sudah dibuat pada 21 November 2026 saat Fuseki dihentikan. Salinan dan sumbernya diverifikasi identik menggunakan manifest SHA-256. **Pemulihan dari backup fisik ini belum diuji.**

### 4f. Memulihkan isi dari backup logis

> ⚠️ **Perintah pemulihan di bawah mengganti seluruh isi dataset yang dituju.** Jika `<dataset>` diisi `aphrodite`, yang ditimpa adalah **dataset utama**. Lakukan itu **hanya setelah Fase 4 disetujui**, dan ingat bahwa pemulihan dataset ber-reasoner belum diuji. Untuk latihan, gunakan dataset sementara, misalnya `aphrodite_tmp`.

Ganti `<file>` dan `<dataset>` sebelum menjalankan, dan biarkan tanda kutipnya:

```bash
curl -X PUT -H 'Content-Type: application/n-quads' \
     --data-binary @"$HOME/aphrodite-fuseki-backups/<file>.nq" \
     'http://127.0.0.1:3030/<dataset>/data'
```

- Perhatikan `@"$HOME/..."`. Jangan menulis `@~/...`, karena shell tidak mengubah `~` menjadi folder home jika letaknya setelah `@`.
- URL **wajib dikutip** karena memuat `<` dan `>`. Jika placeholder tersalin apa adanya, permintaan dikirim ke dataset `<dataset>` yang tidak ada.
- Dengan curl 8.7.1, jika file backup tidak ditemukan, curl berhenti dengan exit 26 dan permintaan tidak dikirim. Versi curl lain belum diuji; versi lama dapat mengirim body kosong. Karena itu, pastikan dulu file backup ada, misalnya dengan `ls -l "<path>"`.
- PUT **tanpa** `?default` ke `/data` mengganti **seluruh isi dataset** (default graph dan named graph) dengan isi file backup.
- Untuk dataset ber-reasoner, hasil pemulihan berisi triple hasil inferensi yang kini tersimpan sebagai data biasa, sehingga tidak persis sama dengan kondisi dasar semula.
- Status uji: pemulihan hanya teruji pada dataset TDB2 polos `aphrodite_tmp`. Lihat bagian 9.

### 4g. Pemadatan (*compaction*)

TDB2 menyimpan data dengan cara menambah, bukan menimpa, sehingga folder database ikut membesar setiap kali data dimuat ulang. Folder itu bisa dipadatkan.

> ⚠️ **Baca dulu sebelum menjalankan pemadatan.** Pemadatan menulis ulang file database dataset yang dituju. Dengan `deleteOld=true`, **folder data lama (`Data-000N`) dihapus secara permanen** dan tidak bisa dikembalikan dari Fuseki. Jika `<dataset>` diisi `aphrodite`, yang terdampak adalah **dataset utama**. Lakukan itu **hanya setelah Fase 4 disetujui** dan pastikan backup fisik dataset utama tersedia. Pemadatan baru diuji pada dataset sementara `aphrodite_tmp` (lihat bagian 9).

Ganti `<dataset>` sebelum menjalankan, dan biarkan tanda kutipnya:

```bash
curl -X POST 'http://127.0.0.1:3030/$/compact/<dataset>?deleteOld=true'
```

## 5. Pemeriksaan lewat browser

Karena `/aphrodite` dibatasi ke localhost (bagian 7), buka browser di laptop yang sama dengan Fuseki.

1. Buka `http://localhost:3030/`. Dataset `aphrodite` harus tampil.
2. Pilih **aphrodite → query**, lalu jalankan query berikut.

```sparql
PREFIX ex: <https://example.org/skincare-ontology#>
SELECT (COUNT(DISTINCT ?p) AS ?produk) WHERE { ?p a ex:Product }
```

Hasil yang diharapkan: **88**.

```sparql
SELECT (COUNT(*) AS ?triple) WHERE { ?s ?p ?o }
```

Hasil yang diharapkan untuk dataset dengan konfigurasi generik (tanpa reasoner): sama dengan jumlah triple file. Untuk commit `398429e`: **2074**. Pada dataset runtime ber-reasoner, angkanya dapat berbeda karena adanya triple hasil inferensi dan karena datanya masih versi sebelum commit `398429e`.

```sparql
PREFIX ex: <https://example.org/skincare-ontology#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
# Teen Lock: harus kosong
SELECT ?p WHERE { ?p ex:suitableForAgeGroup ex:Teen ; ex:hasIngredient ?i .
                  ?i a/rdfs:subClassOf* ex:Retinoid }
```

## 6. Validasi SHACL

`load.sh` tidak menjalankan SHACL. Untuk memvalidasi file ontologi:

```bash
.venv/bin/python -c "from pyshacl import validate; c,_,t=validate('ontology/aphrodite-v2.ttl', shacl_graph='ontology/shapes/aphrodite_shapes.ttl'); print(t)"
```

Per commit `398429e` masih ada **4 pelanggaran yang diketahui** karena bukti resminya belum tersedia:

| Produk | Properti yang belum terpenuhi |
|---|---|
| CRY-0003 | `suitableForSkinType` |
| CRY-0004 | `suitableForSkinType` |
| KAH-0047 | `suitableForAgeGroup` |
| LAB-0060 | `suitableForAgeGroup` |

Rinciannya ada di `reports/v2_changelog.md`. Pelanggaran ini **tidak boleh dihilangkan dengan mengisi data tanpa bukti** atau dengan melonggarkan shape SHACL.

## 7. Keamanan

### 7a. Perilaku bawaan Fuseki 5.1.0

- Fuseki mendengarkan di **semua alamat jaringan** pada port 3030.
- `run/shiro.ini` bawaan hanya membatasi admin API (`/$/**`) ke localhost. Endpoint dataset bebas diakses dari jaringan melalui aturan `/**=anon`.

### 7b. Layanan endpoint pada konfigurasi dataset generik

Butir di bawah ini membahas **layanan endpoint yang didefinisikan di `fuseki/aphrodite-config.ttl`** (konfigurasi generik). Ini **bukan** aturan pembatasan akses Shiro, dan tidak menjelaskan endpoint yang aktif pada konfigurasi runtime. Pembatasan akses Shiro dijelaskan di bagian 7c.

- Konfigurasi generik **tidak mendefinisikan** layanan `update` dan `upload` untuk `aphrodite`.
- Dalam konfigurasi generik, penulisan hanya bisa lewat layanan `/aphrodite/data` (Graph Store Protocol).

### 7c. Konfigurasi Shiro final (sudah diterapkan)

Bagian `[urls]` pada `run/shiro.ini` sekarang memuat aturan berikut, sesuai urutan aktual di file:

```ini
/$/status  = localhostFilter
/$/server  = localhostFilter
/$/ping    = anon
/$/metrics = localhostFilter
/$/**      = localhostFilter
/aphrodite    = localhostFilter
/aphrodite/   = localhostFilter
/aphrodite/** = localhostFilter
/aphrodite_tmp    = localhostFilter
/aphrodite_tmp/   = localhostFilter
/aphrodite_tmp/** = localhostFilter
/**=anon
```

| Pola URL | Filter | Arti |
|---|---|---|
| `/$/status` | `localhostFilter` | Status server hanya dari localhost |
| `/$/server` | `localhostFilter` | Informasi server hanya dari localhost |
| `/$/ping` | `anon` | Pemeriksaan hidup/mati server, boleh diakses anonim. Ditulis sebelum `/$/**` agar tidak tertangkap aturan itu |
| `/$/metrics` | `localhostFilter` | Metrik server hanya dari localhost |
| `/$/**` | `localhostFilter` | Sisa admin API hanya dari localhost |
| `/aphrodite` | `localhostFilter` | Endpoint dasar dataset `aphrodite` hanya dari localhost |
| `/aphrodite/` | `localhostFilter` | Path `/aphrodite/` (dengan garis miring) hanya dari localhost |
| `/aphrodite/**` | `localhostFilter` | Semua endpoint di bawah `/aphrodite/` hanya dari localhost |
| `/aphrodite_tmp` | `localhostFilter` | Endpoint dasar dataset `aphrodite_tmp` hanya dari localhost |
| `/aphrodite_tmp/` | `localhostFilter` | Path `/aphrodite_tmp/` hanya dari localhost |
| `/aphrodite_tmp/**` | `localhostFilter` | Semua endpoint di bawah `/aphrodite_tmp/` hanya dari localhost |
| `/**` | `anon` | **Aturan terakhir.** Akses anonim untuk path lain |

Penjelasan:

- `localhostFilter` adalah filter bawaan Fuseki. Permintaan dari selain `127.0.0.1` atau `::1` dijawab **403**.
- Aturan Shiro diproses dari atas, dan aturan pertama yang cocok yang dipakai. Karena itu `/**=anon` harus tetap menjadi aturan terakhir.
- **`/**=anon` mengizinkan akses anonim ke setiap path yang tidak cocok dengan aturan-aturan sebelumnya.** Aturan ini bukan pembatasan. Dataset lain (misalnya `pwsa`) tidak dibatasi oleh konfigurasi ini.
- Pola `/aphrodite/**` sudah mencakup `/aphrodite/data`, `/aphrodite/update`, dan `/aphrodite/upload`, sehingga rencana pengetatan per endpoint yang ditulis sebelumnya tidak diperlukan lagi.
- Shiro tidak bisa membedakan GET dan PUT di file ini. Akibatnya, **membaca** `/aphrodite` dari perangkat lain juga ditolak, bukan hanya menulis. Aplikasi yang memakai `http://127.0.0.1:3030/aphrodite/query` harus berjalan di mesin yang sama dengan Fuseki.
- Alternatif `./fuseki-server --localhost` membatasi **seluruh server**, termasuk dataset lain. Alternatif ini tidak dipakai.

### 7d. Hasil pengujian (21 November 2026)

| Pengujian | Hasil |
|---|---|
| Lokal ke `/$/status`, `/$/server`, `/$/metrics`, `/$/ping`, `/aphrodite` | HTTP 200 |
| Dari iPhone melalui Wi-Fi ke `http://192.168.18.85:3030/aphrodite` | HTTP 403, pesan `Access denied : only localhost access allowed` |

**Batas pengujian:** uji jaringan hanya membuktikan pembatasan pada endpoint `/aphrodite` yang diuji. Endpoint lain (misalnya `/aphrodite/query`, `/aphrodite/data`, `/aphrodite_tmp`, admin API, dan `/$/ping`) **belum diuji dari jaringan**, begitu pula kemungkinan akses jaringan lainnya.

## 8. Catatan integrasi untuk Orang 3 (Recommendation Engine)

- `app/Http/Controllers/SparqlController.php` baris 12 memakai endpoint tetap `http://localhost:3030/skincare/query`. **Dataset `skincare` tidak ada**; dataset yang dipakai bersama adalah `aphrodite`. Akibatnya `POST /api/recommendation` akan gagal.
- `app/Services/FusekiService.php` baris 38 juga memakai nilai cadangan `.../skincare/query`. Saat ini nilai itu tidak terpakai, karena `config/services.php` sudah mengarah ke `http://127.0.0.1:3030/aphrodite/query`. Namun jika config tidak ditemukan, nilai cadangan ini yang akan dipakai.
- Saran: ambil endpoint dari `config('services.fuseki.query_url')` di kedua tempat, lalu set `FUSEKI_QUERY_URL` di `.env` bila perlu.
- Tidak ada reasoner di Fuseki. Query yang membutuhkan hierarki kelas harus memakai `rdf:type/rdfs:subClassOf*` dan `skos:broader*` secara eksplisit. Relasi `ex:conflictsWith` sudah ditulis dua arah di data.
- Nama URI di data berbeda dari contoh di dokumen spesifikasi: `ex:OilySkinType` (bukan `ex:Oily`), `ex:AllSkinType` (bukan `ex:AllSkin`), `ex:RetinolIngredient`, `ex:NiacinamideIngredient`, dan seterusnya.

File-file di atas milik Orang 3 dan **tidak diubah** oleh Knowledge Engineer.

## 9. Status pengujian (2026-11-21)

Semua uji yang **mengubah data** dilakukan **hanya** pada dataset sementara `aphrodite_tmp`, yang dihapus setelah pengujian. Isi dataset `aphrodite` utama dan dataset lain tidak diubah. Pada dataset utama hanya dilakukan restart Fuseki, backup fisik saat Fuseki dihentikan, dan permintaan baca. Lingkungan uji: macOS, Bash 3.2.57, Fuseki 5.1.0, Python `.venv` dengan rdflib.

| Hal | Status | Hasil |
|---|---|---|
| Konfigurasi generik diterima Fuseki 5.1.0 lewat admin API | ✅ Diuji | Endpoint: query (`""`, `query`, `sparql`), `get`, `data`. Update, upload, dan tulis lewat URL dataset ditolak (405/415) |
| Lokasi DB relatif saat dataset dibuat | ✅ Diuji | Terbentuk di `<folder-fuseki>/run/databases/<dataset>` |
| Pemuatan ke dataset baru | ✅ Diuji | 2074 triple, 88 produk, semua penanda lolos |
| Pengaman `CONFIRM_OVERWRITE` | ✅ Diuji | Kosong, `YES`, `1`, `y`, `yes ` (dengan spasi), dan `true` ditolak sebelum backup dan PUT. File DB tidak berubah sama sekali (sidik jari identik). Hanya `yes` yang diterima |
| Pemuatan berulang (idempoten) | ✅ Diuji | Tetap 2074 triple |
| Backup logis + verifikasi rdflib + SHA-256 | ✅ Diuji | Jumlah quad sama dengan isi server; `shasum -c` OK |
| Penolakan `BACKUP_DIR` di dalam repo | ✅ Diuji | Berhenti, folder tidak tercipta |
| Kegagalan curl saat backup | ✅ Diuji | curl exit 23 terdeteksi; tidak ada PUT |
| Tanpa `shasum`/`sha256sum` | ✅ Diuji | Berhenti sebelum backup |
| Validasi `DATASET` dan `EXPECTED_PRODUCTS` | ✅ Diuji | Nama dengan `/`, spasi, `.`, `*`, dan angka tidak valid ditolak |
| `TTL_FILE` di luar repo | ✅ Diuji | Pemeriksaan Git dilewati tanpa peringatan palsu |
| Pemulihan dari backup logis (PUT N-Quads ke `/data`) pada dataset TDB2 polos | ✅ Diuji | Setelah sengaja memuat v1.5 (1513 triple), perintah pemulihan yang dicetak skrip mengembalikan 2074 triple, isomorfik dengan backup dan dengan commit `398429e` |
| Pemadatan `/$/compact/<dataset>?deleteOld=true` | ✅ Diuji | Tugas `success: true`; `Data-0001` diganti `Data-0002`; isi tetap 2074 |
| Perintah SHACL di bagian 6 | ✅ Diuji | `Conforms: False`, 4 pelanggaran yang diketahui (CRY-0003, CRY-0004, KAH-0047, LAB-0060) |
| Hasil di Fuseki isomorfik dengan commit `398429e` | ✅ Diuji | 2074 lawan 2074 (dataset sementara) |
| Restart Fuseki dengan konfigurasi runtime | ✅ Diuji | Fuseki 5.1.0 berjalan kembali setelah konfigurasi Shiro final diterapkan |
| Backup fisik dataset utama | ✅ Dilakukan | Dibuat saat Fuseki dihentikan; salinan dan sumber identik menurut manifest SHA-256 |
| Pembatasan Shiro `localhostFilter` | ✅ Diterapkan | Aturan final di bagian 7c |
| Akses lokal ke `/$/status`, `/$/server`, `/$/metrics`, `/$/ping`, `/aphrodite` | ✅ Diuji | HTTP 200 |
| Akses jaringan ke `/aphrodite` (iPhone via Wi-Fi) | ✅ Diuji | HTTP 403, `Access denied : only localhost access allowed` |
| Akses jaringan ke endpoint lain dan skenario jaringan lain | ❌ **Belum diuji** | Hanya `/aphrodite` yang diuji dari jaringan |
| Restart Fuseki dengan konfigurasi generik (lokasi relatif) | ❌ **Belum diuji** | — |
| **Pemulihan dataset ber-reasoner** (dari backup logis maupun fisik) | ❌ **Belum diuji** | Pemulihan hanya diuji pada dataset TDB2 polos |
| Linux, Windows (Git Bash/WSL), `python3` sistem tanpa `.venv` | ❌ **Belum diuji** | — |
