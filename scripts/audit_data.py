#!/usr/bin/env python3
"""
Audit kualitas data ontologi Aphrodite (temuan A1–A10).

Skrip ini HANYA MEMBACA file Turtle (.ttl) dan menulis laporan Markdown.
Tidak ada data sumber yang diubah, diperbaiki, atau dihapus.

Definisi A1–A10 diambil dari dokumen
"Aphrodite — Kritik, Rencana Pengembangan & Pembagian Tugas 5 Orang", bagian A:
  A1  Kolom tergeser: jenis kulit berisi nama bahan
  A2  Jenis kulit belum diisi
  A3  sourceProductId dobel / URI terpotong
  A4  Bahan pecah karena split koma dan sisa Excel
  A5  Klaim/catatan tercampur dengan bahan
  A6  Produk tanpa kelompok umur
  A7  Data melanggar Teen Lock (produk ber-retinoid ditandai Teen)
  A8  Bahan tampak tersalin dari produk lain
  A9  Kategori dobel dan tidak konsisten
  A10 Typo dan label kosong (Brand, Series, Category tanpa rdfs:label)

Cara pakai (dari folder root project):
  python3 scripts/audit_data.py
  python3 scripts/audit_data.py --input ontology/skincare_ontology.ttl --output reports/audit_report.md
"""

import argparse
import difflib
import hashlib
import os
import re
import sys
from collections import Counter, defaultdict
from datetime import datetime
from pathlib import Path

from rdflib import Graph, Literal, Namespace, RDF, RDFS, URIRef

# ---------------------------------------------------------------------------
# 1. KONFIGURASI
# ---------------------------------------------------------------------------
PROJECT_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_INPUT = PROJECT_ROOT / "ontology" / "skincare_ontology.ttl"
DEFAULT_OUTPUT = PROJECT_ROOT / "reports" / "audit_report.md"

EX = Namespace("https://example.org/skincare-ontology#")

# Daftar resmi jenis kulit. Lima nilai pertama dipakai di ontologi saat ini;
# "Kombinasi" ditambahkan karena dipakai di form kuis (dan diusulkan di B6).
OFFICIAL_SKIN_TYPES = {"SemuaJenisKulit", "Berminyak", "Kering", "Normal", "Sensitif", "Kombinasi"}

# Kata yang menandakan nilai jenis kulit masih placeholder (A2).
PLACEHOLDER_KEYWORDS = ["verifikasi", "konfirmasi", "not specified", "tbd", "-"]

# Kata kunci klaim/catatan yang bukan nama bahan (A5).
CLAIM_KEYWORDS = ["alcohol free", "free from", "tanpa ", "paraben", "organik", "organic",
                  "fragrance free", "non comedogenic", "non-comedogenic", "hypoallergenic"]
NOTE_KEYWORDS = ["cek inci", "perlu verifikasi", "perlu konfirmasi", "konfirmasi label",
                 "label resmi", "belum diverifikasi"]

# Kata kunci golongan retinoid (A7). Dicocokkan sebagai substring huruf kecil.
RETINOID_KEYWORDS = ["retinol", "retinal", "retinoid", "retinyl", "retinoate",
                     "tretinoin", "adapalene", "tazarotene"]

# Kata penghubung yang menandakan dua bahan tergabung / sisa pemecahan kalimat (A4).
CONNECTOR_PREFIXES = ("dan ", "and ", "serta ", "& ")

# Format sourceProductId yang diharapkan: 3 huruf - 4 digit (contoh WAR-0001).
ID_PATTERN = re.compile(r"^[A-Z]{3}-\d{4}$")


# ---------------------------------------------------------------------------
# 2. FUNGSI BANTU
# ---------------------------------------------------------------------------
def local(uri):
    """Ambil bagian setelah '#' dari URI, contoh ex:WAR-0001 -> 'WAR-0001'."""
    s = str(uri)
    return s.split("#")[-1] if "#" in s else s.rsplit("/", 1)[-1]


def md5_of(path):
    return hashlib.md5(Path(path).read_bytes()).hexdigest()


def norm(text):
    """Huruf kecil + hanya huruf/angka; dipakai untuk membandingkan nama."""
    return re.sub(r"[^a-z0-9]", "", str(text).lower())


