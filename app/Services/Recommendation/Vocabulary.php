<?php

namespace App\Services\Recommendation;

use App\Services\SparqlService;

/**
 * Daftar nilai yang SAH untuk setiap kriteria kuis, diambil langsung dari ontologi.
 *
 * Inilah "whitelist" anti SPARQL injection (C4): input user hanya diterima kalau
 * nama lokalnya ada di daftar ini, lalu diubah menjadi IRI lengkap. Karena daftar
 * berasal dari ontologi (C5), menambah data di TTL otomatis menambah opsi yang sah.
 */
class Vocabulary
{
    /** kriteria => kelas ontologi */
    public const GROUPS = [
        'age' => 'AgeGroup',
        'skin' => 'SkinType',
        'category' => 'Category',
        'concern' => 'SkinConcern',
        'ingredient' => 'Ingredient',
    ];

    /** @var array<string, array<string, string>>|null kriteria => [namaLokal => label] */
    private ?array $terms = null;

    public function __construct(private SparqlService $sparql) {}

    /** @return array<string, array<string, string>> */
    public function all(): array
    {
        return $this->terms ??= $this->load();
    }

    /** @return array<string, string> namaLokal => label */
    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    public function has(string $group, string $local): bool
    {
        return isset($this->all()[$group][$local]);
    }

    public function label(string $group, string $local): string
    {
        return $this->all()[$group][$local] ?? self::humanize($local);
    }

    /** OilySkinType -> "Oily Skin Type" (cadangan kalau tidak ada rdfs:label). */
    public static function humanize(string $local): string
    {
        $text = preg_replace('/(Brand|Series|Category|Ingredient|SkinType)$/', '', $local) ?: $local;

        return trim((string) preg_replace('/(?<=[a-z])(?=[A-Z0-9])/', ' ', $text));
    }

    private function load(): array
    {
        $values = [];
        foreach (self::GROUPS as $group => $class) {
            $values[] = '("'.$group.'" '.$this->sparql->iri($class).')';
        }
        $values = implode(' ', $values);

        // Label diprioritaskan: rdfs:label@id -> rdfs:label tanpa bahasa -> label apa pun.
        // Subclass ikut dihitung (rdfs:subClassOf*) supaya ontologi v2 dengan
        // hierarki golongan bahan tetap terbaca tanpa reasoner.
        $rows = $this->sparql->select(<<<SPARQL
SELECT ?group ?term (SAMPLE(?lid) AS ?labelId) (SAMPLE(?lplain) AS ?labelPlain) (SAMPLE(?lany) AS ?labelAny)
WHERE {
  VALUES (?group ?cls) { {$values} }
  ?term a/rdfs:subClassOf* ?cls .
  FILTER(isIRI(?term))
  OPTIONAL { ?term rdfs:label ?lid FILTER(langMatches(lang(?lid), "id")) }
  OPTIONAL { ?term rdfs:label ?lplain FILTER(lang(?lplain) = "") }
  OPTIONAL { ?term rdfs:label ?lany }
}
GROUP BY ?group ?term
SPARQL);

        $terms = array_fill_keys(array_keys(self::GROUPS), []);
        $ns = $this->sparql->namespace();

        foreach ($rows as $row) {
            $iri = SparqlService::value($row, 'term') ?? '';
            $group = SparqlService::value($row, 'group') ?? '';

            // Hanya IRI di namespace ex: dan nama lokal yang aman yang masuk whitelist.
            if (! isset($terms[$group]) || ! str_starts_with($iri, $ns)) {
                continue;
            }
            $local = substr($iri, strlen($ns));
            if (! SparqlService::isSafeLocalName($local)) {
                continue;
            }

            $terms[$group][$local] = SparqlService::value($row, 'labelId')
                ?? SparqlService::value($row, 'labelPlain')
                ?? SparqlService::value($row, 'labelAny')
                ?? self::humanize($local);
        }

        foreach ($terms as &$items) {
            asort($items, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $terms;
    }
}
