#!/usr/bin/env python3
"""
Pembersihan data ontologi Aphrodite: v1 -> v1.5.

- Membaca  : ontology/skincare_ontology.ttl  (TIDAK diubah; dicek dengan MD5)
- Menulis  : ontology/aphrodite-v1.5.ttl
- Changelog: reports/v15_changelog.md

Prinsip:
  * Hanya perubahan yang sudah disetujui (daftar eksplisit di bagian 2).
  * Nama properti lama TIDAK diganti / dihapus.
  * Nilai yang tidak terbukti dari sumber TIDAK ditebak; hanya diberi ex:verificationNote.
  * Setiap perubahan punya "prasyarat": jika nilai lama tidak ditemukan persis,
    skrip BERHENTI (tidak menulis apa pun) supaya tidak ada perubahan diam-diam.
  * Setelah selesai, skrip memeriksa bahwa tidak ada nilai bahan/klaim/catatan yang hilang.

Cara pakai (dari root project):
  python3 scripts/clean_v15.py
"""

import argparse
import hashlib
import sys
from collections import Counter
from pathlib import Path

from rdflib import Graph, Literal, Namespace, OWL, RDF, RDFS, URIRef, XSD

PROJECT_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_INPUT = PROJECT_ROOT / "ontology" / "skincare_ontology.ttl"
DEFAULT_OUTPUT = PROJECT_ROOT / "ontology" / "aphrodite-v1.5.ttl"
DEFAULT_LOG = PROJECT_ROOT / "reports" / "v15_changelog.md"

EX = Namespace("https://example.org/skincare-ontology#")
DCTERMS = Namespace("http://purl.org/dc/terms/")

# Penghapusan sisa referensi sel Excel (+G25, +G42). Excel asli juga memuat teks ini
# (bukan rumus); kolom G = Ingredients, jadi '+G25' dianggap referensi sel, bukan bahan.
# Nilai asli tetap disimpan di verificationNote. Set False untuk menonaktifkan.
APPLY_G_REF_CLEANUP = True

# ---------------------------------------------------------------------------
# 1. DATA PERUBAHAN (semua berasal dari audit + Excel sumber, sudah disetujui)
# ---------------------------------------------------------------------------

# A3: ID baru LABORE sesuai urutan baris Excel (baris 54–71).
LAB_RENAME = [
    ("LAB-0053", "LAB-0053-GentleBiomeMildCleanser"),
    ("LAB-0054", "LAB-0053-AcneProBiomeSebumAcneCleansingMouss"),
    ("LAB-0055", "LAB-0053-GentleBiomeHydrationToner"),
    ("LAB-0056", "LAB-0053-AcneProBiomeMicroExfoliatingGentleT"),
    ("LAB-0057", "LAB-0053-GentleBiomeSkinNutritionGel"),
    ("LAB-0058", "LAB-0053-BiomeRepairBarrierReviveCream"),
    ("LAB-0059", "LAB-0053-BiomeRepairTopiCalmCream"),
    ("LAB-0060", "LAB-0053-BarrierRestorativeAdvancedLotion"),
    ("LAB-0061", "LAB-0053-GentleBiomeBarrierRepairSerum"),
    ("LAB-0062", "LAB-0053-MultiPigmentationBrighteningSerum"),
    ("LAB-0063", "LAB-0053-IntensiveAcneSerumLactoTargetedSAL"),
    ("LAB-0064", "LAB-0053-HydratingPlumpingConcentratedSerum"),
    ("LAB-0065", "LAB-0053-BiomeProtectPhysicalSunscreenSPF50P"),
    ("LAB-0066", "LAB-0053-AcneOilCorrectPhysicalSunscreenSPF5"),
    ("LAB-0067", "LAB-0053-AntiPigmentationBrighteningPhysical"),
    ("LAB-0068", "LAB-0053-GentleBiomeThermalSoothingMist"),
    ("LAB-0069", "LAB-0053-AcneProBiomeAcneSpotGel"),
    ("LAB-0070", "LAB-0053-GentleBiomeHydratingFaceBodyCleanse"),
]