def split_camel(name):
    """'BighteningSeries' -> ['Bightening', 'Series']"""
    return re.findall(r"[A-Z]+(?=[A-Z][a-z]|\d|\b)|[A-Z]?[a-z]+|\d+", name)


def md_escape(text):
    """Supaya karakter | dan baris baru tidak merusak tabel Markdown."""
    return str(text).replace("|", "\\|").replace("\n", " ").strip()


class Finding:
    def __init__(self, code, subtype, product, value, reason):
        self.code = code          # contoh: "A4"
        self.subtype = subtype    # contoh: "Mengandung sisa Excel (+G)"
        self.product = product    # ID/URI produk atau resource yang bermasalah
        self.value = value        # nilai yang ditemukan di data
        self.reason = reason      # alasan dianggap bermasalah


# ---------------------------------------------------------------------------
# 3. MEMBACA DATA
# ---------------------------------------------------------------------------
def load_products(g):
    """Ubah graf RDF menjadi daftar dict per produk agar mudah diperiksa."""
    products = []
    for p in sorted(g.subjects(RDF.type, EX.Product), key=str):
        products.append({
            "uri": p,
            "local": local(p),
            "id": str(g.value(p, EX.sourceProductId) or ""),
            "name": str(g.value(p, EX.productName) or ""),
            "brand": [local(o) for o in g.objects(p, EX.hasBrand)],
            "series": [local(o) for o in g.objects(p, EX.belongsToSeries)],
            "categories": [local(o) for o in g.objects(p, EX.hasCategory)],
            "skin": [str(o) for o in g.objects(p, EX.suitableForSkinType)],
            "age": [local(o) for o in g.objects(p, EX.suitableForAgeGroup)],
            "ingredients": sorted(str(o) for o in g.objects(p, EX.hasIngredient)),
        })
    return products


def label_of(prod):
    """Penanda produk di laporan: URI lokal (+ sourceProductId jika berbeda)."""
    if prod["id"] and prod["id"] != prod["local"]:
        return f"ex:{prod['local']} (sourceProductId {prod['id']})"
    return f"ex:{prod['local']}"


# ---------------------------------------------------------------------------
# 4. PEMERIKSAAN A1–A10
# ---------------------------------------------------------------------------
def is_placeholder(value):
    v = value.strip().lower()
    return v == "" or any(k == v or (len(k) > 2 and k in v) for k in PLACEHOLDER_KEYWORDS)


def check_a1_a2(products, all_ingredients_norm):
    """A1: nilai jenis kulit di luar daftar resmi. A2: jenis kulit kosong/placeholder."""
    out = []
    for p in products:
        if not p["skin"]:
            out.append(Finding("A2", "Tidak punya suitableForSkinType", label_of(p), "(kosong)",
                               "Produk tidak punya jenis kulit sama sekali, sehingga tidak muncul saat user memilih jenis kulit."))
        for v in sorted(p["skin"]):
            if v in OFFICIAL_SKIN_TYPES:
                continue
            if is_placeholder(v):
                out.append(Finding("A2", "Jenis kulit masih placeholder", label_of(p), v,
                                   "Nilai menandakan data belum diverifikasi, bukan jenis kulit nyata."))
                continue
            in_own = norm(v) in {norm(i) for i in p["ingredients"]}
            in_any = norm(v) in all_ingredients_norm
            extra = (" Nilai yang sama juga tercatat sebagai bahan produk ini." if in_own else
                     " Nilai yang sama tercatat sebagai bahan di produk lain." if in_any else "")
            out.append(Finding("A1", "Jenis kulit di luar daftar resmi", label_of(p), v,
                               "Bukan salah satu dari " + ", ".join(sorted(OFFICIAL_SKIN_TYPES))
                               + "; kemungkinan kolom tergeser (berisi nama bahan)." + extra))
    return out


