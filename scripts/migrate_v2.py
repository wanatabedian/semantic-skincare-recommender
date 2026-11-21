#!/usr/bin/env python3
"""
Migrasi ontologi Aphrodite: v1.5 -> v2.

- Membaca  : ontology/aphrodite-v1.5.ttl            (TIDAK diubah; dicek dengan MD5)
             ontology/mappings/ingredient_map_v2.csv (pemetaan bahan, sudah disetujui)
             ontology/mappings/skintype_map_v2.csv   (pemetaan jenis kulit, sudah disetujui)
- Menulis  : ontology/aphrodite-v2.ttl

Perubahan:
  * ex:hasIngredient       DatatypeProperty -> ObjectProperty (range ex:Ingredient)
  * ex:suitableForSkinType DatatypeProperty -> ObjectProperty (range ex:SkinType)
  * Semua literal lama dipindah ke ex:sourceIngredientText / ex:sourceSkinTypeText
    (195 + 88 triple), termasuk nilai HOLD, KEEP_TEXT, dan 'PerluVerifikasi'.
  * Individu Ingredient & SkinType dibuat HANYA dari baris CSV berstatus MAP/SPLIT.
  * skos:prefLabel / skos:altLabel sesuai CSV.

Prinsip:
  * Tidak ada individu di luar CSV; tidak ada tebakan.
  * Literal sumber yang tidak ada di CSV -> skrip BERHENTI.
  * Seluruh validasi dijalankan di memori SEBELUM menulis, lalu diulang pada
    hasil tulis (round-trip). File output hanya muncul jika semua lolos.
  * Selain triple yang memang dimigrasikan, semua triple v1.5 harus identik di v2.

Cara pakai (dari root project):
  python3 scripts/migrate_v2.py --check-only   # validasi saja, tidak menulis
  python3 scripts/migrate_v2.py                # validasi + tulis aphrodite-v2.ttl
"""

import argparse
import csv
import hashlib
import os
import sys
from collections import Counter, defaultdict
from pathlib import Path

from rdflib import Graph, Literal, Namespace, OWL, RDF, RDFS, URIRef, XSD
from rdflib.namespace import SKOS

PROJECT_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_INPUT = PROJECT_ROOT / "ontology" / "aphrodite-v1.5.ttl"
DEFAULT_OUTPUT = PROJECT_ROOT / "ontology" / "aphrodite-v2.ttl"
DEFAULT_ING_MAP = PROJECT_ROOT / "ontology" / "mappings" / "ingredient_map_v2.csv"
DEFAULT_SKIN_MAP = PROJECT_ROOT / "ontology" / "mappings" / "skintype_map_v2.csv"
PROTECTED = [
    PROJECT_ROOT / "ontology" / "skincare_ontology.ttl",
    PROJECT_ROOT / "ontology" / "aphrodite-v1.5.ttl",
]

EX = Namespace("https://example.org/skincare-ontology#")
DCTERMS = Namespace("http://purl.org/dc/terms/")

HAS_ING = EX.hasIngredient
SKIN = EX.suitableForSkinType
SRC_ING = EX.sourceIngredientText
SRC_SKIN = EX.sourceSkinTypeText
ONTOLOGY = EX.SkincareOntology

# Angka yang disetujui (lihat ontology/mappings/*.csv).
EXPECTED = {
    "products": 88,
    "hasIngredient": 96,
    "suitableForSkinType": 84,
    "sourceIngredientText": 195,
    "sourceSkinTypeText": 88,
    "ingredient_individuals": 39,
    "skintype_individuals": 5,
    "ingredient_altLabels": 24,
    "ingredient_csv_rows": 135,
}
ING_DECISIONS = {"MAP", "SPLIT", "KEEP_TEXT", "HOLD"}
NOT_A_SKIN_TYPE = "PerluVerifikasi"


class MigrationError(Exception):
    pass


def require(cond, msg):
    if not cond:
        raise MigrationError(msg)


def md5_of(path):
    h = hashlib.md5()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(65536), b""):
            h.update(chunk)
    return h.hexdigest()


def ex_uri(curie, suffix):
    """'ex:NiacinamideIngredient' -> URIRef; wajib prefiks ex: dan sufiks yang benar."""
    curie = curie.strip()
    require(curie.startswith("ex:"), f"URI target harus berprefiks 'ex:': {curie!r}")
    local = curie[3:]
    require(local.endswith(suffix) and local != suffix,
            f"URI target harus bersufiks '{suffix}': {curie!r}")
    return EX[local]


