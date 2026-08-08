<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guest checkout is part of the approved MVP.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer_email' => [
                'required',
                'email:rfc',
                'max:255',
            ],

            'customer_phone' => [
                'required',
                'string',
                'max:50',
            ],

            'shipping_address_line_1' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_city' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_province' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_postal_code' => [
                'required',
                'string',
                'max:20',
            ],

            'shipping_method' => [
                'required',
                Rule::enum(ShippingMethod::class),
            ],

            'payment_method' => [
                'required',
                Rule::enum(PaymentMethod::class),
            ],

            'customer_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
