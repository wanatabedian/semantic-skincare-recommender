<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