def product_ids(cell):
    ids = [p.strip() for p in cell.split(";") if p.strip()]
    require(len(ids) == len(set(ids)), f"Produk ganda di sel CSV: {cell!r}")
    return ids


# ---------------------------------------------------------------------------
# 1. MEMBACA CSV
# ---------------------------------------------------------------------------

def load_ingredient_map(path):
    """Kembalikan (rows_by_literal, individuals).

    individuals: {URIRef: {"pref": str, "alt": set[str]}}
    rows_by_literal: {literal: {"decision", "targets": [URIRef], "products": [..], "triples": int}}
    """
    with open(path, newline="", encoding="utf-8") as f:
        rows = list(csv.DictReader(f))
    require(len(rows) == EXPECTED["ingredient_csv_rows"],
            f"ingredient CSV: {len(rows)} baris, harus {EXPECTED['ingredient_csv_rows']}")

    by_lit = {}
    prefs = defaultdict(set)
    alts = defaultdict(set)
    for i, r in enumerate(rows, start=2):
        lit = r["source_literal"]
        dec = r["final_decision"].strip()
        require(lit not in by_lit, f"baris {i}: literal ganda {lit!r}")
        require(dec in ING_DECISIONS, f"baris {i}: final_decision tidak dikenal {dec!r}")
        require(r["keep_in_sourceIngredientText"].strip() == "YA",
                f"baris {i}: keep_in_sourceIngredientText harus YA")
        targets = []
        if dec in ("MAP", "SPLIT"):
            t_cells = r["target_uri"].split(";")
            p_cells = r["pref_label"].split(";")
            # alt_label: MAP = satu nilai utuh; SPLIT = berurutan, dipisah ';'
            a_cells = r["alt_label"].split(";") if dec == "SPLIT" else [r["alt_label"]]
            if dec == "MAP":
                require(len(t_cells) == 1, f"baris {i}: MAP harus tepat 1 target")
            else:
                require(len(t_cells) >= 2, f"baris {i}: SPLIT harus >= 2 target")
            require(len(p_cells) == len(t_cells) == len(a_cells),
                    f"baris {i}: jumlah target/pref_label/alt_label tidak sejajar")
            for t, p, a in zip(t_cells, p_cells, a_cells):
                uri = ex_uri(t, "Ingredient")
                targets.append(uri)
                # pref_label boleh kosong di baris ini (mis. baris C) asalkan individu
                # yang sama mendapat prefLabel dari baris lain; dicek di bawah.
                if p.strip():
                    prefs[uri].add(p.strip())
                else:
                    prefs.setdefault(uri, set())
                if a.strip():
                    alts[uri].add(a.strip())
            require(len(targets) == len(set(targets)), f"baris {i}: target ganda")
        else:
            require(not r["target_uri"].strip(),
                    f"baris {i}: {dec} tidak boleh punya target_uri ({r['target_uri']!r})")
        by_lit[lit] = {
            "decision": dec,
            "group": r["group"],
            "targets": targets,
            "products": product_ids(r["products"]),
            "triples": int(r["triples"]),
        }

    individuals = {}
    for uri, ps in prefs.items():
        require(len(ps) == 1, f"{uri}: harus tepat 1 prefLabel di CSV, ditemukan {sorted(ps)}")
        pref = next(iter(ps))
        alt = alts.get(uri, set())
        require(pref not in alt, f"{uri}: altLabel sama dengan prefLabel {pref!r}")
        individuals[uri] = {"pref": pref, "alt": alt}

    # prefLabel unik antar individu; altLabel tidak boleh = prefLabel individu lain
    lower = Counter(v["pref"].lower() for v in individuals.values())
    require(all(c == 1 for c in lower.values()),
            f"prefLabel ganda: {[k for k, c in lower.items() if c > 1]}")
    pref_owner = {v["pref"].lower(): u for u, v in individuals.items()}
    for u, v in individuals.items():
        for a in v["alt"]:
            owner = pref_owner.get(a.lower())
            require(owner in (None, u), f"altLabel {a!r} dari {u} = prefLabel {owner}")
    return by_lit, individuals