# A1 / A2: jenis kulit. Nilai salah diganti penanda yang sudah ada di dataset ("PerluVerifikasi").
SKIN_A1 = {
    "CRY-0003": ["3% Encapsulated Ferulic Acid", "Gold Peptide Crystals", "Meadowfoam Lipidic Essence", "PHA"],
    "CRY-0004": ["Gold Peptide Crystals", "YouthGlow Active", "dan Triple Hyaluronic Acid"],
}
SKIN_A2 = ["CRY-0001", "CRY-0002"]
PLACEHOLDER_SKIN = "PerluVerifikasi"

# A4: perbaikan bahan. (produk, [nilai lama], [nilai baru], alasan, simpan_nilai_asli_di_note)
# Produk LAB memakai ID baru (rename dilakukan lebih dulu).
INGREDIENT_FIXES = [
    ("LAB-0056", ["1", "2-hexanediol"], ["1,2-hexanediol"],
     "Satu bahan '1,2-hexanediol' (sesuai Excel) terpecah oleh split koma.", False),
    ("TAV-0006", ["Superberries (Goji", "Acai", "dan Japanese Beauty Berry) + Amino Acid Complex"],
     ["Superberries (Goji, Acai, dan Japanese Beauty Berry) + Amino Acid Complex"],
     "Digabung kembali persis seperti sel Excel; pecah karena split koma di dalam kurung.", False),
    ("CRY-0004", ["dan MatrixKey Molecule"], ["MatrixKey Molecule"],
     "Kata penghubung daftar 'dan' dibuang (Excel: '..., YouthGlow Active, dan MatrixKey Molecule').", False),
    ("CRY-0005", ["dan MatrixKey Molecule"], ["MatrixKey Molecule"],
     "Kata penghubung daftar 'dan' dibuang.", False),
    ("CRY-0009", ["dan Advanced Micro Peptide"], ["Advanced Micro Peptide"],
     "Kata penghubung daftar 'dan' dibuang.", False),
    ("LAB-0064", ["and ferment lysates"], ["ferment lysates"],
     "Kata penghubung daftar 'and' dibuang (Excel: '..., Niacinamide, and ferment lysates').", False),
    ("LAB-0067", ["and Glycyrrhiza Glabra (Licorice) Root Extract"], ["Glycyrrhiza Glabra (Licorice) Root Extract"],
     "Kata penghubung daftar 'and' dibuang.", False),
    ("CRY-0001", ["Gold-Peptide Crystals dan Amino Ectoin"], ["Gold-Peptide Crystals", "Amino Ectoin"],
     "Dua bahan dalam satu nilai; keduanya tercatat sebagai bahan tersendiri di produk Crystallure lain "
     "(Gold Peptide Crystals: CRY-0004/5/9; Amino Ectoin: CRY-0010).", False),
    ("CRY-0007", ["Gold Peptide Crystale dan YouthGlow Active"], ["Gold Peptide Crystals", "YouthGlow Active"],
     "Dua bahan dalam satu nilai + typo 'Crystale'. 'Gold Peptide Crystals' dan 'YouthGlow Active' "
     "tercatat terpisah di CRY-0004/CRY-0005.", True),
    ("CRY-0008", ["youthglow active dan tujuh minyak tanaman"], ["youthglow active", "tujuh minyak tanaman"],
     "Pola daftar Excel 'gold peptide crystals, youthglow active dan tujuh minyak tanaman': "
     "'dan' memisahkan dua komponen; ejaan asli dipertahankan.", False),
]
if APPLY_G_REF_CLEANUP:
    INGREDIENT_FIXES += [
        ("WAR-0012", ["UV filters+G25"], ["UV filters"],
         "'+G25' = referensi sel Excel (kolom G = Ingredients), bukan bahan; 'UV filters' ada di produk lain.", True),
        ("EMI-0040", ["summer plum extract+G42"], ["summer plum extract"],
         "'+G42' = referensi sel Excel (kolom G = Ingredients), bukan bahan; 'summer plum extract' ada di produk lain.", True),
    ]

