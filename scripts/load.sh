#!/usr/bin/env bash
# =====================================================================
# load.sh — memuat ontology/aphrodite-v2.ttl ke Apache Jena Fuseki
#
# Pemakaian (dari folder mana saja):
#   scripts/load.sh                              # dataset baru / kosong
#   CONFIRM_OVERWRITE=yes scripts/load.sh        # dataset sudah berisi data
#
# PERINGATAN: di laptop pengembang, dataset "aphrodite" yang lama masih
# memakai OWLFBRuleReasoner. Jangan jalankan skrip ini terhadap dataset
# tersebut sebelum Fase 4 disetujui (lihat fuseki/README.md).
#
# Variabel lingkungan opsional:
#   CONFIRM_OVERWRITE wajib "yes" jika dataset sudah berisi data
#                     (tidak ada nilai default; tanpa ini skrip berhenti)
#   FUSEKI_URL        default http://127.0.0.1:3030
#   DATASET           default aphrodite (hanya huruf, angka, _ dan -)
#   FUSEKI_CONFIG     default <repo>/fuseki/aphrodite-config.ttl
#                     (hanya dipakai jika dataset BELUM ada)
#   TTL_FILE          default <repo>/ontology/aphrodite-v2.ttl
#   BACKUP_DIR        default ~/aphrodite-fuseki-backups (harus di luar repo)
#   EXPECTED_PRODUCTS default 88
#   PYTHON            default <repo>/.venv/bin/python, lalu python3
#                     (wajib punya paket rdflib)
#
# Yang dilakukan skrip ini:
#   1. Memeriksa input, curl, Python+rdflib, file ontologi, dan Fuseki.
#   2. Memeriksa dataset; membuatnya dari FUSEKI_CONFIG jika belum ada.
#      Konfigurasi dataset yang SUDAH ada tidak pernah diubah.
#   3. Jika dataset berisi data: berhenti kecuali CONFIRM_OVERWRITE=yes.
#   4. Jika dikonfirmasi: backup logis (N-Quads) ke BACKUP_DIR, diverifikasi
#      dengan parser RDF + checksum SHA-256. Gagal verifikasi = berhenti.
#   5. HTTP PUT ke default graph (?default). PUT hanya mengganti DEFAULT
#      GRAPH; named graph tidak disentuh. Dijalankan berulang tidak
#      menggandakan triple.
#   6. Verifikasi: jumlah triple = jumlah triple file (parser RDF),
#      jumlah produk, dan penanda penting ontologi v2.
#
# Skrip ini TIDAK PERNAH menghapus dataset dan tidak mengubah keamanan.
# Kompatibel dengan Bash 3.2 (bawaan macOS).
# =====================================================================
set -euo pipefail

# ---------- Lokasi repo berdasarkan lokasi skrip ----------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
REPO_DIR="$(cd "$SCRIPT_DIR/.." && pwd -P)"

FUSEKI_URL="${FUSEKI_URL:-http://127.0.0.1:3030}"
FUSEKI_URL="${FUSEKI_URL%/}"
DATASET="${DATASET:-aphrodite}"
FUSEKI_CONFIG="${FUSEKI_CONFIG:-$REPO_DIR/fuseki/aphrodite-config.ttl}"
TTL_FILE="${TTL_FILE:-$REPO_DIR/ontology/aphrodite-v2.ttl}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/aphrodite-fuseki-backups}"
EXPECTED_PRODUCTS="${EXPECTED_PRODUCTS:-88}"
CONFIRM_OVERWRITE="${CONFIRM_OVERWRITE:-}"

EX="https://example.org/skincare-ontology#"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

info() { printf '==> %s\n' "$*"; }
ok()   { printf '    OK  %s\n' "$*"; }
warn() { printf '    PERINGATAN: %s\n' "$*" >&2; }
die()  { printf '\nGAGAL: %s\n' "$*" >&2; exit 1; }

# ---------- 0. Validasi input ----------
DATASET_RE='^[A-Za-z0-9_-]+$'
[[ "$DATASET" =~ $DATASET_RE ]] \
    || die "Nama DATASET tidak valid: '$DATASET'. Hanya boleh huruf, angka, garis bawah (_), dan tanda minus (-)."