def load_skintype_map(path):
    """Kembalikan (rows_by_literal, individuals {URIRef: {"id": str, "en": str}})."""
    with open(path, newline="", encoding="utf-8") as f:
        rows = list(csv.DictReader(f))
    by_lit, individuals = {}, {}
    for i, r in enumerate(rows, start=2):
        lit = r["source_literal"]
        status = r["status"].strip()
        require(lit not in by_lit, f"skintype baris {i}: literal ganda {lit!r}")
        require(r["keep_in_sourceSkinTypeText"].strip() == "YA",
                f"skintype baris {i}: keep_in_sourceSkinTypeText harus YA")
        target = None
        if status == "MAP":
            require(lit != NOT_A_SKIN_TYPE, f"{NOT_A_SKIN_TYPE} tidak boleh MAP")
            target = ex_uri(r["target_uri"], "SkinType")
            require(r["label_id"].strip() and r["label_en"].strip(),
                    f"skintype baris {i}: label_id/label_en kosong")
            require(target not in individuals, f"skintype: target ganda {target}")
            individuals[target] = {"id": r["label_id"].strip(), "en": r["label_en"].strip()}
        else:
            require(status.startswith("NO_MAP"), f"skintype baris {i}: status {status!r}")
            require(not r["target_uri"].strip(), f"skintype baris {i}: NO_MAP punya target")
        by_lit[lit] = {"target": target, "products": product_ids(r["products"]),
                       "triples": int(r["triples"])}
    require(NOT_A_SKIN_TYPE in by_lit and by_lit[NOT_A_SKIN_TYPE]["target"] is None,
            f"{NOT_A_SKIN_TYPE} harus ada di CSV dengan status NO_MAP")
    return by_lit, individuals


# ---------------------------------------------------------------------------
# 2. MIGRASI
# ---------------------------------------------------------------------------

def check_source_matches_csv(src, prop, by_lit, label):
    """Setiap (produk, literal) di v1.5 harus tercantum persis di CSV, dan sebaliknya."""
    in_graph = Counter()
    for s, o in src.subject_objects(prop):
        require(isinstance(o, Literal), f"{label}: objek bukan literal di v1.5: {s} {o}")
        require(o.language is None and o.datatype is None,
                f"{label}: literal bertag bahasa/tipe tidak didukung: {o!r}")
        in_graph[(str(s).split("#")[1], str(o))] += 1
    in_csv = Counter((p, lit) for lit, row in by_lit.items() for p in row["products"])
    missing = set(in_graph) - set(in_csv)
    extra = set(in_csv) - set(in_graph)
    require(not missing, f"{label}: nilai sumber tidak ada di CSV: {sorted(missing)[:10]}")
    require(not extra, f"{label}: CSV memuat pasangan yang tidak ada di sumber: {sorted(extra)[:10]}")
    for lit, row in by_lit.items():
        require(row["triples"] == len(row["products"]),
                f"{label}: kolom triples tidak sesuai untuk {lit!r}")
    return in_graph