# A5: klaim dipindah ke ex:hasClaim; catatan dipindah ke ex:verificationNote.
#     (produk, nilai bahan lama, nilai klaim baru)
CLAIMS = [
    ("WAR-0003", "Alcohol Free", "Alcohol Free"), ("WAR-0003", "Tanpa Pewangi", "Tanpa Pewangi"),
    ("WAR-0004", "Alcohol Free", "Alcohol Free"), ("WAR-0004", "Tanpa Pewangi", "Tanpa Pewangi"),
    ("WAR-0006", "Tanpa Paraben", "Tanpa Paraben"),
    ("WAR-0007", "Alcohol Free", "Alcohol Free"), ("WAR-0007", "Organik", "Organik"),
    ("WAR-0007", "Tanpa Pewangi", "Tanpa Pewangi"),
    ("WAR-0008", "Alcohol Free", "Alcohol Free"), ("WAR-0008", "Organik", "Organik"),
    ("WAR-0008", "Tanpa Pewangi", "Tanpa Pewangi"),
    ("LAB-0054", "Free From", "Free From"),
    ("CRY-0010", "dan SPF 35 PA+++", "SPF 35 PA+++"),
]
#     (produk, nilai bahan lama, nilai bahan pengganti atau None, isi catatan)
NOTES_FROM_INGREDIENT = [
    ("EMI-0035", "cek INCI resmi", None, "cek INCI resmi"),
    ("EMI-0038", "cek INCI", None, "cek INCI"),
    ("CRY-0002", "Perlu verifikasi dari label resmi", None, "Perlu verifikasi dari label resmi"),
    ("CRY-0003", "Ferulic Acid (perlu konfirmasi label)", "Ferulic Acid", "Ferulic Acid: perlu konfirmasi label"),
]

# Catatan untuk temuan yang TIDAK diubah (nilai benar tidak ada di sumber).
EXTRA_NOTES = [
    ("CRY-0001", "A2: jenis kulit belum diverifikasi (sumber: 'Perlu verifikasi')."),
    ("CRY-0002", "A2: jenis kulit belum diverifikasi (sumber: 'Perlu verifikasi')."),
    ("KAH-0047", "A6: kelompok umur di sumber tertulis '19' (ambigu); perlu verifikasi."),
    ("LAB-0060", "A6: kelompok umur di sumber 'Not Specified'; perlu verifikasi."),
    ("LAB-0069", "A7: mengandung 'Micro Encapsulation Retinol' tetapi ditandai Teen; batas usia perlu verifikasi resmi."),
    ("LAB-0062", "A8: komposisi memuat 'acnepro biome™ technology' (teknologi seri AcnePro); perlu dicek ulang di situs LABORE."),
    ("LAB-0062", "A10: nama seri di sumber 'Bightening' kemungkinan typo; dipertahankan sesuai sumber."),
    ("LAB-0054", "A5: klaim 'Free From' di sumber tidak lengkap (bebas dari apa belum disebut)."),
    ("LAB-0053", "A4: bahan 'accharide Isomerate' kemungkinan terpotong; nama lengkap tidak ada di sumber."),
    ("CRY-0010", "A4: bahan 'Crystals' kemungkinan terpotong (sumber: ' Crystals, Amino Ectoin, ...'); perlu verifikasi."),
    ("CRY-0006", "A4: nilai bahan 'kombinasi dari peptida dengan kristal emas murni (pure gold crystals) dan Youth Glow Active' "
                 "adalah kalimat deskripsi, belum dipecah."),
    ("LAB-0067", "A4: nilai bahan 'mineral filters like Zinc Oxide (nano) and Titanium Dioxide' dan "
                 "'combined with active brighteners such as Niacinamide' adalah potongan kalimat, belum dipecah."),
]

# A9: kategori identik (Excel: 'Face mist' vs 'Face Mist').
CATEGORY_MERGE = ("FacemistCategory", "FaceMistCategory")

