<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\StoreSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
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
        $userId = Auth::id() ?? 0;

        return [
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],
            'customer_email' => [
                'required',
                'email',
                'max:255',
            ],
            'customer_phone' => [
                'required',
                'string',
                'max:50',
            ],

            'shipping_address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(
                    fn ($query) => $query->where(
                        'user_id',
                        $userId,
                    ),
                ),
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                $paymentMethod = PaymentMethod::tryFrom(
                    (string) $this->input('payment_method'),
                );

                if (
                    $paymentMethod?->usesPayMongo() === true
                    && ! StoreSetting::payMongoAvailableForNewCheckout()
                ) {
                    $validator->errors()->add(
                        'payment_method',
                        'GCash and Maya are currently unavailable.',
                    );
                }
            },
        );
    }
}