def check_a3(products):
    """A3: sourceProductId dobel, format ID salah, URI tidak sama dengan ID, URI terpotong."""
    out = []
    counts = Counter(p["id"] for p in products)
    for p in products:
        if not p["id"]:
            out.append(Finding("A3", "Tidak punya sourceProductId", label_of(p), "(kosong)",
                               "Setiap produk wajib punya ID unik."))
            continue
        if counts[p["id"]] > 1:
            out.append(Finding("A3", "sourceProductId dobel", f"ex:{p['local']}", p["id"],
                               f"ID ini dipakai oleh {counts[p['id']]} produk; ID harus unik."))
        if not ID_PATTERN.match(p["id"]):
            out.append(Finding("A3", "Format sourceProductId tidak standar", f"ex:{p['local']}", p["id"],
                               "Tidak mengikuti pola XXX-0000."))
        if p["local"] != p["id"]:
            out.append(Finding("A3", "URI tidak sama dengan sourceProductId", f"ex:{p['local']}", p["local"],
                               f"Produk lain memakai URI pendek ex:<ID>; URI ini ditambah akhiran nama "
                               f"({len(p['local'])} karakter)."))
            # Deteksi URI terpotong: akhiran URI adalah awalan nama produk tapi lebih pendek.
            prefix = p["id"] + "-"
            if p["local"].startswith(prefix):
                tail = norm(p["local"][len(prefix):])
                full = norm(p["name"])
                if tail and full.startswith(tail) and len(tail) < len(full):
                    out.append(Finding("A3", "URI terpotong", f"ex:{p['local']}",
                                       f"…{p['local'][len(prefix):]} vs nama \"{p['name']}\"",
                                       "Akhiran URI berhenti di tengah nama produk (terpotong)."))
    return out


def check_a4(products):
    """A4: bahan pecah/rusak akibat split koma dan sisa Excel."""
    out = []
    for p in products:
        for ing in p["ingredients"]:
            s = ing.strip()
            reasons = []
            if len(s) < 3:
                reasons.append(("Panjang < 3 karakter", f"Hanya {len(s)} karakter; kemungkinan potongan nama bahan (mis. '1,2-hexanediol' terpecah)."))
            if re.search(r"\+G\d*", s):
                reasons.append(("Mengandung sisa Excel (+G)", "Ada pola '+G<angka>' yang berasal dari sisa rumus/sel Excel."))
            if s.count("(") != s.count(")"):
                reasons.append(("Kurung tidak seimbang", "Jumlah '(' dan ')' tidak sama; bahan terpecah di tengah kurung karena split koma."))
            if s.lower().startswith(CONNECTOR_PREFIXES):
                reasons.append(("Diawali kata penghubung", "Diawali 'dan/and/serta'; sisa pemecahan kalimat."))
            elif re.search(r"\s(dan|and)\s", s, re.I) and "(" not in s:
                reasons.append(("Beberapa bahan dalam satu nilai", "Mengandung 'dan/and' di tengah; kemungkinan dua bahan belum dipisah."))
            if re.match(r"^\d+-[a-z]", s):
                reasons.append(("Diawali angka-tanda hubung", "Kemungkinan sisa nama kimia yang terpotong (mis. '1,2-…')."))
            if re.search(r"\bPA\+{2,}|SPF\s?\d+", s):
                reasons.append(("Klaim SPF/PA di kolom bahan", "SPF/PA adalah tingkat perlindungan, bukan nama bahan."))
            for sub, why in reasons:
                out.append(Finding("A4", sub, label_of(p), s, why))

    distinct = sorted({i.strip() for p in products for i in p["ingredients"]})
    words = Counter(w.lower() for i in distinct for w in re.findall(r"[A-Za-z]{5,}", i))

    # Kemungkinan typo per KATA: kata yang hanya muncul sekali dan sangat mirip
    # dengan kata lain yang lebih sering muncul (mis. 'Crystale' vs 'Crystals').
    # Beda bentuk tunggal/jamak (akhiran 's') tidak dianggap typo.
    for p in products:
        for ing in p["ingredients"]:
            for w in re.findall(r"[A-Za-z]{6,}", ing):
                lw = w.lower()
                if words[lw] > 1:
                    continue
                for other in difflib.get_close_matches(lw, [x for x in words if x != lw], n=3, cutoff=0.85):
                    if other.rstrip("s") == lw.rstrip("s") or words[other] < 2:
                        continue  # bentuk jamak, atau pembanding juga jarang muncul
                    if lw.startswith(other) or other.startswith(lw):
                        continue  # kata gabungan, mis. 'AdvancedHA', 'TechnologyTM'
                    if lw[:-1] == other[:-1] and lw.endswith("a") and other.endswith("e"):
                        continue  # ejaan Indonesia (-ida) vs Inggris (-ide), mis. peptida/peptide
                    out.append(Finding("A4", "Kemungkinan typo pada nama bahan", label_of(p), ing.strip(),
                                       f"Kata '{w}' hanya muncul sekali dan mirip '{other}' "
                                       f"(muncul {words[other]}x di data)."))
                    break

    # Huruf awal hilang: kata pertama huruf kecil yang ternyata akhiran kata bahan lain.
    for p in products:
        for ing in p["ingredients"]:
            first = re.match(r"[A-Za-z]+", ing.strip())
            if not first:
                continue
            w = first.group(0)
            if w[0].islower() and len(w) >= 5:
                longer = [x for x in words if x != w.lower() and x.endswith(w.lower()) and len(x) - len(w) <= 2]
                medical = ["saccharide"] if w.lower() == "accharide" else []
                cand = longer or medical
                if cand:
                    out.append(Finding("A4", "Kemungkinan huruf awal hilang", label_of(p), ing.strip(),
                                       f"Kata '{w}' kemungkinan terpotong dari '{cand[0]}'."))
    return out


