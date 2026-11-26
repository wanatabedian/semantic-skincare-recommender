<?php

namespace App\Http\Requests;

use App\Services\FusekiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        try { $options = app(FusekiService::class)->options(); }
        catch (\RuntimeException) { $options = ['age_groups' => [], 'skin_types' => [], 'categories' => [], 'concerns' => [], 'ingredients' => [], 'brands' => []]; }
        $options += ['brands' => []];
        return [
            'age' => ['nullable', 'string', Rule::in(array_column($options['age_groups'], 'value'))],
            'skin' => ['nullable', 'string', Rule::in(array_column($options['skin_types'], 'value'))],
            'category' => ['nullable', 'string', Rule::in(array_column($options['categories'], 'value'))],
            'ingredient' => ['nullable', 'string', Rule::in(array_column($options['ingredients'], 'value'))],
            'concern' => ['sometimes', 'array', 'max:2'],
            'concern.*' => ['string', 'distinct', Rule::in(array_column($options['concerns'], 'value'))],
            'brand' => ['sometimes', 'array'],
            'brand.*' => ['string', 'distinct', Rule::in(array_column($options['brands'], 'value'))],
            'avoid' => ['sometimes', 'array'],
            'avoid.*' => ['string', 'distinct', Rule::in(array_column($options['ingredients'], 'value'))],
            'price_min' => ['nullable', 'integer', 'min:0'],
            // 'gte:price_min' menolak input saat price_min kosong (mis. "Di bawah Rp50.000"),
            // jadi perbandingan min/max dilakukan di after() hanya bila keduanya diisi.
            'price_max' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $min = $this->input('price_min');
            $max = $this->input('price_max');
            if (is_numeric($min) && is_numeric($max) && (int) $max < (int) $min) {
                $validator->errors()->add('price_max', 'Harga maksimum harus lebih besar atau sama dengan harga minimum.');
            }
            $ingredient = $this->input('ingredient');
            if ($ingredient && in_array($ingredient, (array) $this->input('avoid', []), true)) {
                $validator->errors()->add('avoid', 'Bahan yang dicari tidak boleh sekaligus dihindari.');
            }
        }];
    }
}
