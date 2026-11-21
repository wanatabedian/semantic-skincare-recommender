<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use JsonException;

class FusekiService
{
    /**
     * Mengambil namespace ontology dari config/services.php.
     * Jika belum diatur, gunakan namespace lama agar kompatibel.
     */
    private function namespace(): string
    {
        $namespace = config(
            'services.fuseki.namespace',
            'https://example.org/skincare-ontology#'
        );

        // Pastikan namespace diakhiri # atau /
        if (!str_ends_with($namespace, '#') && !str_ends_with($namespace, '/')) {
            $namespace .= '#';
        }

        return $namespace;
    }

    /**
     * Mengambil URL endpoint query Fuseki.
     */
    private function queryUrl(): string
    {
        return config(
            'services.fuseki.query_url',
            'http://localhost:3030/skincare/query'
        );
    }

    /**
     * Menjalankan SPARQL SELECT query ke Fuseki.
     *
     * @throws RuntimeException Jika Fuseki tidak dapat diakses
     */
    public function query(string $body): array
    {
        $namespace = $this->namespace();

        $query = 'PREFIX ex: <' . $namespace . ">
"
            . "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
"
            . $body;

        try {
            $client = new Client([
                'timeout' => (int) config('services.fuseki.timeout', 15),
                'connect_timeout' => (int) config('services.fuseki.connect_timeout', 5),
                'http_errors' => true,
            ]);

            $response = $client->post($this->queryUrl(), [
                'form_params' => [
                    'query' => $query,
                ],
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

            if (!isset($result['results']['bindings']) || !is_array($result['results']['bindings'])) {
                throw new RuntimeException(
                    'Format respons Fuseki tidak sesuai dengan SPARQL JSON Results.'
                );
            }

            return $result['results']['bindings'];

        } catch (GuzzleException $e) {
            report($e);

            throw new RuntimeException(
                'Tidak dapat terhubung ke Fuseki. Pastikan Fuseki aktif, endpoint benar, dan dataset tersedia.',
                0,
                $e
            );

        } catch (JsonException $e) {
            report($e);

            throw new RuntimeException(
                'Respons dari Fuseki tidak dapat dibaca sebagai JSON.',
                0,
                $e
            );

        } catch (RuntimeException $e) {
            report($e);

            throw $e;

        } catch (\Throwable $e) {
            report($e);

            throw new RuntimeException(
                'Terjadi kesalahan saat mengambil data dari Fuseki.',
                0,
                $e
            );
        }
    }

    /**
     * Mengambil seluruh pilihan yang tersedia dari ontology.
     *
     * Pilihan:
     * - age_groups
     * - skin_types
     * - categories
     * - concerns
     * - ingredients
     */
    public function options(): array
    {
        $rows = $this->query("
            SELECT DISTINCT
                ?age ?ageLabel
                ?skin ?skinLabel
                ?category ?categoryLabel
                ?concern ?concernLabel
                ?ingredient ?ingredientLabel
            WHERE {
                {
                    ?age a ex:AgeGroup ; rdfs:label ?ageLabel .
                }
                UNION
                {
                    ?skin a ex:SkinType ; rdfs:label ?skinLabel .
                }
                UNION
                {
                    ?category a ex:Category ; rdfs:label ?categoryLabel .
                }
                UNION
                {
                    ?concern a ex:SkinConcern ; rdfs:label ?concernLabel .
                }
                UNION
                {
                    ?ingredient a ex:Ingredient ; rdfs:label ?ingredientLabel .
                }
            }
            ORDER BY ?ageLabel ?skinLabel ?categoryLabel ?concernLabel ?ingredientLabel
        ");

        $options = [
            'age_groups' => [],
            'skin_types' => [],
            'categories' => [],
            'concerns' => [],
            'ingredients' => [],
        ];

        foreach ($rows as $row) {

            // Kelompok usia
            if (isset($row['age']['value'])) {
                $value = $this->termValue($row['age']);

                $options['age_groups'][] = [
                    'value' => $value,
                    'label' => $row['ageLabel']['value'] ?? $this->label($value),
                ];
            }

            // Jenis kulit
            if (isset($row['skin']['value'])) {
                $value = $this->termValue($row['skin']);

                $options['skin_types'][] = [
                    'value' => $value,
                    'label' => $row['skinLabel']['value'] ?? $this->label($value),
                ];
            }

            // Kategori produk
            if (isset($row['category']['value'])) {
                $value = $this->termValue($row['category']);

                $options['categories'][] = [
                    'value' => $value,
                    'label' => $row['categoryLabel']['value'] ?? $this->label($value),
                ];
            }

            // Masalah kulit
            if (isset($row['concern']['value'])) {
                $value = $this->termValue($row['concern']);

                $label = isset($row['concernLabel']['value'])
                    ? $row['concernLabel']['value']
                    : $this->label($value);

                $options['concerns'][] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }

            // Kandungan skincare
            if (isset($row['ingredient']['value'])) {
                $value = $this->termValue($row['ingredient']);

                $options['ingredients'][] = [
                    'value' => $value,
                    'label' => $row['ingredientLabel']['value'] ?? $this->label($value),
                ];
            }
        }

        // Hilangkan data duplikat dan urutkan berdasarkan label.
        foreach ($options as &$items) {
            $unique = [];

            foreach ($items as $item) {
                $unique[$item['value']] = $item;
            }

            $items = array_values($unique);

            usort($items, function ($a, $b) {
                return strcasecmp($a['label'], $b['label']);
            });
        }

        unset($items);

        return $options;
    }

    /**
     * Mengubah nilai binding SPARQL menjadi nilai yang digunakan aplikasi.
     *
     * Jika berupa URI, ambil bagian terakhir URI.
     * Jika berupa literal, pertahankan nilainya.
     */
    private function termValue(array $term): string
    {
        $value = $term['value'] ?? '';

        if (($term['type'] ?? '') === 'uri') {
            return $this->local($value);
        }

        return trim($value);
    }

    /**
     * Mengambil nama lokal dari sebuah IRI.
     *
     * Contoh:
     * https://example.org/skincare-ontology#OilySkin
     * menjadi OilySkin
     */
    public function local(string $iri): string
    {
        $parts = preg_split('/[#\/]/', rtrim($iri, '#/'));

        return end($parts) ?: $iri;
    }

    /**
     * Membuat label yang lebih mudah dibaca.
     *
     * Contoh:
     * OilySkin       -> Oily Skin
     * SkinCategory   -> Skin
     * MoisturizerCategory -> Moisturizer
     */
    public function label(string $iri): string
    {
        $label = $this->local($iri);

        $label = preg_replace(
            '/(Brand|Series|Category)$/',
            '',
            $label
        );

        $label = preg_replace(
            '/(?<=[a-z])(?=[A-Z])/',
            ' ',
            $label
        );

        return trim($label);
    }
}