NUM_RE='^[0-9]+$'
[[ "$EXPECTED_PRODUCTS" =~ $NUM_RE ]] || die "EXPECTED_PRODUCTS harus berupa angka: '$EXPECTED_PRODUCTS'"

QUERY_URL="$FUSEKI_URL/$DATASET/query"
DATA_URL="$FUSEKI_URL/$DATASET/data"
GET_URL="$FUSEKI_URL/$DATASET/get"
ADMIN_URL="$FUSEKI_URL/\$"

# ---------- Fungsi bantu ----------
# sparql_count "<query yang menghasilkan ?n>"  -> mencetak angka
sparql_count() {
    local out
    if ! out="$(curl -sS -f -G "$QUERY_URL" --data-urlencode "query=$1" \
                    -H 'Accept: text/csv' 2>&1)"; then
        die "Query ke $QUERY_URL gagal: $out"
    fi
    out="$(printf '%s\n' "$out" | tail -n 1 | tr -d '\r')"
    [[ "$out" =~ $NUM_RE ]] || die "Hasil COUNT tidak berupa angka: '$out'"
    printf '%s' "$out"
}

# sparql_ask "<ASK query>"  -> exit 0 jika true, 1 jika false
sparql_ask() {
    local out
    if ! out="$(curl -sS -f -G "$QUERY_URL" --data-urlencode "query=$1" \
                    -H 'Accept: application/sparql-results+json' 2>&1)"; then
        die "Query ASK ke $QUERY_URL gagal: $out"
    fi
    printf '%s' "$out" | grep -Eq '"boolean"[[:space:]]*:[[:space:]]*true'
}

# realpath portabel (Python sudah diwajibkan)
real_path() {
    "$PYTHON" -I -c 'import os,sys; print(os.path.realpath(os.path.expanduser(sys.argv[1])))' "$1"
}

# sha256_file <file> <output>  -> berhenti jika tidak ada alat checksum
sha256_file() {
    if command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$1" > "$2" || die "Gagal menghitung SHA-256 untuk $1"
    elif command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" > "$2" || die "Gagal menghitung SHA-256 untuk $1"
    else
        die "Tidak ada alat checksum (shasum atau sha256sum). Backup tidak bisa dilengkapi checksum. Pemuatan DIBATALKAN."
    fi
}

# ---------- 1. Pemeriksaan awal ----------
info "Pemeriksaan awal"
command -v curl >/dev/null 2>&1 || die "Perintah 'curl' tidak ditemukan. Pasang curl terlebih dahulu."
ok "curl tersedia"

if [[ -z "${PYTHON:-}" ]]; then
    if [[ -x "$REPO_DIR/.venv/bin/python" ]]; then PYTHON="$REPO_DIR/.venv/bin/python"
    else PYTHON="$(command -v python3 || true)"; fi
fi
[[ -n "${PYTHON:-}" ]] || die "Python 3 tidak ditemukan. Dibutuhkan untuk verifikasi RDF."
"$PYTHON" -I -c 'import rdflib' 2>/dev/null \
    || die "Paket rdflib tidak tersedia di $PYTHON. Pasang dengan: $PYTHON -m pip install rdflib"
ok "Parser RDF: rdflib ($PYTHON)"

[[ -f "$TTL_FILE" ]] || die "File ontologi tidak ditemukan: $TTL_FILE"
TTL_REAL="$(real_path "$TTL_FILE")"
ok "File ontologi: $TTL_REAL"