def check_a5(products):
    """A5: klaim produk atau catatan verifikasi yang tercatat sebagai bahan."""
    out = []
    for p in products:
        for ing in p["ingredients"]:
            low = ing.lower()
            note = next((k for k in NOTE_KEYWORDS if k in low), None)
            claim = next((k for k in CLAIM_KEYWORDS if k in low), None)
            if note:
                out.append(Finding("A5", "Catatan verifikasi di kolom bahan", label_of(p), ing,
                                   f"Mengandung '{note.strip()}': ini catatan proses, seharusnya di verificationStatus."))
            elif claim:
                out.append(Finding("A5", "Klaim produk di kolom bahan", label_of(p), ing,
                                   f"Mengandung '{claim.strip()}': ini klaim, bukan bahan (usulan: ex:hasClaim)."))
    return out


def check_a6(products):
    """A6: produk tanpa suitableForAgeGroup."""
    return [Finding("A6", "Tidak punya kelompok umur", label_of(p), f"\"{p['name']}\"",
                    "Tidak punya suitableForAgeGroup; produk hilang setiap kali user memilih umur.")
            for p in products if not p["age"]]


def check_a7(products):
    """A7: produk ber-retinoid tapi ditandai cocok untuk Teen."""
    out = []
    for p in products:
        if "Teen" not in p["age"]:
            continue
        for ing in p["ingredients"]:
            kw = next((k for k in RETINOID_KEYWORDS if k in ing.lower()), None)
            if kw:
                out.append(Finding("A7", "Retinoid ditandai Teen", label_of(p),
                                   f"\"{ing}\"; umur: {', '.join(sorted(p['age']))}",
                                   f"Bahan mengandung '{kw}' (golongan retinoid) tetapi produk ditandai ex:Teen; melanggar Teen Lock."))
    return out


def check_a8(products, all_series):
    """A8 (heuristik): bahan menyebut nama seri/teknologi milik seri lain."""
    out = []
    keys = {}
    for s in all_series:
        base = s[:-len("Series")] if s.endswith("Series") else s
        if len(base) >= 5:
            keys[norm(base)] = s
    for p in products:
        own = {norm(s[:-6] if s.endswith("Series") else s) for s in p["series"]}
        for ing in p["ingredients"]:
            n = norm(ing)
            for key, series in keys.items():
                if key in n and key not in own and key not in norm(p["name"]):
                    out.append(Finding("A8", "Bahan menyebut teknologi seri lain", label_of(p),
                                       f"\"{ing}\"; seri produk: {', '.join(p['series']) or '-'}",
                                       f"Nama bahan memuat '{series}' padahal produk bukan bagian seri itu; "
                                       f"kemungkinan tersalin dari produk lain. Wajib dicek manual di situs resmi."))
    return out