# A10: label dari kolom Excel (Brand / Product_series / Category), persis seperti sumber.
BRAND_LABELS = {
    "CrystallureBrand": "Crystallure", "EminaBrand": "Emina", "KahfBrand": "Kahf",
    "LABOREBrand": "LABORE", "TAVIBrand": "TAVI", "WardahBrand": "Wardah",
}
SERIES_LABELS = {
    "AcneProSeries": "AcnePro", "Altero6MWHyaluSeries": "Altero6MWHyalu", "BighteningSeries": "Bightening",
    "BiomeProtectSeries": "BiomeProtect", "BiomeRepairSeries": "BiomeRepair",
    "BrightBlemishSolutionSeries": "Bright & Blemish Solution", "BrightStuffAcneProneSeries": "Bright Stuff Acne Prone",
    "BrightStuffSeries": "Bright Stuff", "CDefenseSeries": "C-Defense", "CrystalSecretSeries": "Crystal Secret",
    "DayNightSeries": "Day & Night", "DermalabRevolutionaireSeries": "Dermalab Revolutionaire",
    "DoubleTheMoistSeries": "Double The Moist", "FaceCareSeries": "Face Care", "FirstCleanserSeries": "First Cleanser",
    "GentleBiomeSeries": "GentleBiome", "HydraRoseSeries": "Hydra Rose", "LightSeries": "Light+",
    "MoistureBoostSeries": "Moisture Boost", "MoistureRichSeries": "Moisture Rich", "NatureDailySeries": "Nature Daily",
    "RESQ10Series": "RES-Q10", "RadiantChargeSeries": "Radiant Charge", "RenewYouSeries": "Renew You",
    "RetextureSeries": "Retexture", "SerumActiveCareSeries": "Serum/Active Care",
    "SlowAgingSignsSeries": "Slow Aging Signs", "SunBattleSeries": "Sun Battle", "SupremeSeries": "Supreme",
    "UVShieldSeries": "UV Shield", "UrbanShieldSeries": "Urban Shield", "WaterBrightSeries": "Water Bright",
    "WhiteSecretSeries": "White Secret",
}
CATEGORY_LABELS = {
    "CleanserCategory": "Cleanser", "DaymoisturizerSPFCategory": "Day moisturizer SPF", "EssenceCategory": "Essence",
    "EssencetonerCategory": "Essence toner", "ExfoliatingTonerCategory": "Exfoliating Toner",
    "EyeCareCategory": "Eye Care", "EyecreamCategory": "Eye cream", "FaceMistCategory": "Face Mist",
    "FacespraytonerCategory": "Face spray/toner", "LotionCategory": "Lotion",
    "MicellarCleanserCategory": "Micellar Cleanser", "MicellarwaterCategory": "Micellar water",
    "MoisturizerCategory": "Moisturizer", "NightCreamCategory": "Night Cream",
    "NightmoisturizerCategory": "Night moisturizer", "OilSerumCategory": "Oil Serum", "SerumCategory": "Serum",
    "SpotTreatmentCategory": "Spot Treatment", "SunscreenCategory": "Sunscreen",
    "SunscreenmoisturizerCategory": "Sunscreen moisturizer", "SunscreentintedCategory": "Sunscreen tinted",
    "TonerCategory": "Toner",
}
AGE_LABELS = {"Teen": [("Teen", "en"), ("Remaja", "id")], "Adult": [("Adult", "en"), ("Dewasa", "id")]}
SERIES_COMMENTS = {
    "BighteningSeries": "Nama seri sesuai sumber ('Bightening'); kemungkinan typo dari 'Brightening', belum diverifikasi.",
}


# ---------------------------------------------------------------------------
# 2. ALAT BANTU
# ---------------------------------------------------------------------------
class CleanError(Exception):
    pass


class Cleaner:
    def __init__(self, g):
        self.g = g
        self.log = []          # (kode, produk/resource, sebelum, sesudah, alasan)

    def record(self, code, target, before, after, reason):
        self.log.append((code, target, before, after, reason))

    def lits(self, s, p):
        return sorted(str(o) for o in self.g.objects(s, p))

    def require(self, s, p, value):
        if (s, p, Literal(value)) not in self.g:
            raise CleanError(f"Prasyarat gagal: {s.n3(self.g.namespace_manager)} {p.n3(self.g.namespace_manager)} "
                             f"\"{value}\" tidak ditemukan. Data sumber berbeda dari yang diaudit.")

    def note(self, s, text):
        self.g.add((s, EX.verificationNote, Literal(text)))


def md5_of(path):
    return hashlib.md5(Path(path).read_bytes()).hexdigest()