def migrate(src, ing_map, ing_ind, skin_map, skin_ind):
    out = Graph()
    for prefix, ns in src.namespaces():
        out.bind(prefix, ns, replace=True)
    out.bind("ex", EX, replace=True)
    out.bind("skos", SKOS, replace=True)

    removed, added = set(), set()

    def add(t):
        if t not in src:
            added.add(t)
        out.add(t)

    # 2a. salin semua triple kecuali yang dimigrasikan
    replaced_decl = {
        (HAS_ING, RDF.type, OWL.DatatypeProperty),
        (HAS_ING, RDFS.range, XSD.string),
        (SKIN, RDF.type, OWL.DatatypeProperty),
        (SKIN, RDFS.range, XSD.string),
    }
    for t in src:
        s, p, o = t
        if p in (HAS_ING, SKIN) and isinstance(o, Literal):
            removed.add(t)
        elif t in replaced_decl:
            removed.add(t)
        elif s == ONTOLOGY and p in (OWL.versionInfo, RDFS.comment):
            removed.add(t)
        else:
            out.add(t)

    # 2b. header ontologi
    add((ONTOLOGY, OWL.versionInfo, Literal("2.0")))
    add((ONTOLOGY, RDFS.comment, Literal(
        "Versi 2.0: hasil migrasi dari aphrodite-v1.5.ttl oleh scripts/migrate_v2.py. "
        "hasIngredient dan suitableForSkinType menjadi ObjectProperty; literal asli "
        "disimpan di sourceIngredientText dan sourceSkinTypeText. Pemetaan: "
        "ontology/mappings/ingredient_map_v2.csv dan skintype_map_v2.csv.", lang="id")))

    # 2c. skema
    for prop, rng in ((HAS_ING, EX.Ingredient), (SKIN, EX.SkinType)):
        add((prop, RDF.type, OWL.ObjectProperty))
        add((prop, RDFS.domain, EX.Product))
        add((prop, RDFS.range, rng))
    add((SRC_ING, RDF.type, OWL.DatatypeProperty))
    add((SRC_ING, RDFS.domain, EX.Product))
    add((SRC_ING, RDFS.range, XSD.string))
    add((SRC_ING, RDFS.label, Literal("teks bahan sumber", lang="id")))
    add((SRC_ING, RDFS.comment, Literal(
        "Literal bahan asli dari v1.5 (termasuk kadar, keterangan, dan nilai yang belum "
        "dipetakan). Satu triple per nilai sumber.", lang="id")))
    add((SRC_SKIN, RDF.type, OWL.DatatypeProperty))
    add((SRC_SKIN, RDFS.domain, EX.Product))
    add((SRC_SKIN, RDFS.range, XSD.string))
    add((SRC_SKIN, RDFS.label, Literal("teks jenis kulit sumber", lang="id")))
    add((SRC_SKIN, RDFS.comment, Literal(
        "Literal jenis kulit asli dari v1.5, termasuk penanda 'PerluVerifikasi' yang "
        "tidak dipetakan ke individu SkinType.", lang="id")))
    # Deklarasi agar SKOS dikenali sebagai anotasi di OWL DL (Protégé/HermiT).
    add((SKOS.prefLabel, RDF.type, OWL.AnnotationProperty))
    add((SKOS.altLabel, RDF.type, OWL.AnnotationProperty))

    # 2d. individu (hanya dari CSV)
    for uri, v in sorted(ing_ind.items()):
        add((uri, RDF.type, EX.Ingredient))
        add((uri, SKOS.prefLabel, Literal(v["pref"])))
        add((uri, RDFS.label, Literal(v["pref"])))
        for a in sorted(v["alt"]):
            add((uri, SKOS.altLabel, Literal(a)))
    for uri, v in sorted(skin_ind.items()):
        add((uri, RDF.type, EX.SkinType))
        add((uri, SKOS.prefLabel, Literal(v["id"], lang="id")))
        add((uri, RDFS.label, Literal(v["id"], lang="id")))
        add((uri, RDFS.label, Literal(v["en"], lang="en")))

    # 2e. relasi produk
    ing_pairs = []
    for s, o in src.subject_objects(HAS_ING):
        if not isinstance(o, Literal):
            continue
        add((s, SRC_ING, o))  # literal disimpan tepat satu kali, termasuk baris SPLIT
        for target in ing_map[str(o)]["targets"]:
            ing_pairs.append((s, target))
    dup = [k for k, c in Counter(ing_pairs).items() if c > 1]
    require(not dup, f"relasi produk-Ingredient duplikat: {dup}")
    for s, target in ing_pairs:
        add((s, HAS_ING, target))

    skin_pairs = []
    for s, o in src.subject_objects(SKIN):
        if not isinstance(o, Literal):
            continue
        add((s, SRC_SKIN, o))
        target = skin_map[str(o)]["target"]
        if target is not None:
            skin_pairs.append((s, target))
    dup = [k for k, c in Counter(skin_pairs).items() if c > 1]
    require(not dup, f"relasi produk-SkinType duplikat: {dup}")
    for s, target in skin_pairs:
        add((s, SKIN, target))

    return out, removed, added


# ---------------------------------------------------------------------------
# 3. VALIDASI
# ---------------------------------------------------------------------------