def check_a9(g):
    """A9: kategori dobel (beda huruf besar/kecil) dan kandidat kategori bertumpuk."""
    out = []
    cats = sorted({local(c) for c in g.subjects(RDF.type, EX.Category)} |
                  {local(o) for o in g.objects(None, EX.hasCategory)})
    usage = Counter(local(o) for o in g.objects(None, EX.hasCategory))

    def base(c):
        return c[:-len("Category")] if c.endswith("Category") else c

    groups = defaultdict(list)
    for c in cats:
        groups[base(c).lower()].append(c)
    dup_pairs = set()
    for k, members in groups.items():
        if len(members) > 1:
            dup_pairs.update({frozenset(members)})
            out.append(Finding("A9", "Kategori dobel (beda huruf besar/kecil)", ", ".join(members),
                               "; ".join(f"{m} dipakai {usage[m]} produk" for m in members),
                               "Nama sama, hanya beda kapitalisasi; harus disatukan."))
    for i, a in enumerate(cats):
        for b in cats[i + 1:]:
            if frozenset({a, b}) in dup_pairs:
                continue
            la, lb = base(a).lower(), base(b).lower()
            common = len(os.path.commonprefix([la, lb]))
            contains = la in lb or lb in la
            if contains or common >= 4:
                kind = ("Kandidat sub-kategori (nama satu memuat nama lain)" if contains
                        else "Kandidat duplikat/sinonim (awalan nama sama)")
                out.append(Finding("A9", kind, f"{a} ↔ {b}",
                                   f"{a} ({usage[a]} produk), {b} ({usage[b]} produk)",
                                   "Perlu keputusan manual: disatukan, atau dijadikan hierarki (skos:broader)."))
    return out


def check_a10(g, products):
    """A10: typo pada nama seri/kategori/brand, dan resource tanpa rdfs:label."""
    out = []
    # (a) Typo: kata di nama seri yang tidak muncul di nama produk anggotanya,
    #     tetapi sangat mirip dengan salah satu kata di nama produk itu.
    members = defaultdict(list)
    for p in products:
        for s in p["series"]:
            members[s].append(p)
    for series, prods in sorted(members.items()):
        name_words = {w.lower() for p in prods for w in re.findall(r"[A-Za-z]+", p["name"])}
        for w in split_camel(series[:-6] if series.endswith("Series") else series):
            lw = w.lower()
            if len(lw) < 5 or lw in name_words:
                continue
            close = difflib.get_close_matches(lw, list(name_words), n=1, cutoff=0.85)
            if close:
                out.append(Finding("A10", "Kemungkinan typo pada nama seri", f"ex:{series}",
                                   f"'{w}' vs '{close[0]}' (nama produk: \"{prods[0]['name']}\")",
                                   "Kata pada URI seri berbeda tipis dengan kata di nama produk anggotanya."))
    # (b) Label kosong pada Brand, ProductSeries, Category (dan SkinConcern/AgeGroup sebagai info).
    targets = [("Brand", EX.Brand, EX.hasBrand), ("ProductSeries", EX.ProductSeries, EX.belongsToSeries),
               ("Category", EX.Category, EX.hasCategory), ("SkinConcern", EX.SkinConcern, EX.treatsConcern),
               ("AgeGroup", EX.AgeGroup, EX.suitableForAgeGroup)]
    for cls_name, cls, prop in targets:
        resources = set(g.subjects(RDF.type, cls)) | set(g.objects(None, prop))
        for r in sorted(resources, key=str):
            if not isinstance(r, URIRef):
                continue
            labels = list(g.objects(r, RDFS.label))
            if not labels:
                out.append(Finding("A10", f"{cls_name} tanpa rdfs:label", f"ex:{local(r)}", "(tidak ada label)",
                                   "Tidak ada rdfs:label \"…\"@id; UI terpaksa menampilkan nama URI mentah."))
            elif not any(isinstance(l, Literal) and l.language == "id" for l in labels):
                out.append(Finding("A10", f"{cls_name} tanpa label @id", f"ex:{local(r)}",
                                   ", ".join(str(l) for l in labels), "Ada label, tetapi tidak berbahasa Indonesia (@id)."))
    return out


def ingredient_variants(products):
    """
    INFORMASI TAMBAHAN (bukan pelanggaran A1–A10): nama bahan yang sama ditulis
    dengan cara berbeda (kapital, kadar %, jamak). Berguna untuk skos:altLabel di v2 (B1).
    """
    groups = defaultdict(lambda: defaultdict(set))
    for p in products:
        for ing in p["ingredients"]:
            s = ing.strip()
            key = re.sub(r"\d+(\.\d+)?\s*%|[^a-z]", "", s.lower()).rstrip("s")
            if len(key) >= 4:
                groups[key][s].add(p["local"])
    rows = []
    for key, variants in sorted(groups.items()):
        if len(variants) > 1:
            total = len({x for v in variants.values() for x in v})
            rows.append((sorted(variants), total))
    return rows


