<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCartItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config(
            'features.cart.bulk_remove',
            false,
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'product_ids.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        $productIds = $this->validated('product_ids');

        if (! is_array($productIds)) {
            return [];
        }

        return array_values(
            array_map(
                static fn (mixed $productId): int => (int) $productId,
                $productIds,
            ),
        );
    }
}
