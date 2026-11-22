<?php

namespace App\Services\Ontology;

use InvalidArgumentException;

/**
 * Validasi query SPARQL Playground (Ontology Explorer, Orang 1).
 *
 * Aturan:
 *  - hanya SELECT dan ASK;
 *  - operasi update / akses endpoint luar ditolak;
 *  - SELECT tanpa LIMIT di tingkat terluar diberi LIMIT 200;
 *  - LIMIT terluar di atas 200 ditolak.
 *
 * Sebelum diperiksa, string literal, IRI, dan komentar disamarkan supaya
 * kata seperti "Insert" di dalam nama produk tidak salah terdeteksi.
 */
final class SparqlPlaygroundGuard
{
    public const MAX_RESULTS = 200;

    public const TIMEOUT_SECONDS = 5.0;

    public const MAX_LENGTH = 5000;

    /** Kata kunci update / federasi / dataset yang tidak boleh muncul. */
    private const FORBIDDEN = [
        'INSERT', 'DELETE', 'UPDATE', 'LOAD', 'CLEAR', 'CREATE', 'DROP',
        'MOVE', 'COPY', 'ADD', 'SERVICE', 'WITH', 'USING', 'FROM',
    ];

    /**
     * @return array{type: 'select'|'ask', query: string, limitApplied: bool}
     *
     * @throws InvalidArgumentException Pesan aman untuk ditampilkan ke pengguna.
     */
    public function prepare(?string $query): array
    {
        $query = trim((string) $query);

        if (mb_strlen($query) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Query terlalu panjang (maksimal '.self::MAX_LENGTH.' karakter).');
        }

        $masked = $this->mask($query);

        if (trim($masked) === '') {
            throw new InvalidArgumentException('Query tidak boleh kosong.');
        }

        $type = $this->queryType($masked);

        if (preg_match('/(?<![\w:?$-])('.implode('|', self::FORBIDDEN).')(?![\w:-])/i', $masked, $m)) {
            throw new InvalidArgumentException('Operasi '.strtoupper($m[1]).' tidak diizinkan. Playground hanya menerima query SELECT dan ASK.');
        }

        $limitApplied = false;

        if ($type === 'select') {
            $topLevel = $this->topLevel($masked);
            preg_match_all('/(?<![\w:?$-])LIMIT\s+(\d+)/i', $topLevel, $limits);

            foreach ($limits[1] as $limit) {
                if ((int) $limit > self::MAX_RESULTS) {
                    throw new InvalidArgumentException('LIMIT maksimal '.self::MAX_RESULTS.' (diminta: '.(int) $limit.').');
                }
            }

            if ($limits[1] === []) {
                if (preg_match('/(?<![\w:?$-])VALUES(?![\w:-])/i', $topLevel)) {
                    throw new InvalidArgumentException('Query dengan VALUES di akhir wajib mencantumkan LIMIT (maksimal '.self::MAX_RESULTS.') secara eksplisit.');
                }

                $query .= "\nLIMIT ".self::MAX_RESULTS;
                $limitApplied = true;
            }
        }

        return ['type' => $type, 'query' => $query, 'limitApplied' => $limitApplied];
    }

    /** Samarkan string literal, IRI, dan komentar tanpa mengubah struktur kurung. */
    public function mask(string $query): string
    {
        return (string) preg_replace_callback(
            '/"""[\s\S]*?"""|\'\'\'[\s\S]*?\'\'\'|"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'|<[^<>"{}|^`\\\\\x00-\x20]*>|#[^\n]*/',
            fn (array $m) => match ($m[0][0]) {
                '#' => ' ',
                '<' => '<>',
                default => '""',
            },
            $query
        );
    }

    /** Ambil kata kunci pertama setelah prolog BASE/PREFIX. */
    private function queryType(string $masked): string
    {
        $prologue = '(?:\s*(?:BASE\s*<>|PREFIX\s+(?:[A-Za-z][\w.-]*)?:\s*<>))*';

        if (! preg_match('/^'.$prologue.'\s*([A-Za-z]+)/i', $masked, $m)) {
            throw new InvalidArgumentException('Query tidak dikenali. Mulai dengan SELECT atau ASK (boleh didahului PREFIX).');
        }

        $keyword = strtoupper($m[1]);

        return match ($keyword) {
            'SELECT' => 'select',
            'ASK' => 'ask',
            default => throw new InvalidArgumentException(
                in_array($keyword, self::FORBIDDEN, true)
                    ? "Operasi {$keyword} tidak diizinkan. Playground hanya menerima query SELECT dan ASK."
                    : "Jenis query {$keyword} tidak didukung. Playground hanya menerima query SELECT dan ASK."
            ),
        };
    }

    /** Bagian query di luar semua kurung kurawal (tempat LIMIT terluar berada). */
    private function topLevel(string $masked): string
    {
        $depth = 0;
        $out = '';

        foreach (str_split($masked) as $char) {
            if ($char === '{') {
                $depth++;
                $out .= ' ';
            } elseif ($char === '}') {
                $depth = max(0, $depth - 1);
                $out .= ' ';
            } elseif ($depth === 0) {
                $out .= $char;
            }
        }

        return $out;
    }
}