# ---------------------------------------------------------------------------
# 5. MENULIS LAPORAN
# ---------------------------------------------------------------------------
TITLES = {
    "A1": "Kolom tergeser: jenis kulit berisi nama bahan",
    "A2": "Jenis kulit belum diisi",
    "A3": "sourceProductId dobel / URI tidak konsisten",
    "A4": "Bahan pecah karena split koma dan sisa Excel",
    "A5": "Klaim/catatan tercampur dengan bahan",
    "A6": "Produk tanpa kelompok umur",
    "A7": "Data melanggar Teen Lock",
    "A8": "Bahan tampak tersalin dari produk lain (heuristik)",
    "A9": "Kategori dobel dan tidak konsisten",
    "A10": "Typo dan label kosong",
}

LIMITATIONS = {
    "A1": "Hanya bisa mendeteksi nilai di luar daftar resmi. Nilai jenis kulit yang BENAR tidak bisa ditentukan otomatis; harus dicek di situs/label resmi.",
    "A2": "Placeholder dikenali dari kata kunci ('verifikasi', 'konfirmasi', 'not specified'). Placeholder dengan kata lain bisa terlewat.",
    "A3": "Deteksi URI terpotong membandingkan akhiran URI dengan nama produk; jika nama produk sendiri berbeda dari URI, potongan tidak terdeteksi.",
    "A4": "Berbasis pola (panjang < 3, '+G', kurung tidak seimbang, kata penghubung, kemiripan nama). Typo yang tidak punya 'pasangan' benar di dataset tidak bisa dideteksi.",
    "A5": "Berbasis daftar kata kunci; klaim dengan kata lain tidak terdeteksi.",
    "A6": "Dapat diperiksa penuh secara otomatis.",
    "A7": "Retinoid dikenali dari kata kunci nama (retinol, retinal, retinyl, dsb.) karena bahan masih berupa teks. Setelah ontologi v2 (kelas ex:Retinoid) pemeriksaan ini bisa berbasis kelas.",
    "A8": "TIDAK bisa dipastikan otomatis. Skrip hanya menandai bahan yang menyebut nama seri/teknologi lain. Kebenaran komposisi harus dicek manual di situs resmi brand.",
    "A9": "Duplikat beda kapital pasti terdeteksi. Sinonim dengan nama berbeda total (mis. 'MicellarCleanser' vs 'Micellarwater') hanya muncul sebagai KANDIDAT; keputusan menyatukan/hierarki tetap manual.",
    "A10": "Label kosong dapat diperiksa penuh. Typo hanya terdeteksi untuk nama seri yang katanya mirip dengan kata di nama produk anggotanya.",
}


