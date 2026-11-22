<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use JsonException;
use RuntimeException;
use Throwable;

class CleanOntologyService
{
    private ?Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client;
    }

    /**
     * Run a SPARQL SELECT query against the read-only Clean dataset.
     *
     * @throws RuntimeException If the endpoint is unavailable or returns an invalid response.
     */
    public function query(string $body): array
    {
        $query = 'PREFIX ex: <'.$this->namespace().">\n"
            ."PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            .$body;

        try {
            $response = $this->client()->post($this->queryUrl(), [
                'form_params' => ['query' => $query],
                'headers' => [
                    'Accept' => 'application/sparql-results+json',
                ],
            ]);

            $result = json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (
                ! is_array($result)
                || ! isset($result['results'])
                || ! is_array($result['results'])
                || ! isset($result['results']['bindings'])
                || ! is_array($result['results']['bindings'])
            ) {
                throw new RuntimeException(
                    'Format respons Fuseki Clean tidak sesuai dengan SPARQL JSON Results.'
                );
            }

            return $result['results']['bindings'];
        } catch (GuzzleException $e) {
            report($e);

            throw new RuntimeException(
                'Tidak dapat terhubung ke endpoint Fuseki Clean. Pastikan dataset aphrodite-clean tersedia.',
                0,
                $e
            );
        } catch (JsonException $e) {
            report($e);

            throw new RuntimeException(
                'Respons dari Fuseki Clean tidak dapat dibaca sebagai JSON.',
                0,
                $e
            );
        } catch (RuntimeException $e) {
            report($e);

            throw $e;
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException(
                'Terjadi kesalahan saat mengambil data dari Fuseki Clean.',
                0,
                $e
            );
        }
    }

    /**
     * Run a SPARQL Playground query (SELECT or ASK) with its own total timeout.
     *
     * Terpisah dari query() supaya timeout dan parser hasil fitur lain tidak
     * berubah. Query harus sudah divalidasi oleh SparqlPlaygroundGuard.
     *
     * @param  'select'|'ask'  $type
     * @return array{type: 'select', vars: list<string>, bindings: array}|array{type: 'ask', boolean: bool}
     *
     * @throws RuntimeException Dengan pesan yang aman ditampilkan ke pengguna.
     */
    public function playground(string $body, string $type, float $timeout): array
    {
        $query = 'PREFIX ex: <'.$this->namespace().">\n"
            ."PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n"
            .$body;

        try {
            $response = $this->client()->post($this->queryUrl(), [
                'form_params' => ['query' => $query],
                'headers' => ['Accept' => 'application/sparql-results+json'],
                // 'timeout' membatasi keseluruhan request, bukan hanya koneksi awal.
                'timeout' => $timeout,
                'connect_timeout' => min($timeout, (float) config('services.fuseki.connect_timeout', 5)),
            ]);
        } catch (TransferException $e) {
            throw $this->playgroundTransferError($e, $timeout);
        }

        try {
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            report($e);

            throw new RuntimeException('Respons dari Fuseki Clean tidak dapat dibaca sebagai JSON.', 0, $e);
        }

        if ($type === 'ask') {
            if (! is_array($result) || ! array_key_exists('boolean', $result) || ! is_bool($result['boolean'])) {
                throw new RuntimeException('Format respons ASK dari Fuseki Clean tidak sesuai (nilai boolean tidak ditemukan).');
            }

            return ['type' => 'ask', 'boolean' => $result['boolean']];
        }

        if (! is_array($result) || ! isset($result['results']['bindings']) || ! is_array($result['results']['bindings'])) {
            throw new RuntimeException('Format respons SELECT dari Fuseki Clean tidak sesuai dengan SPARQL JSON Results.');
        }

        $vars = $result['head']['vars'] ?? [];

        return [
            'type' => 'select',
            'vars' => is_array($vars) ? array_values(array_filter($vars, 'is_string')) : [],
            'bindings' => $result['results']['bindings'],
        ];
    }

    private function playgroundTransferError(TransferException $e, float $timeout): RuntimeException
    {
        $context = $e instanceof RequestException || $e instanceof ConnectException ? $e->getHandlerContext() : [];
        $timedOut = ($context['errno'] ?? null) === 28 || stripos($e->getMessage(), 'timed out') !== false;

        if ($timedOut) {
            return new RuntimeException(
                'Query melebihi batas waktu '.rtrim(rtrim(number_format($timeout, 1, '.', ''), '0'), '.').' detik dan dihentikan. Sederhanakan query atau perkecil LIMIT.',
                0,
                $e
            );
        }

        // Fuseki membalas 400 untuk query yang salah sintaks: tampilkan ringkasannya.
        if ($e instanceof RequestException && $e->hasResponse() && $e->getResponse()->getStatusCode() < 500) {
            $detail = trim(strtok((string) $e->getResponse()->getBody(), "\n") ?: '');
            $detail = mb_substr($detail, 0, 300);

            return new RuntimeException('Query ditolak oleh Fuseki'.($detail !== '' ? ': '.$detail : '.'), 0, $e);
        }

        report($e);

        return new RuntimeException(
            'Tidak dapat terhubung ke endpoint Fuseki Clean. Pastikan dataset aphrodite-clean tersedia.',
            0,
            $e
        );
    }

    /**
     * Get the ontology namespace, keeping the existing Fuseki configuration.
     */
    private function namespace(): string
    {
        $namespace = (string) config(
            'services.fuseki.namespace',
            'https://example.org/skincare-ontology#'
        );

        if (! str_ends_with($namespace, '#') && ! str_ends_with($namespace, '/')) {
            $namespace .= '#';
        }

        return $namespace;
    }

    private function queryUrl(): string
    {
        return (string) config(
            'services.fuseki.clean_query_url',
            'http://127.0.0.1:3030/aphrodite-clean/query'
        );
    }

    private function client(): Client
    {
        return $this->client ??= new Client([
            'timeout' => (int) config('services.fuseki.timeout', 15),
            'connect_timeout' => (int) config('services.fuseki.connect_timeout', 5),
            'http_errors' => true,
        ]);
    }

    public function local(string $iri): string
    {
        $parts = preg_split('/[#\/]/', rtrim($iri, '#/'));

        return end($parts) ?: $iri;
    }

    public function label(string $iri): string
    {
        $label = $this->local($iri);

        $label = preg_replace('/(Brand|Series|Category)$/', '', $label);
        $label = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $label);

        return trim($label);
    }
}