def validate(src, out, ing_map, ing_ind, skin_map, skin_ind, removed=None, added=None):
    """Kembalikan dict angka; raise MigrationError jika ada yang gagal."""
    n = {}
    products = set(out.subjects(RDF.type, EX.Product))
    n["products"] = len(products)
    n["hasIngredient"] = len(list(out.triples((None, HAS_ING, None))))
    n["suitableForSkinType"] = len(list(out.triples((None, SKIN, None))))
    n["sourceIngredientText"] = len(list(out.triples((None, SRC_ING, None))))
    n["sourceSkinTypeText"] = len(list(out.triples((None, SRC_SKIN, None))))
    ing_out = set(out.subjects(RDF.type, EX.Ingredient))
    skin_out = set(out.subjects(RDF.type, EX.SkinType))
    n["ingredient_individuals"] = len(ing_out)
    n["skintype_individuals"] = len(skin_out)
    n["ingredient_altLabels"] = sum(len(list(out.objects(u, SKOS.altLabel))) for u in ing_out)

    for key, val in n.items():
        require(val == EXPECTED[key], f"{key} = {val}, harus {EXPECTED[key]}")
    require(products == set(src.subjects(RDF.type, EX.Product)), "himpunan produk berubah")

    # skema
    for prop, rng in ((HAS_ING, EX.Ingredient), (SKIN, EX.SkinType)):
        require((prop, RDF.type, OWL.ObjectProperty) in out, f"{prop} bukan ObjectProperty")
        require((prop, RDF.type, OWL.DatatypeProperty) not in out, f"{prop} masih DatatypeProperty")
        require(set(out.objects(prop, RDFS.range)) == {rng}, f"{prop}: range harus {rng}")
        require(set(out.objects(prop, RDFS.domain)) == {EX.Product}, f"{prop}: domain harus Product")

    # tidak ada literal pada ObjectProperty; objek bertipe benar
    for prop, cls in ((HAS_ING, EX.Ingredient), (SKIN, EX.SkinType)):
        for s, o in out.subject_objects(prop):
            require(not isinstance(o, Literal), f"literal pada {prop}: {s} {o!r}")
            require((o, RDF.type, cls) in out, f"{s} {prop} {o}: objek bukan {cls}")
            require(s in products, f"subjek {s} pada {prop} bukan Product")

    # tidak ada individu di luar CSV
    require(ing_out == set(ing_ind), f"individu Ingredient tidak sesuai CSV: "
            f"lebih={sorted(ing_out - set(ing_ind))} kurang={sorted(set(ing_ind) - ing_out)}")
    require(skin_out == set(skin_ind), "individu SkinType tidak sesuai CSV")

    # label Ingredient
    for u in ing_out:
        prefs = list(out.objects(u, SKOS.prefLabel))
        require(len(prefs) == 1 and str(prefs[0]).strip(), f"{u}: harus tepat 1 prefLabel")
        require(str(prefs[0]) == ing_ind[u]["pref"], f"{u}: prefLabel tidak sesuai CSV")
        require({str(a) for a in out.objects(u, SKOS.altLabel)} == ing_ind[u]["alt"],
                f"{u}: altLabel tidak sesuai CSV")
        require(any(out.subjects(HAS_ING, u)), f"{u}: tidak dipakai produk mana pun")
    bg = EX.BetaGlucanIngredient
    require([str(x) for x in out.objects(bg, SKOS.prefLabel)] == ["Beta-Glucan"],
            "BetaGlucanIngredient: prefLabel harus 'Beta-Glucan'")
    require([str(x) for x in out.objects(bg, SKOS.altLabel)] == ["beta-glucan"],
            "BetaGlucanIngredient: altLabel harus 'beta-glucan'")
    require((EX["LAB-0062"], HAS_ING, bg) in out, "LAB-0062 harus terhubung ke BetaGlucanIngredient")

    # PerluVerifikasi
    for u in skin_out:
        labels = {str(x) for x in out.objects(u, None) if isinstance(x, Literal)}
        require(NOT_A_SKIN_TYPE not in labels and "Perlu verifikasi" not in labels,
                f"{u}: SkinType berlabel {NOT_A_SKIN_TYPE}")
    for p in skin_map[NOT_A_SKIN_TYPE]["products"]:
        s = EX[p]
        require(not any(out.objects(s, SKIN)), f"{p}: PerluVerifikasi tidak boleh punya {SKIN}")
        require(Literal(NOT_A_SKIN_TYPE) in set(out.objects(s, SRC_SKIN)),
                f"{p}: sourceSkinTypeText '{NOT_A_SKIN_TYPE}' hilang")

    # keterlacakan per produk: literal lama == sourceText baru (persis)
    for old_p, new_p, label in ((HAS_ING, SRC_ING, "bahan"), (SKIN, SRC_SKIN, "jenis kulit")):
        for s in products:
            old = {o for o in src.objects(s, old_p) if isinstance(o, Literal)}
            new = set(out.objects(s, new_p))
            require(old == new, f"{s}: literal {label} tidak terlacak "
                    f"(hilang={sorted(old - new)}, lebih={sorted(new - old)})")

    # relasi produk sesuai keputusan CSV (HOLD/KEEP_TEXT tidak menghasilkan relasi)
    for s in products:
        exp_ing = {t for o in src.objects(s, HAS_ING) if isinstance(o, Literal)
                   for t in ing_map[str(o)]["targets"]}
        require(set(out.objects(s, HAS_ING)) == exp_ing, f"{s}: relasi hasIngredient tidak sesuai CSV")
        exp_skin = {skin_map[str(o)]["target"] for o in src.objects(s, SKIN)
                    if isinstance(o, Literal)} - {None}
        require(set(out.objects(s, SKIN)) == exp_skin, f"{s}: relasi suitableForSkinType tidak sesuai CSV")

    # semua triple lain identik
    if removed is not None:
        require(set(src) - set(out) == removed, "ada triple v1.5 yang hilang di luar migrasi")
        require(set(out) - set(src) == added, "ada triple v2 yang tidak tercatat")
        for s, p, o in removed:
            require(p in (HAS_ING, SKIN, RDF.type, RDFS.range, OWL.versionInfo, RDFS.comment),
                    f"triple terhapus tak terduga: {s} {p} {o}")

    n["decisions"] = dict(Counter(r["decision"] for r in ing_map.values()))
    return n


