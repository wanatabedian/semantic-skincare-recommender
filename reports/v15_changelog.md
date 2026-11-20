# Changelog Data v1.5

> Dibuat otomatis oleh `scripts/clean_v15.py`.

- Sumber: `ontology/skincare_ontology.ttl` (MD5 `2b0ea844d72aaa69be133700b747502f`, tidak diubah)
- Hasil: `ontology/aphrodite-v1.5.ttl`
- Produk: 88 → 88  ·  Triple: 1423 → 1513
- Penghapusan +G (referensi sel Excel): aktif

## Jumlah perubahan per kode

| Kode | Jumlah |
|---|---:|
| A1 | 2 |
| A2 | 4 |
| A3 | 18 |
| A4 | 16 |
| A5 | 18 |
| A6 | 2 |
| A7 | 1 |
| A8 | 1 |
| A9 | 3 |
| A10 | 65 |
| Skema | 3 |

## Detail

| # | Kode | Target | Sebelum | Sesudah | Alasan |
|---:|---|---|---|---|---|
| 1 | Skema | ex:hasClaim | (tidak ada) | owl:DatatypeProperty | Klaim pemasaran produk (mis. 'Alcohol Free', 'Tanpa Pewangi'), dipisahkan dari daftar bahan. |
| 2 | Skema | ex:verificationNote | (tidak ada) | owl:DatatypeProperty | Catatan verifikasi / temuan audit data. Boleh lebih dari satu nilai per produk. |
| 3 | Skema | ex:SkincareOntology | - | owl:versionInfo "1.5" | Penanda versi. |
| 4 | A3 | ex:LAB-0053 | ex:LAB-0053-GentleBiomeMildCleanser / sourceProductId "LAB-0053" | ex:LAB-0053 / sourceProductId "LAB-0053" | ID unik sesuai urutan baris Excel; URI = ID. |
| 5 | A3 | ex:LAB-0054 | ex:LAB-0053-AcneProBiomeSebumAcneCleansingMouss / sourceProductId "LAB-0053" | ex:LAB-0054 / sourceProductId "LAB-0054" | ID unik sesuai urutan baris Excel; URI = ID. |
| 6 | A3 | ex:LAB-0055 | ex:LAB-0053-GentleBiomeHydrationToner / sourceProductId "LAB-0053" | ex:LAB-0055 / sourceProductId "LAB-0055" | ID unik sesuai urutan baris Excel; URI = ID. |
| 7 | A3 | ex:LAB-0056 | ex:LAB-0053-AcneProBiomeMicroExfoliatingGentleT / sourceProductId "LAB-0053" | ex:LAB-0056 / sourceProductId "LAB-0056" | ID unik sesuai urutan baris Excel; URI = ID. |
| 8 | A3 | ex:LAB-0057 | ex:LAB-0053-GentleBiomeSkinNutritionGel / sourceProductId "LAB-0053" | ex:LAB-0057 / sourceProductId "LAB-0057" | ID unik sesuai urutan baris Excel; URI = ID. |
| 9 | A3 | ex:LAB-0058 | ex:LAB-0053-BiomeRepairBarrierReviveCream / sourceProductId "LAB-0053" | ex:LAB-0058 / sourceProductId "LAB-0058" | ID unik sesuai urutan baris Excel; URI = ID. |
| 10 | A3 | ex:LAB-0059 | ex:LAB-0053-BiomeRepairTopiCalmCream / sourceProductId "LAB-0053" | ex:LAB-0059 / sourceProductId "LAB-0059" | ID unik sesuai urutan baris Excel; URI = ID. |
| 11 | A3 | ex:LAB-0060 | ex:LAB-0053-BarrierRestorativeAdvancedLotion / sourceProductId "LAB-0053" | ex:LAB-0060 / sourceProductId "LAB-0060" | ID unik sesuai urutan baris Excel; URI = ID. |
| 12 | A3 | ex:LAB-0061 | ex:LAB-0053-GentleBiomeBarrierRepairSerum / sourceProductId "LAB-0053" | ex:LAB-0061 / sourceProductId "LAB-0061" | ID unik sesuai urutan baris Excel; URI = ID. |
| 13 | A3 | ex:LAB-0062 | ex:LAB-0053-MultiPigmentationBrighteningSerum / sourceProductId "LAB-0053" | ex:LAB-0062 / sourceProductId "LAB-0062" | ID unik sesuai urutan baris Excel; URI = ID. |
| 14 | A3 | ex:LAB-0063 | ex:LAB-0053-IntensiveAcneSerumLactoTargetedSAL / sourceProductId "LAB-0053" | ex:LAB-0063 / sourceProductId "LAB-0063" | ID unik sesuai urutan baris Excel; URI = ID. |
| 15 | A3 | ex:LAB-0064 | ex:LAB-0053-HydratingPlumpingConcentratedSerum / sourceProductId "LAB-0053" | ex:LAB-0064 / sourceProductId "LAB-0064" | ID unik sesuai urutan baris Excel; URI = ID. |
| 16 | A3 | ex:LAB-0065 | ex:LAB-0053-BiomeProtectPhysicalSunscreenSPF50P / sourceProductId "LAB-0053" | ex:LAB-0065 / sourceProductId "LAB-0065" | ID unik sesuai urutan baris Excel; URI = ID. |
| 17 | A3 | ex:LAB-0066 | ex:LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5 / sourceProductId "LAB-0053" | ex:LAB-0066 / sourceProductId "LAB-0066" | ID unik sesuai urutan baris Excel; URI = ID. |
| 18 | A3 | ex:LAB-0067 | ex:LAB-0053-AntiPigmentationBrighteningPhysical / sourceProductId "LAB-0053" | ex:LAB-0067 / sourceProductId "LAB-0067" | ID unik sesuai urutan baris Excel; URI = ID. |
| 19 | A3 | ex:LAB-0068 | ex:LAB-0053-GentleBiomeThermalSoothingMist / sourceProductId "LAB-0053" | ex:LAB-0068 / sourceProductId "LAB-0068" | ID unik sesuai urutan baris Excel; URI = ID. |
| 20 | A3 | ex:LAB-0069 | ex:LAB-0053-AcneProBiomeAcneSpotGel / sourceProductId "LAB-0053" | ex:LAB-0069 / sourceProductId "LAB-0069" | ID unik sesuai urutan baris Excel; URI = ID. |
| 21 | A3 | ex:LAB-0070 | ex:LAB-0053-GentleBiomeHydratingFaceBodyCleanse / sourceProductId "LAB-0053" | ex:LAB-0070 / sourceProductId "LAB-0070" | ID unik sesuai urutan baris Excel; URI = ID. |
| 22 | A1 | ex:CRY-0003 | 3% Encapsulated Ferulic Acid; Gold Peptide Crystals; Meadowfoam Lipidic Essence; PHA | PerluVerifikasi + verificationNote | Nilai bukan jenis kulit; tidak ditebak, nilai asli disimpan di catatan. |
| 23 | A1 | ex:CRY-0004 | Gold Peptide Crystals; YouthGlow Active; dan Triple Hyaluronic Acid | PerluVerifikasi + verificationNote | Nilai bukan jenis kulit; tidak ditebak, nilai asli disimpan di catatan. |
| 24 | A2 | ex:CRY-0001 | PerluVerifikasi | PerluVerifikasi (tetap) + verificationNote | Belum ada sumber resmi; hanya diberi catatan. |
| 25 | A2 | ex:CRY-0002 | PerluVerifikasi | PerluVerifikasi (tetap) + verificationNote | Belum ada sumber resmi; hanya diberi catatan. |
| 26 | A4 | ex:LAB-0056 | 1 \| 2-hexanediol | 1,2-hexanediol | Satu bahan '1,2-hexanediol' (sesuai Excel) terpecah oleh split koma. |
| 27 | A4 | ex:TAV-0006 | Superberries (Goji \| Acai \| dan Japanese Beauty Berry) + Amino Acid Complex | Superberries (Goji, Acai, dan Japanese Beauty Berry) + Amino Acid Complex | Digabung kembali persis seperti sel Excel; pecah karena split koma di dalam kurung. |
| 28 | A4 | ex:CRY-0004 | dan MatrixKey Molecule | MatrixKey Molecule | Kata penghubung daftar 'dan' dibuang (Excel: '..., YouthGlow Active, dan MatrixKey Molecule'). |
| 29 | A4 | ex:CRY-0005 | dan MatrixKey Molecule | MatrixKey Molecule | Kata penghubung daftar 'dan' dibuang. |
| 30 | A4 | ex:CRY-0009 | dan Advanced Micro Peptide | Advanced Micro Peptide | Kata penghubung daftar 'dan' dibuang. |
| 31 | A4 | ex:LAB-0064 | and ferment lysates | ferment lysates | Kata penghubung daftar 'and' dibuang (Excel: '..., Niacinamide, and ferment lysates'). |
| 32 | A4 | ex:LAB-0067 | and Glycyrrhiza Glabra (Licorice) Root Extract | Glycyrrhiza Glabra (Licorice) Root Extract | Kata penghubung daftar 'and' dibuang. |
| 33 | A4 | ex:CRY-0001 | Gold-Peptide Crystals dan Amino Ectoin | Gold-Peptide Crystals \| Amino Ectoin | Dua bahan dalam satu nilai; keduanya tercatat sebagai bahan tersendiri di produk Crystallure lain (Gold Peptide Crystals: CRY-0004/5/9; Amino Ectoin: CRY-0010). |
| 34 | A4 | ex:CRY-0007 | Gold Peptide Crystale dan YouthGlow Active | Gold Peptide Crystals \| YouthGlow Active | Dua bahan dalam satu nilai + typo 'Crystale'. 'Gold Peptide Crystals' dan 'YouthGlow Active' tercatat terpisah di CRY-0004/CRY-0005. |
| 35 | A4 | ex:CRY-0008 | youthglow active dan tujuh minyak tanaman | youthglow active \| tujuh minyak tanaman | Pola daftar Excel 'gold peptide crystals, youthglow active dan tujuh minyak tanaman': 'dan' memisahkan dua komponen; ejaan asli dipertahankan. |
| 36 | A4 | ex:WAR-0012 | UV filters+G25 | UV filters | '+G25' = referensi sel Excel (kolom G = Ingredients), bukan bahan; 'UV filters' ada di produk lain. |
| 37 | A4 | ex:EMI-0040 | summer plum extract+G42 | summer plum extract | '+G42' = referensi sel Excel (kolom G = Ingredients), bukan bahan; 'summer plum extract' ada di produk lain. |
| 38 | A5 | ex:WAR-0003 | hasIngredient "Alcohol Free" | hasClaim "Alcohol Free" | Klaim, bukan bahan. |
| 39 | A5 | ex:WAR-0003 | hasIngredient "Tanpa Pewangi" | hasClaim "Tanpa Pewangi" | Klaim, bukan bahan. |
| 40 | A5 | ex:WAR-0004 | hasIngredient "Alcohol Free" | hasClaim "Alcohol Free" | Klaim, bukan bahan. |
| 41 | A5 | ex:WAR-0004 | hasIngredient "Tanpa Pewangi" | hasClaim "Tanpa Pewangi" | Klaim, bukan bahan. |
| 42 | A5 | ex:WAR-0006 | hasIngredient "Tanpa Paraben" | hasClaim "Tanpa Paraben" | Klaim, bukan bahan. |
| 43 | A5 | ex:WAR-0007 | hasIngredient "Alcohol Free" | hasClaim "Alcohol Free" | Klaim, bukan bahan. |
| 44 | A5 | ex:WAR-0007 | hasIngredient "Organik" | hasClaim "Organik" | Klaim, bukan bahan. |
| 45 | A5 | ex:WAR-0007 | hasIngredient "Tanpa Pewangi" | hasClaim "Tanpa Pewangi" | Klaim, bukan bahan. |
| 46 | A5 | ex:WAR-0008 | hasIngredient "Alcohol Free" | hasClaim "Alcohol Free" | Klaim, bukan bahan. |
| 47 | A5 | ex:WAR-0008 | hasIngredient "Organik" | hasClaim "Organik" | Klaim, bukan bahan. |
| 48 | A5 | ex:WAR-0008 | hasIngredient "Tanpa Pewangi" | hasClaim "Tanpa Pewangi" | Klaim, bukan bahan. |
| 49 | A5 | ex:LAB-0054 | hasIngredient "Free From" | hasClaim "Free From" | Klaim, bukan bahan. |
| 50 | A5 | ex:CRY-0010 | hasIngredient "dan SPF 35 PA+++" | hasClaim "SPF 35 PA+++" | Klaim, bukan bahan. |
| 51 | A5 | ex:EMI-0035 | hasIngredient "cek INCI resmi" | verificationNote "cek INCI resmi" | Catatan verifikasi, bukan bahan. |
| 52 | A5 | ex:EMI-0038 | hasIngredient "cek INCI" | verificationNote "cek INCI" | Catatan verifikasi, bukan bahan. |
| 53 | A5 | ex:CRY-0002 | hasIngredient "Perlu verifikasi dari label resmi" | verificationNote "Perlu verifikasi dari label resmi" | Catatan verifikasi, bukan bahan. |
| 54 | A5 | ex:CRY-0003 | hasIngredient "Ferulic Acid (perlu konfirmasi label)" | hasIngredient "Ferulic Acid" + verificationNote "Ferulic Acid: perlu konfirmasi label" | Catatan verifikasi, bukan bahan. |
| 55 | A2 | ex:CRY-0001 | - | verificationNote "A2: jenis kulit belum diverifikasi (sumber: 'Perlu verifikasi')." | Tidak diubah (nilai benar tidak ada di sumber). |
| 56 | A2 | ex:CRY-0002 | - | verificationNote "A2: jenis kulit belum diverifikasi (sumber: 'Perlu verifikasi')." | Tidak diubah (nilai benar tidak ada di sumber). |
| 57 | A6 | ex:KAH-0047 | - | verificationNote "A6: kelompok umur di sumber tertulis '19' (ambigu); perlu verifikasi." | Tidak diubah (nilai benar tidak ada di sumber). |
| 58 | A6 | ex:LAB-0060 | - | verificationNote "A6: kelompok umur di sumber 'Not Specified'; perlu verifikasi." | Tidak diubah (nilai benar tidak ada di sumber). |
| 59 | A7 | ex:LAB-0069 | - | verificationNote "A7: mengandung 'Micro Encapsulation Retinol' tetapi ditandai Teen; batas usia perlu verifikasi resmi." | Tidak diubah (nilai benar tidak ada di sumber). |
| 60 | A8 | ex:LAB-0062 | - | verificationNote "A8: komposisi memuat 'acnepro biome™ technology' (teknologi seri AcnePro); perlu dicek ulang di situs LABORE." | Tidak diubah (nilai benar tidak ada di sumber). |
| 61 | A10 | ex:LAB-0062 | - | verificationNote "A10: nama seri di sumber 'Bightening' kemungkinan typo; dipertahankan sesuai sumber." | Tidak diubah (nilai benar tidak ada di sumber). |
| 62 | A5 | ex:LAB-0054 | - | verificationNote "A5: klaim 'Free From' di sumber tidak lengkap (bebas dari apa belum disebut)." | Tidak diubah (nilai benar tidak ada di sumber). |
| 63 | A4 | ex:LAB-0053 | - | verificationNote "A4: bahan 'accharide Isomerate' kemungkinan terpotong; nama lengkap tidak ada di sumber." | Tidak diubah (nilai benar tidak ada di sumber). |
| 64 | A4 | ex:CRY-0010 | - | verificationNote "A4: bahan 'Crystals' kemungkinan terpotong (sumber: ' Crystals, Amino Ectoin, ...'); perlu verifikasi." | Tidak diubah (nilai benar tidak ada di sumber). |
| 65 | A4 | ex:CRY-0006 | - | verificationNote "A4: nilai bahan 'kombinasi dari peptida dengan kristal emas murni (pure gold crystals) dan Youth Glow Active' adalah kalimat deskripsi, belum dipecah." | Tidak diubah (nilai benar tidak ada di sumber). |
| 66 | A4 | ex:LAB-0067 | - | verificationNote "A4: nilai bahan 'mineral filters like Zinc Oxide (nano) and Titanium Dioxide' dan 'combined with active brighteners such as Niacinamide' adalah potongan kalimat, belum dipecah." | Tidak diubah (nilai benar tidak ada di sumber). |
| 67 | A9 | ex:LAB-0068 | hasCategory ex:FacemistCategory | hasCategory ex:FaceMistCategory | Excel 'Face mist' = 'Face Mist' (hanya beda kapital). |
| 68 | A9 | ex:WAR-0022 | hasCategory ex:FacemistCategory | hasCategory ex:FaceMistCategory | Excel 'Face mist' = 'Face Mist' (hanya beda kapital). |
| 69 | A9 | ex:FacemistCategory | a ex:Category | (dihapus) | Tidak ada referensi tersisa. |
| 70 | A10 | ex:CrystallureBrand | (tanpa label) | rdfs:label "Crystallure"@id | Dari kolom Excel. |
| 71 | A10 | ex:EminaBrand | (tanpa label) | rdfs:label "Emina"@id | Dari kolom Excel. |
| 72 | A10 | ex:KahfBrand | (tanpa label) | rdfs:label "Kahf"@id | Dari kolom Excel. |
| 73 | A10 | ex:LABOREBrand | (tanpa label) | rdfs:label "LABORE"@id | Dari kolom Excel. |
| 74 | A10 | ex:TAVIBrand | (tanpa label) | rdfs:label "TAVI"@id | Dari kolom Excel. |
| 75 | A10 | ex:WardahBrand | (tanpa label) | rdfs:label "Wardah"@id | Dari kolom Excel. |
| 76 | A10 | ex:AcneProSeries | (tanpa label) | rdfs:label "AcnePro"@id | Dari kolom Excel. |
| 77 | A10 | ex:Altero6MWHyaluSeries | (tanpa label) | rdfs:label "Altero6MWHyalu"@id | Dari kolom Excel. |
| 78 | A10 | ex:BighteningSeries | (tanpa label) | rdfs:label "Bightening"@id | Dari kolom Excel. |
| 79 | A10 | ex:BiomeProtectSeries | (tanpa label) | rdfs:label "BiomeProtect"@id | Dari kolom Excel. |
| 80 | A10 | ex:BiomeRepairSeries | (tanpa label) | rdfs:label "BiomeRepair"@id | Dari kolom Excel. |
| 81 | A10 | ex:BrightBlemishSolutionSeries | (tanpa label) | rdfs:label "Bright & Blemish Solution"@id | Dari kolom Excel. |
| 82 | A10 | ex:BrightStuffAcneProneSeries | (tanpa label) | rdfs:label "Bright Stuff Acne Prone"@id | Dari kolom Excel. |
| 83 | A10 | ex:BrightStuffSeries | (tanpa label) | rdfs:label "Bright Stuff"@id | Dari kolom Excel. |
| 84 | A10 | ex:CDefenseSeries | (tanpa label) | rdfs:label "C-Defense"@id | Dari kolom Excel. |
| 85 | A10 | ex:CrystalSecretSeries | (tanpa label) | rdfs:label "Crystal Secret"@id | Dari kolom Excel. |
| 86 | A10 | ex:DayNightSeries | (tanpa label) | rdfs:label "Day & Night"@id | Dari kolom Excel. |
| 87 | A10 | ex:DermalabRevolutionaireSeries | (tanpa label) | rdfs:label "Dermalab Revolutionaire"@id | Dari kolom Excel. |
| 88 | A10 | ex:DoubleTheMoistSeries | (tanpa label) | rdfs:label "Double The Moist"@id | Dari kolom Excel. |
| 89 | A10 | ex:FaceCareSeries | (tanpa label) | rdfs:label "Face Care"@id | Dari kolom Excel. |
| 90 | A10 | ex:FirstCleanserSeries | (tanpa label) | rdfs:label "First Cleanser"@id | Dari kolom Excel. |
| 91 | A10 | ex:GentleBiomeSeries | (tanpa label) | rdfs:label "GentleBiome"@id | Dari kolom Excel. |
| 92 | A10 | ex:HydraRoseSeries | (tanpa label) | rdfs:label "Hydra Rose"@id | Dari kolom Excel. |
| 93 | A10 | ex:LightSeries | (tanpa label) | rdfs:label "Light+"@id | Dari kolom Excel. |
| 94 | A10 | ex:MoistureBoostSeries | (tanpa label) | rdfs:label "Moisture Boost"@id | Dari kolom Excel. |
| 95 | A10 | ex:MoistureRichSeries | (tanpa label) | rdfs:label "Moisture Rich"@id | Dari kolom Excel. |
| 96 | A10 | ex:NatureDailySeries | (tanpa label) | rdfs:label "Nature Daily"@id | Dari kolom Excel. |
| 97 | A10 | ex:RESQ10Series | (tanpa label) | rdfs:label "RES-Q10"@id | Dari kolom Excel. |
| 98 | A10 | ex:RadiantChargeSeries | (tanpa label) | rdfs:label "Radiant Charge"@id | Dari kolom Excel. |
| 99 | A10 | ex:RenewYouSeries | (tanpa label) | rdfs:label "Renew You"@id | Dari kolom Excel. |
| 100 | A10 | ex:RetextureSeries | (tanpa label) | rdfs:label "Retexture"@id | Dari kolom Excel. |
| 101 | A10 | ex:SerumActiveCareSeries | (tanpa label) | rdfs:label "Serum/Active Care"@id | Dari kolom Excel. |
| 102 | A10 | ex:SlowAgingSignsSeries | (tanpa label) | rdfs:label "Slow Aging Signs"@id | Dari kolom Excel. |
| 103 | A10 | ex:SunBattleSeries | (tanpa label) | rdfs:label "Sun Battle"@id | Dari kolom Excel. |
| 104 | A10 | ex:SupremeSeries | (tanpa label) | rdfs:label "Supreme"@id | Dari kolom Excel. |
| 105 | A10 | ex:UVShieldSeries | (tanpa label) | rdfs:label "UV Shield"@id | Dari kolom Excel. |
| 106 | A10 | ex:UrbanShieldSeries | (tanpa label) | rdfs:label "Urban Shield"@id | Dari kolom Excel. |
| 107 | A10 | ex:WaterBrightSeries | (tanpa label) | rdfs:label "Water Bright"@id | Dari kolom Excel. |
| 108 | A10 | ex:WhiteSecretSeries | (tanpa label) | rdfs:label "White Secret"@id | Dari kolom Excel. |
| 109 | A10 | ex:CleanserCategory | (tanpa label) | rdfs:label "Cleanser"@id | Dari kolom Excel. |
| 110 | A10 | ex:DaymoisturizerSPFCategory | (tanpa label) | rdfs:label "Day moisturizer SPF"@id | Dari kolom Excel. |
| 111 | A10 | ex:EssenceCategory | (tanpa label) | rdfs:label "Essence"@id | Dari kolom Excel. |
| 112 | A10 | ex:EssencetonerCategory | (tanpa label) | rdfs:label "Essence toner"@id | Dari kolom Excel. |
| 113 | A10 | ex:ExfoliatingTonerCategory | (tanpa label) | rdfs:label "Exfoliating Toner"@id | Dari kolom Excel. |
| 114 | A10 | ex:EyeCareCategory | (tanpa label) | rdfs:label "Eye Care"@id | Dari kolom Excel. |
| 115 | A10 | ex:EyecreamCategory | (tanpa label) | rdfs:label "Eye cream"@id | Dari kolom Excel. |
| 116 | A10 | ex:FaceMistCategory | (tanpa label) | rdfs:label "Face Mist"@id | Dari kolom Excel. |
| 117 | A10 | ex:FacespraytonerCategory | (tanpa label) | rdfs:label "Face spray/toner"@id | Dari kolom Excel. |
| 118 | A10 | ex:LotionCategory | (tanpa label) | rdfs:label "Lotion"@id | Dari kolom Excel. |
| 119 | A10 | ex:MicellarCleanserCategory | (tanpa label) | rdfs:label "Micellar Cleanser"@id | Dari kolom Excel. |
| 120 | A10 | ex:MicellarwaterCategory | (tanpa label) | rdfs:label "Micellar water"@id | Dari kolom Excel. |
| 121 | A10 | ex:MoisturizerCategory | (tanpa label) | rdfs:label "Moisturizer"@id | Dari kolom Excel. |
| 122 | A10 | ex:NightCreamCategory | (tanpa label) | rdfs:label "Night Cream"@id | Dari kolom Excel. |
| 123 | A10 | ex:NightmoisturizerCategory | (tanpa label) | rdfs:label "Night moisturizer"@id | Dari kolom Excel. |
| 124 | A10 | ex:OilSerumCategory | (tanpa label) | rdfs:label "Oil Serum"@id | Dari kolom Excel. |
| 125 | A10 | ex:SerumCategory | (tanpa label) | rdfs:label "Serum"@id | Dari kolom Excel. |
| 126 | A10 | ex:SpotTreatmentCategory | (tanpa label) | rdfs:label "Spot Treatment"@id | Dari kolom Excel. |
| 127 | A10 | ex:SunscreenCategory | (tanpa label) | rdfs:label "Sunscreen"@id | Dari kolom Excel. |
| 128 | A10 | ex:SunscreenmoisturizerCategory | (tanpa label) | rdfs:label "Sunscreen moisturizer"@id | Dari kolom Excel. |
| 129 | A10 | ex:SunscreentintedCategory | (tanpa label) | rdfs:label "Sunscreen tinted"@id | Dari kolom Excel. |
| 130 | A10 | ex:TonerCategory | (tanpa label) | rdfs:label "Toner"@id | Dari kolom Excel. |
| 131 | A10 | ex:Teen | (tanpa label) | "Teen"@en, "Remaja"@id | Disetujui (dua bahasa). |
| 132 | A10 | ex:Adult | (tanpa label) | "Adult"@en, "Dewasa"@id | Disetujui (dua bahasa). |
| 133 | A10 | ex:BighteningSeries | - | rdfs:comment (kemungkinan typo) | URI dan label dipertahankan sesuai sumber. |
