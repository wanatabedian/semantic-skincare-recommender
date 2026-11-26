<?php

namespace App\Services\Recommendation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kriteria kuis yang sudah divalidasi. Semua nilai berupa nama lokal yang
 * dijamin ada di ontologi (lihat Vocabulary), jadi aman diubah menjadi IRI.
 *
 * Nama parameter mengikuti kontrak: age, skin, category, ingredient,
 * concern[], brand[], avoid[], price_min, price_max. Nama lama dari SparqlController versi
 * pertama (age_group, skin_type, concerns) tetap diterima sebagai alias.
 *
 *  - brand[] : brand pilihan (cukup salah satu cocok) -> menambah skor.
 *  - avoid[] : bahan yang ingin dihindari -> filter keras, tidak pernah dilonggarkan.
 */
final class Criteria
{
    /**
     * @param  string[]  $concerns
     * @param  string[]  $brands
     * @param  string[]  $avoid
     */
    public function __construct(
        public readonly ?string $age = null,
        public readonly ?string $skin = null,
        public readonly ?string $category = null,
        public readonly ?string $ingredient = null,
        public readonly array $concerns = [],
        public readonly ?int $priceMin = null,
        public readonly ?int $priceMax = null,
        public readonly array $brands = [],
        public readonly array $avoid = [],
    ) {}

    private const ALIASES = [
        'age_group' => 'age',
        'skin_type' => 'skin',
        'concerns' => 'concern',
    ];

    /**
     * Validasi input mentah dari request.
     *
     * @throws ValidationException
     */
    public static function fromInput(array $input, Vocabulary $vocabulary): self
    {
        foreach (self::ALIASES as $old => $new) {
            if (! array_key_exists($new, $input) && array_key_exists($old, $input)) {
                $input[$new] = $input[$old];
            }
        }

        // Nilai kosong dari form ("") dianggap "dilewati".
        foreach (['age', 'skin', 'category', 'ingredient', 'price_min', 'price_max'] as $key) {
            if (isset($input[$key]) && $input[$key] === '') {
                $input[$key] = null;
            }
        }
        // concern=Acne (tanpa []) tetap diterima; berlaku juga untuk brand dan avoid.
        foreach (['concern', 'brand', 'avoid'] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = $input[$key] === '' ? [] : [$input[$key]];
            }
            if (isset($input[$key]) && is_array($input[$key])) {
                $input[$key] = array_values(array_filter($input[$key], fn ($c) => $c !== '' && $c !== null));
            }
        }

        $in = fn (string $group) => Rule::in(array_keys($vocabulary->group($group)));
        $maxConcerns = (int) config('recommendation.max_concerns', 2);

        $validator = Validator::make($input, [
            'age' => ['nullable', 'string', $in('age')],
            'skin' => ['nullable', 'string', $in('skin')],
            'category' => ['nullable', 'string', $in('category')],
            'ingredient' => ['nullable', 'string', $in('ingredient')],
            'concern' => ['nullable', 'array', 'max:'.$maxConcerns],
            'concern.*' => ['string', 'distinct', $in('concern')],
            'brand' => ['nullable', 'array'],
            'brand.*' => ['string', 'distinct', $in('brand')],
            'avoid' => ['nullable', 'array'],
            'avoid.*' => ['string', 'distinct', $in('ingredient')],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
        ], [
            'in' => ':attribute tidak dikenal di ontologi.',
            'concern.max' => 'Keluhan maksimal :max.',
            'concern.*.in' => 'Keluhan tidak dikenal di ontologi.',
            'concern.*.distinct' => 'Keluhan tidak boleh sama.',
            'brand.*.in' => 'Brand tidak dikenal di ontologi.',
            'brand.*.distinct' => 'Brand tidak boleh sama.',
            'avoid.*.in' => 'Bahan yang dihindari tidak dikenal di ontologi.',
            'avoid.*.distinct' => 'Bahan yang dihindari tidak boleh sama.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute tidak boleh negatif.',
        ], [
            'age' => 'Kelompok umur',
            'skin' => 'Jenis kulit',
            'category' => 'Kategori',
            'ingredient' => 'Bahan aktif',
            'concern' => 'Keluhan',
            'brand' => 'Brand',
            'avoid' => 'Bahan yang dihindari',
            'price_min' => 'Harga minimum',
            'price_max' => 'Harga maksimum',
        ]);

        $validator->after(function ($v) use ($input) {
            $min = $input['price_min'] ?? null;
            $max = $input['price_max'] ?? null;
            if (is_numeric($min) && is_numeric($max) && (int) $max < (int) $min) {
                $v->errors()->add('price_max', 'Harga maksimum harus lebih besar atau sama dengan harga minimum.');
            }
            $ingredient = $input['ingredient'] ?? null;
            if (is_string($ingredient) && is_array($input['avoid'] ?? null) && in_array($ingredient, $input['avoid'], true)) {
                $v->errors()->add('avoid', 'Bahan yang dicari tidak boleh sekaligus dihindari.');
            }
        });

        $data = $validator->validate();

        return new self(
            age: $data['age'] ?? null,
            skin: $data['skin'] ?? null,
            category: $data['category'] ?? null,
            ingredient: $data['ingredient'] ?? null,
            concerns: array_values($data['concern'] ?? []),
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            brands: array_values($data['brand'] ?? []),
            avoid: array_values($data['avoid'] ?? []),
        );
    }

    public function hasPrice(): bool
    {
        return $this->priceMin !== null || $this->priceMax !== null;
    }

    public function isEmpty(): bool
    {
        return $this->age === null && $this->skin === null && $this->category === null
            && $this->ingredient === null && $this->concerns === [] && ! $this->hasPrice()
            && $this->brands === [] && $this->avoid === [];
    }

    /** Bentuk array untuk JSON / view / query string. */
    public function toArray(): array
    {
        return array_filter([
            'age' => $this->age,
            'skin' => $this->skin,
            'category' => $this->category,
            'ingredient' => $this->ingredient,
            'concern' => $this->concerns,
            'brand' => $this->brands,
            'avoid' => $this->avoid,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