# Info Git hanya jika file ontologi berada di dalam repo
case "$TTL_REAL" in
    "$REPO_DIR"/*)
        if command -v git >/dev/null 2>&1 && git -C "$REPO_DIR" rev-parse --git-dir >/dev/null 2>&1; then
            rel="${TTL_REAL#"$REPO_DIR"/}"
            ok "Commit terakhir file: $(git -C "$REPO_DIR" log -1 --format='%h %s' -- "$rel" 2>/dev/null || echo '-')"
            if ! git -C "$REPO_DIR" diff --quiet HEAD -- "$rel" 2>/dev/null; then
                warn "File ontologi berbeda dari commit HEAD (ada perubahan yang belum di-commit)."
            fi
        fi
        ;;
    *)
        ok "File ontologi berada di luar repo; pemeriksaan Git dilewati"
        ;;
esac

# Jumlah triple yang diharapkan, dihitung dengan parser RDF
if ! EXPECTED_TRIPLES="$("$PYTHON" -I - "$TTL_REAL" <<'PY' 2>"$TMP_DIR/py.err"
import sys
from rdflib import Graph
g = Graph()
g.parse(sys.argv[1], format="turtle")
print(len(g))
PY
)"; then
    die "File ontologi tidak bisa diparse sebagai Turtle: $(tail -n 3 "$TMP_DIR/py.err")"
fi
ok "File ontologi valid; jumlah triple menurut parser RDF: $EXPECTED_TRIPLES"

# ---------- 2. Status Fuseki ----------
info "Memeriksa Fuseki di $FUSEKI_URL"
if ! curl -sS -f -m 5 -o /dev/null "$ADMIN_URL/ping" 2>"$TMP_DIR/curl.err"; then
    die "Fuseki tidak aktif atau tidak bisa dihubungi di $FUSEKI_URL ($(cat "$TMP_DIR/curl.err")).
       Jalankan Fuseki dari folder instalasinya:  cd <folder-fuseki> && ./fuseki-server"
fi
ok "Fuseki aktif"

# ---------- 3. Dataset ----------
info "Memeriksa dataset '$DATASET'"
code="$(curl -sS -o "$TMP_DIR/ds.json" -w '%{http_code}' "$ADMIN_URL/datasets/$DATASET" || true)"
case "$code" in
    200)
        ok "Dataset '$DATASET' sudah ada; konfigurasinya TIDAK diubah"
        ;;
    404)
        info "Dataset '$DATASET' belum ada; membuat dari $FUSEKI_CONFIG"
        [[ -f "$FUSEKI_CONFIG" ]] || die "File konfigurasi tidak ditemukan: $FUSEKI_CONFIG"
        grep -Eq "fuseki:name[[:space:]]+\"$DATASET\"" "$FUSEKI_CONFIG" \
            || die "fuseki:name di $FUSEKI_CONFIG tidak sama dengan DATASET='$DATASET'."
        ccode="$(curl -sS -o "$TMP_DIR/create.txt" -w '%{http_code}' -X POST \
                    -H 'Content-Type: text/turtle; charset=utf-8' \
                    --data-binary @"$FUSEKI_CONFIG" "$ADMIN_URL/datasets" || true)"
        [[ "$ccode" == "200" || "$ccode" == "201" ]] \
            || die "Gagal membuat dataset (HTTP $ccode): $(head -c 400 "$TMP_DIR/create.txt")"
        ok "Dataset '$DATASET' dibuat"
        ;;
    403)
        die "Admin API Fuseki menolak akses (HTTP 403). Admin API hanya bisa dari localhost;
       gunakan FUSEKI_URL=http://127.0.0.1:3030 dan jalankan skrip di laptop yang menjalankan Fuseki."
        ;;
    *)
        die "Tidak bisa memeriksa dataset (HTTP $code): $(head -c 300 "$TMP_DIR/ds.json" 2>/dev/null)"
        ;;
esac

# ---------- 4. Pengaman: dataset berisi data butuh konfirmasi eksplisit ----------
TOTAL_BEFORE="$(sparql_count 'SELECT (COUNT(*) AS ?n) WHERE { { ?s ?p ?o } UNION { GRAPH ?g { ?s ?p ?o } } }')"
info "Isi dataset saat ini: $TOTAL_BEFORE triple/quad"

BACKUP_FILE=""
if [[ "$TOTAL_BEFORE" -gt 0 ]]; then
    if [[ "$CONFIRM_OVERWRITE" != "yes" ]]; then
        die "Dataset '$DATASET' sudah berisi $TOTAL_BEFORE triple/quad. Tidak ada backup maupun pemuatan yang dilakukan.
       Pemuatan akan MENGGANTI default graph dataset ini, jadi dibutuhkan konfirmasi eksplisit.
       Jika memang ingin menimpa (setelah memastikan dataset ini boleh diganti):
           CONFIRM_OVERWRITE=yes DATASET=$DATASET scripts/load.sh
       Catatan: dataset 'aphrodite' lama yang masih memakai reasoner TIDAK boleh ditimpa
       sebelum Fase 4 disetujui (lihat fuseki/README.md)."
    fi
    ok "CONFIRM_OVERWRITE=yes diterima; lanjut ke backup"

    # ---------- 5. Backup logis ----------
    # Cek lokasi SEBELUM membuat folder, supaya tidak ada folder yang tercipta di dalam repo
    BACKUP_REAL="$(real_path "$BACKUP_DIR")"
    case "$BACKUP_REAL/" in
        "$REPO_DIR"/*) die "BACKUP_DIR ($BACKUP_REAL) berada di dalam repo. Pilih folder di luar repo agar backup tidak ikut ter-commit. Pemuatan DIBATALKAN." ;;
    esac
    command -v shasum >/dev/null 2>&1 || command -v sha256sum >/dev/null 2>&1 \
        || die "Tidak ada alat checksum (shasum atau sha256sum). Pemuatan DIBATALKAN sebelum backup."
    mkdir -p "$BACKUP_REAL" || die "Tidak bisa membuat folder backup: $BACKUP_REAL"
    BACKUP_FILE="$BACKUP_REAL/$(date +%Y%m%d-%H%M%S)-$$-$DATASET-logical.nq"
    [[ ! -e "$BACKUP_FILE" ]] || die "File backup sudah ada (tidak akan ditimpa): $BACKUP_FILE"
    info "Backup logis ke $BACKUP_FILE"

    if bcode="$(curl -sS -o "$BACKUP_FILE" -w '%{http_code}' \
                    -H 'Accept: application/n-quads' "$GET_URL" 2>"$TMP_DIR/curl.err")"; then
        brc=0
    else
        brc=$?
    fi
    [[ "$brc" == "0" ]] \
        || die "Unduhan backup gagal (curl exit $brc: $(cat "$TMP_DIR/curl.err")). Pemuatan DIBATALKAN."
    [[ "$bcode" == "200" ]] || die "Backup gagal (HTTP $bcode dari $GET_URL). Pemuatan DIBATALKAN."

    if ! BACKUP_COUNT="$("$PYTHON" -I - "$BACKUP_FILE" <<'PY' 2>"$TMP_DIR/py.err"
import sys
from rdflib import Dataset
ds = Dataset()
ds.parse(sys.argv[1], format="nquads")
print(sum(1 for _ in ds.quads((None, None, None, None))))
PY
)"; then
        die "Backup tidak bisa diparse sebagai N-Quads: $(tail -n 3 "$TMP_DIR/py.err"). Pemuatan DIBATALKAN."
    fi
    [[ "$BACKUP_COUNT" == "$TOTAL_BEFORE" ]] \
        || die "Backup tidak lengkap: parser membaca $BACKUP_COUNT, server berisi $TOTAL_BEFORE. Pemuatan DIBATALKAN."
    sha256_file "$BACKUP_FILE" "$BACKUP_FILE.sha256"
    ok "Backup terverifikasi parser RDF: $BACKUP_COUNT quad; checksum di $(basename "$BACKUP_FILE").sha256"
    ok "Catatan: backup logis = triple yang terlihat lewat query (termasuk hasil inferensi jika ada reasoner), BUKAN salinan fisik database"
else
    ok "Dataset kosong; konfirmasi dan backup tidak diperlukan"
fi

# ---------- 6. Pemuatan (HTTP PUT ke default graph) ----------
info "Memuat $(basename "$TTL_REAL") ke DEFAULT GRAPH (HTTP PUT ?default; named graph tidak disentuh)"
pcode="$(curl -sS -o "$TMP_DIR/put.txt" -w '%{http_code}' -X PUT \
            -H 'Content-Type: text/turtle; charset=utf-8' \
            --data-binary @"$TTL_REAL" "$DATA_URL?default" || true)"
case "$pcode" in
    200|201|204) ok "Pemuatan diterima Fuseki (HTTP $pcode)" ;;
    *) die "Pemuatan ditolak (HTTP $pcode) oleh $DATA_URL: $(head -c 400 "$TMP_DIR/put.txt")" ;;
esac

# ---------- 7. Verifikasi ----------
info "Verifikasi hasil"
FAIL=0
# check "<pesan>" <perintah...>  -> lolos jika perintah sukses (aman dengan set -e)
check()     { local msg="$1"; shift; if "$@"; then ok "$msg"; else printf '    X   %s\n' "$msg" >&2; FAIL=1; fi; }
check_not() { local msg="$1"; shift; if "$@"; then printf '    X   %s\n' "$msg" >&2; FAIL=1; else ok "$msg"; fi; }

TRIPLES_AFTER="$(sparql_count 'SELECT (COUNT(*) AS ?n) WHERE { ?s ?p ?o }')"
if [[ "$TRIPLES_AFTER" == "$EXPECTED_TRIPLES" ]]; then
    ok "Jumlah triple default graph: $TRIPLES_AFTER = jumlah triple file ($EXPECTED_TRIPLES)"
elif [[ "$TRIPLES_AFTER" -gt "$EXPECTED_TRIPLES" ]]; then
    printf '    X   Jumlah triple %s > %s. Kemungkinan dataset memakai reasoner/inferensi.\n' \
        "$TRIPLES_AFTER" "$EXPECTED_TRIPLES" >&2; FAIL=1
else
    printf '    X   Jumlah triple %s < %s. Sebagian data tidak termuat.\n' "$TRIPLES_AFTER" "$EXPECTED_TRIPLES" >&2; FAIL=1
fi

PRODUCTS="$(sparql_count "SELECT (COUNT(DISTINCT ?p) AS ?n) WHERE { ?p a <${EX}Product> }")"
check "Jumlah produk: $PRODUCTS (harus $EXPECTED_PRODUCTS)" [ "$PRODUCTS" = "$EXPECTED_PRODUCTS" ]

check "ex:ActiveIngredient ada sebagai owl:Class" \
    sparql_ask "ASK { <${EX}ActiveIngredient> a <http://www.w3.org/2002/07/owl#Class> }"
check "ex:routineStep terisi pada kategori" \
    sparql_ask "ASK { ?c <${EX}routineStep> ?n }"
check "ex:NiacinamideIngredient ex:targetsConcern ex:DullnessBrightening" \
    sparql_ask "ASK { <${EX}NiacinamideIngredient> <${EX}targetsConcern> <${EX}DullnessBrightening> }"
check_not "Tidak ada relasi ex:LAB-0069 ex:suitableForAgeGroup ex:Teen" \
    sparql_ask "ASK { <${EX}LAB-0069> <${EX}suitableForAgeGroup> <${EX}Teen> }"

if [[ "$FAIL" != "0" ]]; then
    if [[ -n "$BACKUP_FILE" ]]; then
        die "Verifikasi tidak lolos. Data sudah dimuat, tetapi isinya tidak sesuai harapan.
       Untuk memulihkan isi sebelumnya dari backup logis (mengganti SELURUH isi dataset,
       termasuk named graph, dengan isi file backup):
           curl -X PUT -H 'Content-Type: application/n-quads' --data-binary @\"$BACKUP_FILE\" \"$DATA_URL\""
    else
        die "Verifikasi tidak lolos. Dataset sebelumnya kosong, jadi tidak ada backup untuk dipulihkan."
    fi
fi

printf '\nSELESAI: dataset "%s" berisi %s triple, %s produk.\n' "$DATASET" "$TRIPLES_AFTER" "$PRODUCTS"
printf 'Catatan: validasi SHACL tidak dijalankan oleh skrip ini (lihat fuseki/README.md).\n'