# ---------------------------------------------------------------------------
# 3. LANGKAH PEMBERSIHAN
# ---------------------------------------------------------------------------
def add_schema(c):
    g = c.g
    for prop, label, comment in [
        (EX.hasClaim, "memiliki klaim",
         "Klaim pemasaran produk (mis. 'Alcohol Free', 'Tanpa Pewangi'), dipisahkan dari daftar bahan."),
        (EX.verificationNote, "catatan verifikasi",
         "Catatan verifikasi / temuan audit data. Boleh lebih dari satu nilai per produk."),
    ]:
        if (prop, RDF.type, None) in g:
            raise CleanError(f"Properti {prop} sudah ada di sumber; batalkan agar tidak bentrok.")
        g.add((prop, RDF.type, OWL.DatatypeProperty))
        g.add((prop, RDFS.domain, EX.Product))
        g.add((prop, RDFS.range, XSD.string))
        g.add((prop, RDFS.label, Literal(label, lang="id")))
        g.add((prop, RDFS.comment, Literal(comment, lang="id")))
        c.record("Skema", prop.n3(g.namespace_manager), "(tidak ada)", "owl:DatatypeProperty", comment)
    onto = EX.SkincareOntology
    g.add((onto, OWL.versionInfo, Literal("1.5")))
    g.add((onto, RDFS.comment, Literal(
        "Versi 1.5: hasil pembersihan data A1–A10 dari skincare_ontology.ttl oleh scripts/clean_v15.py. "
        "Nama properti lama tidak diubah.", lang="id")))
    c.record("Skema", "ex:SkincareOntology", "-", 'owl:versionInfo "1.5"', "Penanda versi.")


def rename_lab(c):
    g = c.g
    for new_id, old_local in LAB_RENAME:
        old, new = EX[old_local], EX[new_id]
        if (old, RDF.type, EX.Product) not in g:
            raise CleanError(f"Produk ex:{old_local} tidak ditemukan.")
        if (new, None, None) in g:
            raise CleanError(f"URI tujuan ex:{new_id} sudah dipakai.")
        c.require(old, EX.sourceProductId, "LAB-0053")
        for p, o in list(g.predicate_objects(old)):
            g.remove((old, p, o))
            g.add((new, p, o))
        for s, p in list(g.subject_predicates(old)):   # referensi sebagai objek (saat ini tidak ada)
            g.remove((s, p, old))
            g.add((s, p, new))
        g.set((new, EX.sourceProductId, Literal(new_id)))
        c.record("A3", f"ex:{new_id}", f'ex:{old_local} / sourceProductId "LAB-0053"',
                 f'ex:{new_id} / sourceProductId "{new_id}"', "ID unik sesuai urutan baris Excel; URI = ID.")


def fix_skin(c):
    g = c.g
    for pid, wrong in SKIN_A1.items():
        s = EX[pid]
        for v in wrong:
            c.require(s, EX.suitableForSkinType, v)
        if set(c.lits(s, EX.suitableForSkinType)) != set(wrong):
            raise CleanError(f"{pid}: jenis kulit tidak sama dengan hasil audit.")
        for v in wrong:
            g.remove((s, EX.suitableForSkinType, Literal(v)))
        g.add((s, EX.suitableForSkinType, Literal(PLACEHOLDER_SKIN)))
        c.note(s, "A1: kolom jenis kulit di sumber berisi nama bahan (" + ", ".join(wrong)
               + "); jenis kulit perlu diverifikasi dari situs resmi.")
        c.record("A1", f"ex:{pid}", "; ".join(wrong), PLACEHOLDER_SKIN + " + verificationNote",
                 "Nilai bukan jenis kulit; tidak ditebak, nilai asli disimpan di catatan.")
    for pid in SKIN_A2:
        c.require(EX[pid], EX.suitableForSkinType, PLACEHOLDER_SKIN)
        c.record("A2", f"ex:{pid}", PLACEHOLDER_SKIN, PLACEHOLDER_SKIN + " (tetap) + verificationNote",
                 "Belum ada sumber resmi; hanya diberi catatan.")