def write_report(path, findings, meta, products):
    by_code = defaultdict(list)
    for f in findings:
        by_code[f.code].append(f)
    codes = [f"A{i}" for i in range(1, 11)]

    lines = []
    w = lines.append
    w("# Laporan Audit Data Ontologi Aphrodite (A1–A10)")
    w("")
    w("> Dibuat otomatis oleh `scripts/audit_data.py`. Jangan diedit manual, jalankan ulang skripnya.")
    w("")
    w(f"- **File sumber:** `{meta['input']}`")
    w(f"- **MD5 file sumber:** `{meta['md5']}` (tidak berubah sebelum/sesudah audit)")
    w(f"- **Waktu audit:** {meta['time']}")
    w(f"- **Jumlah triple:** {meta['triples']}  ·  **Jumlah produk:** {len(products)}")
    w(f"- **Daftar resmi jenis kulit:** {', '.join(sorted(OFFICIAL_SKIN_TYPES))}")
    w("")
    w("## Ringkasan")
    w("")
    w("| Kode | Anomali | Jumlah temuan | Produk/resource terdampak |")
    w("|---|---|---:|---:|")
    for c in codes:
        items = by_code.get(c, [])
        affected = len({f.product for f in items})
        w(f"| {c} | {TITLES[c]} | {len(items)} | {affected} |")
    w(f"| | **Total** | **{len(findings)}** | |")
    w("")
    w("Catatan: satu produk bisa muncul di beberapa kategori, dan satu nilai bisa memicu lebih dari satu sub-jenis temuan.")
    w("")

    for c in codes:
        items = by_code.get(c, [])
        w(f"## {c} — {TITLES[c]}")
        w("")
        w(f"**Jumlah temuan:** {len(items)}")
        sub = Counter(f.subtype for f in items)
        if sub:
            w("")
            for s, n in sorted(sub.items()):
                w(f"- {s}: {n}")
        w("")
        if items:
            w("| # | Jenis anomali | Produk / URI | Nilai ditemukan | Alasan |")
            w("|---:|---|---|---|---|")
            for i, f in enumerate(items, 1):
                w(f"| {i} | {md_escape(f.subtype)} | {md_escape(f.product)} | {md_escape(f.value)} | {md_escape(f.reason)} |")
        else:
            w("_Tidak ada temuan._")
        w("")
        w(f"**Keterbatasan pemeriksaan:** {LIMITATIONS[c]}")
        w("")

    variants = ingredient_variants(products)
    w("## Informasi tambahan: varian penulisan bahan (bukan bagian A1–A10)")
    w("")
    w("Nama bahan yang sama ditulis berbeda (kapital, kadar %, tunggal/jamak). Ini bukan kesalahan data, "
      "tetapi bahan untuk `skos:altLabel` saat migrasi ke ontologi v2 (temuan B1). Tidak dihitung di ringkasan.")
    w("")
    if variants:
        w("| # | Varian yang ditemukan | Jumlah produk |")
        w("|---:|---|---:|")
        for i, (names, total) in enumerate(variants, 1):
            w(f"| {i} | {md_escape(' · '.join(names))} | {total} |")
    else:
        w("_Tidak ada._")
    w("")

    w("## Catatan umum")
    w("")
    w("- Audit ini hanya **mendeteksi**; tidak ada data yang diperbaiki atau dihapus.")
    w("- Nilai pengganti (jenis kulit yang benar, komposisi yang benar, ID baru) **tidak** dibuat oleh skrip ini.")
    w("- Temuan bertanda *kandidat* / *kemungkinan* perlu dicek manusia sebelum dijadikan perbaikan di data v1.5.")
    w("")

    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text("\n".join(lines), encoding="utf-8")


# ---------------------------------------------------------------------------
# 6. PROGRAM UTAMA
# ---------------------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser(description="Audit anomali data A1–A10 pada ontologi Aphrodite (read-only).")
    ap.add_argument("--input", type=Path, default=DEFAULT_INPUT, help="File Turtle sumber")
    ap.add_argument("--output", type=Path, default=DEFAULT_OUTPUT, help="File laporan Markdown")
    args = ap.parse_args()

    src = args.input.resolve()
    if not src.exists():
        sys.exit(f"File tidak ditemukan: {src}")

    md5_before = md5_of(src)
    g = Graph()
    g.parse(src, format="turtle")
    products = load_products(g)

    all_ing_norm = {norm(i) for p in products for i in p["ingredients"]}
    all_series = sorted({local(s) for s in g.subjects(RDF.type, EX.ProductSeries)} |
                        {s for p in products for s in p["series"]})

    findings = []
    findings += check_a1_a2(products, all_ing_norm)
    findings += check_a3(products)
    findings += check_a4(products)
    findings += check_a5(products)
    findings += check_a6(products)
    findings += check_a7(products)
    findings += check_a8(products, all_series)
    findings += check_a9(g)
    findings += check_a10(g, products)

    md5_after = md5_of(src)
    if md5_before != md5_after:
        sys.exit("PERINGATAN: file sumber berubah selama audit! Laporan tidak ditulis.")

    try:
        shown = src.relative_to(PROJECT_ROOT)
    except ValueError:
        shown = src
    meta = {"input": shown, "md5": md5_before, "triples": len(g),
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
    out = args.output.resolve()
    write_report(out, findings, meta, products)

    counts = Counter(f.code for f in findings)
    print(f"Audit selesai: {len(products)} produk, {len(findings)} temuan.")
    for i in range(1, 11):
        c = f"A{i}"
        print(f"  {c:<4} {counts.get(c, 0):>4}  {TITLES[c]}")
    print(f"Laporan: {out}")
    print(f"File sumber tidak berubah (MD5 {md5_before}).")


if __name__ == "__main__":
    main()
