<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShopIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'availability' => [
                'nullable',
                Rule::in([
                    'in_stock',
                ]),
            ],
            'sort' => [
                'nullable',
                Rule::in([
                    'featured',
                    'newest',
                    'price_asc',
                    'price_desc',
                ]),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('min_price') || ! $this->filled('max_price')) {
                    return;
                }

                if ((float) $this->input('max_price') < (float) $this->input('min_price')) {
                    $validator
                        ->errors()
                        ->add('max_price', 'Maximum price must be greater than or equal to minimum price.');
                }
            },
        ];
    }
}