def fix_ingredients(c):
    g = c.g
    for pid, olds, news, reason, keep_original in INGREDIENT_FIXES:
        s = EX[pid]
        for v in olds:
            c.require(s, EX.hasIngredient, v)
        for v in olds:
            g.remove((s, EX.hasIngredient, Literal(v)))
        for v in news:
            g.add((s, EX.hasIngredient, Literal(v)))
        if keep_original:
            c.note(s, "A4: nilai bahan asli di sumber " + " | ".join(f"'{v}'" for v in olds)
                   + " dinormalisasi menjadi " + " | ".join(f"'{v}'" for v in news) + ".")
        c.record("A4", f"ex:{pid}", " | ".join(olds), " | ".join(news), reason)


def move_claims_notes(c):
    g = c.g
    for pid, old, claim in CLAIMS:
        s = EX[pid]
        c.require(s, EX.hasIngredient, old)
        g.remove((s, EX.hasIngredient, Literal(old)))
        g.add((s, EX.hasClaim, Literal(claim)))
        c.record("A5", f"ex:{pid}", f"hasIngredient \"{old}\"", f"hasClaim \"{claim}\"", "Klaim, bukan bahan.")
    for pid, old, replacement, note in NOTES_FROM_INGREDIENT:
        s = EX[pid]
        c.require(s, EX.hasIngredient, old)
        g.remove((s, EX.hasIngredient, Literal(old)))
        after = f"verificationNote \"{note}\""
        if replacement:
            g.add((s, EX.hasIngredient, Literal(replacement)))
            after = f"hasIngredient \"{replacement}\" + " + after
        c.note(s, note)
        c.record("A5", f"ex:{pid}", f"hasIngredient \"{old}\"", after, "Catatan verifikasi, bukan bahan.")


def add_extra_notes(c):
    for pid, text in EXTRA_NOTES:
        s = EX[pid]
        if (s, RDF.type, EX.Product) not in c.g:
            raise CleanError(f"Produk ex:{pid} tidak ditemukan untuk catatan.")
        c.note(s, text)
        code = text.split(":")[0]
        c.record(code, f"ex:{pid}", "-", f"verificationNote \"{text}\"", "Tidak diubah (nilai benar tidak ada di sumber).")


def merge_category(c):
    g = c.g
    old, new = EX[CATEGORY_MERGE[0]], EX[CATEGORY_MERGE[1]]
    users = sorted(g.subjects(EX.hasCategory, old), key=str)
    for s in users:
        g.remove((s, EX.hasCategory, old))
        g.add((s, EX.hasCategory, new))
        c.record("A9", s.n3(g.namespace_manager), f"hasCategory ex:{CATEGORY_MERGE[0]}",
                 f"hasCategory ex:{CATEGORY_MERGE[1]}", "Excel 'Face mist' = 'Face Mist' (hanya beda kapital).")
    # Hapus deklarasi lama HANYA jika tidak ada referensi tersisa.
    remaining = [t for t in g.triples((None, None, old))] + \
                [t for t in g.triples((old, None, None)) if t[1] != RDF.type]
    if remaining:
        raise CleanError(f"Masih ada referensi ke ex:{CATEGORY_MERGE[0]}: {remaining}")
    g.remove((old, RDF.type, EX.Category))
    if (old, None, None) in g or (None, None, old) in g:
        raise CleanError("Deklarasi lama belum bersih.")
    c.record("A9", f"ex:{CATEGORY_MERGE[0]}", "a ex:Category", "(dihapus)", "Tidak ada referensi tersisa.")


def add_labels(c):
    g = c.g
    for table, cls in [(BRAND_LABELS, EX.Brand), (SERIES_LABELS, EX.ProductSeries), (CATEGORY_LABELS, EX.Category)]:
        for loc, label in table.items():
            s = EX[loc]
            if (s, RDF.type, cls) not in g:
                raise CleanError(f"ex:{loc} bukan {cls}.")
            if list(g.objects(s, RDFS.label)):
                raise CleanError(f"ex:{loc} sudah punya label; tidak ditimpa.")
            g.add((s, RDFS.label, Literal(label, lang="id")))
            c.record("A10", f"ex:{loc}", "(tanpa label)", f'rdfs:label "{label}"@id', "Dari kolom Excel.")
    for loc, labels in AGE_LABELS.items():
        s = EX[loc]
        if (s, RDF.type, EX.AgeGroup) not in g:
            raise CleanError(f"ex:{loc} bukan AgeGroup.")
        for text, lang in labels:
            g.add((s, RDFS.label, Literal(text, lang=lang)))
        c.record("A10", f"ex:{loc}", "(tanpa label)",
                 ", ".join(f'"{t}"@{l}' for t, l in labels), "Disetujui (dua bahasa).")
    for loc, comment in SERIES_COMMENTS.items():
        g.add((EX[loc], RDFS.comment, Literal(comment, lang="id")))
        c.record("A10", f"ex:{loc}", "-", "rdfs:comment (kemungkinan typo)", "URI dan label dipertahankan sesuai sumber.")