# ---------------------------------------------------------------------------
# 4. MAIN
# ---------------------------------------------------------------------------

def main():
    ap = argparse.ArgumentParser(description="Migrasi ontologi v1.5 -> v2 (sumber tidak diubah).")
    ap.add_argument("--input", type=Path, default=DEFAULT_INPUT)
    ap.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    ap.add_argument("--ingredient-map", type=Path, default=DEFAULT_ING_MAP)
    ap.add_argument("--skintype-map", type=Path, default=DEFAULT_SKIN_MAP)
    ap.add_argument("--check-only", action="store_true", help="validasi saja, tidak menulis file")
    ap.add_argument("--force", action="store_true", help="timpa output jika sudah ada")
    args = ap.parse_args()

    out_path = args.output.resolve()
    if any(out_path == p.resolve() for p in PROTECTED):
        sys.exit(f"DITOLAK: output tidak boleh menimpa {out_path}")
    if out_path.exists() and not (args.force or args.check_only):
        sys.exit(f"DITOLAK: {out_path} sudah ada (gunakan --force untuk menimpa)")

    protected_md5 = {p: md5_of(p) for p in PROTECTED if p.exists()}

    try:
        ing_map, ing_ind = load_ingredient_map(args.ingredient_map)
        skin_map, skin_ind = load_skintype_map(args.skintype_map)

        src = Graph()
        src.parse(args.input, format="turtle")
        check_source_matches_csv(src, HAS_ING, ing_map, "bahan")
        check_source_matches_csv(src, SKIN, skin_map, "jenis kulit")

        out, removed, added = migrate(src, ing_map, ing_ind, skin_map, skin_ind)
        stats = validate(src, out, ing_map, ing_ind, skin_map, skin_ind, removed, added)

        if not args.check_only:
            tmp = out_path.with_suffix(out_path.suffix + ".tmp")
            out.serialize(destination=tmp, format="turtle", encoding="utf-8")
            reread = Graph()
            reread.parse(tmp, format="turtle")
            try:
                validate(src, reread, ing_map, ing_ind, skin_map, skin_ind, removed, added)
            except MigrationError:
                tmp.unlink(missing_ok=True)
                raise
            os.replace(tmp, out_path)

        for p, h in protected_md5.items():
            require(md5_of(p) == h, f"file sumber berubah: {p}")
    except MigrationError as e:
        sys.exit(f"GAGAL: {e}\nTidak ada file yang ditulis.")

    print("Validasi LOLOS")
    for k in EXPECTED:
        if k in stats:
            print(f"  {k:<24} {stats[k]}")
    print(f"  {'keputusan CSV':<24} {stats['decisions']}")
    print(f"  triple v1.5 / v2          {len(src)} / {len(out)} "
          f"(dihapus {len(removed)}, ditambah {len(added)})")
    print(f"  file sumber tidak berubah (MD5): {', '.join(p.name for p in protected_md5)}")
    print("Mode --check-only: tidak ada file ditulis." if args.check_only
          else f"Ditulis: {out_path.relative_to(PROJECT_ROOT)}")


if __name__ == "__main__":
    main()
