<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Satu-satunya pintu untuk mengirim query SPARQL ke Fuseki.
 *
 * - Prefix (ex:, rdf:, rdfs:, owl:, xsd:, skos:, schema:) didefinisikan sekali di sini.
 * - Hasil query di-cache (default 10 menit, lihat config/recommendation.php).
 * - Kalau Fuseki mati / query salah, yang dilempar adalah SparqlException
 *   dengan pesan berbahasa Indonesia yang aman ditampilkan ke user.
 * - Helper iri() dan literal() dipakai untuk menyusun query dengan aman
 *   (tidak pernah menyambung teks user mentah ke query).
 */
class SparqlService
{
    public const PREFIXES = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl' => 'http://www.w3.org/2002/07/owl#',
        'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        'skos' => 'http://www.w3.org/2004/02/skos/core#',
        'schema' => 'https://schema.org/',
    ];

    private ?Client $client = null;

    public function __construct(?Client $client = null)
    {
        $this->client = $client;
    }

    /** Namespace ontologi Aphrodite (ex:). */
    public function namespace(): string
    {
        $ns = (string) config('services.fuseki.namespace', 'https://example.org/skincare-ontology#');

        return str_ends_with($ns, '#') || str_ends_with($ns, '/') ? $ns : $ns.'#';
    }

    /** Blok PREFIX yang ditempel otomatis di depan setiap query. */
    public function prefixes(): string
    {
        $lines = ['PREFIX ex: <'.$this->namespace().'>'];
        foreach (self::PREFIXES as $prefix => $uri) {
            $lines[] = "PREFIX {$prefix}: <{$uri}>";
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Jalankan SELECT dan kembalikan array bindings (format SPARQL JSON Results).
     *
     * @throws SparqlException
     */
    public function select(string $body, bool $useCache = true): array
    {
        $result = $this->run($body, $useCache);

        if (! isset($result['results']['bindings']) || ! is_array($result['results']['bindings'])) {
            throw new SparqlException('Format respons Fuseki tidak sesuai (bukan hasil SELECT).');
        }

        return $result['results']['bindings'];
    }

    /**
     * Jalankan ASK dan kembalikan boolean.
     *
     * @throws SparqlException
     */
    public function ask(string $body, bool $useCache = true): bool
    {
        $result = $this->run($body, $useCache);

        if (! array_key_exists('boolean', $result)) {
            throw new SparqlException('Format respons Fuseki tidak sesuai (bukan hasil ASK).');
        }

        return (bool) $result['boolean'];
    }

    /** IRI lengkap (dalam kurung sudut) untuk nama lokal di namespace ex:. */
    public function iri(string $local): string
    {
        if (! self::isSafeLocalName($local)) {
            throw new InvalidArgumentException("Nama lokal tidak valid untuk IRI: {$local}");
        }

        return '<'.$this->namespace().$local.'>';
    }

    /** Literal string yang sudah di-escape untuk disisipkan ke query. */
    public static function literal(string $value): string
    {
        $escaped = strtr($value, [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
        ]);

        return '"'.$escaped.'"';
    }

    /** Nama lokal hanya boleh huruf, angka, garis bawah, dan tanda hubung. */
    public static function isSafeLocalName(string $local): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_][A-Za-z0-9_\-]*$/', $local);
    }

    /** Ambil nama lokal dari IRI: https://...#OilySkinType -> OilySkinType */
    public static function local(string $iri): string
    {
        $parts = preg_split('/[#\/]/', rtrim($iri, '#/'));

        return end($parts) ?: $iri;
    }

    /** Nilai mentah dari satu binding (atau null kalau tidak ada). */
    public static function value(array $row, string $var): ?string
    {
        return $row[$var]['value'] ?? null;
    }

    private function run(string $body, bool $useCache): array
    {
        $query = $this->prefixes().$body;
        $ttl = (int) config('recommendation.cache_ttl', 600);
        $key = 'sparql:'.sha1($this->queryUrl().'|'.$query);

        if ($useCache && $ttl > 0) {
            $cached = $this->cacheGet($key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = $this->send($query);

        if ($useCache && $ttl > 0) {
            $this->cachePut($key, $result, $ttl);
        }

        return $result;
    }

    private function send(string $query): array
    {
        try {
            $response = $this->client()->post($this->queryUrl(), [
                'form_params' => ['query' => $query],
                'headers' => ['Accept' => 'application/sparql-results+json'],
            ]);

            return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (ConnectException $e) {
            report($e);
            throw new SparqlException(
                'Server Fuseki tidak dapat dihubungi. Pastikan Fuseki sudah dinyalakan dan dataset "aphrodite" sudah dimuat.',
                0,
                $e
            );
        } catch (RequestException $e) {
            report($e);
            $status = $e->getResponse()?->getStatusCode();
            // Fuseki membalas 404/405 kalau dataset di URL belum dibuat.
            $message = in_array($status, [404, 405], true)
                ? 'Dataset Fuseki tidak ditemukan. Buat dataset "aphrodite" dan muat file ontology/aphrodite-v2.ttl, atau periksa FUSEKI_QUERY_URL di file .env.'
                : 'Query SPARQL ditolak oleh Fuseki'.($status ? " (HTTP {$status})" : '').'.';
            throw new SparqlException($message, 0, $e);
        } catch (GuzzleException $e) {
            report($e);
            throw new SparqlException('Gagal mengirim query ke Fuseki.', 0, $e);
        } catch (JsonException $e) {
            report($e);
            throw new SparqlException('Respons dari Fuseki tidak dapat dibaca sebagai JSON.', 0, $e);
        }
    }

    private function client(): Client
    {
        return $this->client ??= new Client([
            'timeout' => (int) config('services.fuseki.timeout', 15),
            'connect_timeout' => (int) config('services.fuseki.connect_timeout', 5),
            'http_errors' => true,
        ]);
    }

    private function queryUrl(): string
    {
        return (string) config('services.fuseki.query_url', 'http://127.0.0.1:3030/aphrodite/query');
    }

    /** Cache tidak boleh membuat aplikasi error (mis. tabel cache belum dimigrasi). */
    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable) {
            return null;
        }
    }

    private function cachePut(string $key, array $value, int $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable) {
            // Abaikan: tanpa cache aplikasi tetap berjalan.
        }
    }
}