# ---------------------------------------------------------------------------
# 4. PEMERIKSAAN SETELAH PEMBERSIHAN
# ---------------------------------------------------------------------------
def post_checks(src, out):
    """Pastikan tidak ada informasi yang hilang dan struktur tetap utuh."""
    problems = []
    old_to_new = {EX[old]: EX[new] for new, old in LAB_RENAME}
    mapped = lambda s: old_to_new.get(s, s)

    src_products = set(src.subjects(RDF.type, EX.Product))
    out_products = set(out.subjects(RDF.type, EX.Product))
    if {mapped(p) for p in src_products} != out_products:
        problems.append("Himpunan produk berubah.")

    # Setiap nilai bahan lama harus: masih bahan, ATAU tercatat di daftar perubahan yang disetujui.
    handled = {}
    for pid, olds, news, *_ in INGREDIENT_FIXES:
        for v in olds:
            handled[(pid, v)] = [("hasIngredient", n) for n in news]
    for pid, old, claim in CLAIMS:
        handled[(pid, old)] = [("hasClaim", claim)]
    for pid, old, repl, note in NOTES_FROM_INGREDIENT:
        handled[(pid, old)] = [("verificationNote", note)] + ([("hasIngredient", repl)] if repl else [])
    for p in src_products:
        np_ = mapped(p)
        pid = str(np_).split("#")[-1]
        out_ing = {str(o) for o in out.objects(np_, EX.hasIngredient)}
        for o in src.objects(p, EX.hasIngredient):
            v = str(o)
            if (pid, v) in handled:
                for prop, val in handled[(pid, v)]:
                    if (np_, EX[prop], Literal(val)) not in out:
                        problems.append(f"{pid}: '{v}' seharusnya menjadi {prop} '{val}', tidak ditemukan.")
            elif v not in out_ing:
                problems.append(f"{pid}: bahan '{v}' hilang tanpa tercatat.")
        # Properti lain (selain yang memang disentuh) harus identik.
        for prop in [EX.productName, EX.estimatedPrice, EX.hasBrand, EX.belongsToSeries,
                     EX.suitableForAgeGroup, EX.treatsConcern, EX.verificationStatus]:
            a = set(src.objects(p, prop))
            b = set(out.objects(np_, prop))
            if a != b:
                problems.append(f"{pid}: {prop} berubah ({a} -> {b}).")

    # Semua nama properti lama masih dideklarasikan.
    for prop in src.subjects(RDF.type, OWL.ObjectProperty):
        if (prop, RDF.type, OWL.ObjectProperty) not in out:
            problems.append(f"ObjectProperty {prop} hilang.")
    for prop in src.subjects(RDF.type, OWL.DatatypeProperty):
        if (prop, RDF.type, OWL.DatatypeProperty) not in out:
            problems.append(f"DatatypeProperty {prop} hilang.")

    ids = Counter(str(o) for o in out.objects(None, EX.sourceProductId))
    if any(n > 1 for n in ids.values()):
        problems.append("Masih ada sourceProductId dobel.")
    for p in out_products:
        if str(p).split("#")[-1] != str(out.value(p, EX.sourceProductId)):
            problems.append(f"URI {p} tidak sama dengan sourceProductId.")
    if (None, None, EX[CATEGORY_MERGE[0]]) in out or (EX[CATEGORY_MERGE[0]], None, None) in out:
        problems.append("Masih ada referensi FacemistCategory.")
    return problems


