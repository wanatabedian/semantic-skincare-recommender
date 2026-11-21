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
        catch (\RuntimeException) { $options = ['age_groups' => [], 'skin_types' => [], 'categories' => [], 'concerns' => [], 'ingredients' => []]; }
        return [
            'age' => ['nullable', 'string', Rule::in(array_column($options['age_groups'], 'value'))],
            'skin' => ['nullable', 'string', Rule::in(array_column($options['skin_types'], 'value'))],
            'category' => ['nullable', 'string', Rule::in(array_column($options['categories'], 'value'))],
            'ingredient' => ['nullable', 'string', Rule::in(array_column($options['ingredients'], 'value'))],
            'concern' => ['sometimes', 'array', 'max:2'],
            'concern.*' => ['string', 'distinct', Rule::in(array_column($options['concerns'], 'value'))],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'gte:price_min'],
        ];
    }
}
