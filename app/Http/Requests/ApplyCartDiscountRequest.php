<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCartDiscountRequest extends FormRequest
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
            'discount_code' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('discount_code')) {
            return;
        }

        $this->merge([
            'discount_code' => strtoupper(
                trim((string) $this->input('discount_code')),
            ),
        ]);
    }
}