# ---------------------------------------------------------------------------
# 5. CHANGELOG
# ---------------------------------------------------------------------------
def write_changelog(path, c, meta):
    esc = lambda t: str(t).replace("|", "\\|").replace("\n", " ")
    lines = ["# Changelog Data v1.5", "",
             "> Dibuat otomatis oleh `scripts/clean_v15.py`.", "",
             f"- Sumber: `{meta['src']}` (MD5 `{meta['md5']}`, tidak diubah)",
             f"- Hasil: `{meta['out']}`",
             f"- Produk: {meta['p_before']} → {meta['p_after']}  ·  Triple: {meta['t_before']} → {meta['t_after']}",
             f"- Penghapusan +G (referensi sel Excel): {'aktif' if APPLY_G_REF_CLEANUP else 'nonaktif'}", "",
             "## Jumlah perubahan per kode", "", "| Kode | Jumlah |", "|---|---:|"]
    for code, n in sorted(Counter(r[0] for r in c.log).items(), key=lambda x: (len(x[0]), x[0])):
        lines.append(f"| {code} | {n} |")
    lines += ["", "## Detail", "", "| # | Kode | Target | Sebelum | Sesudah | Alasan |", "|---:|---|---|---|---|---|"]
    for i, (code, t, b, a, r) in enumerate(c.log, 1):
        lines.append(f"| {i} | {code} | {esc(t)} | {esc(b)} | {esc(a)} | {esc(r)} |")
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


# ---------------------------------------------------------------------------
# 6. PROGRAM UTAMA
# ---------------------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser(description="Bersihkan data v1 -> v1.5 (sumber tidak diubah).")
    ap.add_argument("--input", type=Path, default=DEFAULT_INPUT)
    ap.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    ap.add_argument("--changelog", type=Path, default=DEFAULT_LOG)
    args = ap.parse_args()
    src_path, out_path = args.input.resolve(), args.output.resolve()
    if src_path == out_path:
        sys.exit("Output tidak boleh sama dengan file sumber.")

    md5_before = md5_of(src_path)
    src = Graph()
    src.parse(src_path, format="turtle")
    work = Graph()                          # salinan kerja; graf sumber tidak disentuh
    for prefix, ns in src.namespaces():
        work.bind(prefix, ns, replace=True)
    work.bind("ex", EX, replace=True)
    for t in src:
        work.add(t)

    c = Cleaner(work)
    try:
        add_schema(c)
        rename_lab(c)
        fix_skin(c)
        fix_ingredients(c)
        move_claims_notes(c)
        add_extra_notes(c)
        merge_category(c)
        add_labels(c)
    except CleanError as e:
        sys.exit(f"DIBATALKAN, tidak ada file ditulis: {e}")

    problems = post_checks(src, work)
    if problems:
        sys.exit("DIBATALKAN, pemeriksaan gagal:\n  - " + "\n  - ".join(problems))
    if md5_of(src_path) != md5_before:
        sys.exit("DIBATALKAN: file sumber berubah selama proses.")

    out_path.parent.mkdir(parents=True, exist_ok=True)
    work.serialize(destination=out_path, format="turtle", encoding="utf-8")

    # Validasi: file hasil harus bisa dibaca ulang dan isinya sama dengan graf kerja.
    check = Graph()
    check.parse(out_path, format="turtle")
    if len(check) != len(work):
        sys.exit("File hasil tidak konsisten setelah dibaca ulang.")

    rel = lambda p: p.relative_to(PROJECT_ROOT) if p.is_relative_to(PROJECT_ROOT) else p
    meta = {"src": rel(src_path), "md5": md5_before, "out": rel(out_path),
            "p_before": len(set(src.subjects(RDF.type, EX.Product))),
            "p_after": len(set(check.subjects(RDF.type, EX.Product))),
            "t_before": len(src), "t_after": len(check)}
    write_changelog(args.changelog.resolve(), c, meta)

    print(f"Selesai. Produk {meta['p_before']} -> {meta['p_after']}, triple {meta['t_before']} -> {meta['t_after']}.")
    for code, n in sorted(Counter(r[0] for r in c.log).items(), key=lambda x: (len(x[0]), x[0])):
        print(f"  {code:<6} {n:>3} perubahan")
    print(f"Hasil     : {out_path}")
    print(f"Changelog : {args.changelog.resolve()}")
    print(f"Sumber tidak berubah (MD5 {md5_before}).")


if __name__ == "__main__":
    main()
