<?php

namespace App\Services;

use RuntimeException;

/**
 * Error yang ramah untuk ditampilkan ke user ketika Fuseki mati
 * atau query SPARQL gagal. Turunan RuntimeException supaya
 * controller lama yang menangkap RuntimeException tetap jalan.
 */
class SparqlException extends RuntimeException
{
}
