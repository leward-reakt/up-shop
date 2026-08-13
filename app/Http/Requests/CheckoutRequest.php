<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Query\Builder;
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
        $user = Auth::user();

        $hasSavedAddresses = $user instanceof User
            && $user->addresses()->exists();

        $userId = $user instanceof User
            ? (int) $user->id
            : 0;

        $shippingAddressIdRules = [
            'nullable',
        ];

        if ($hasSavedAddresses) {
            $shippingAddressIdRules = [
                'required',
                'integer',
                Rule::exists('addresses', 'id')->where(
                    fn (Builder $query): Builder => $query->where(
                        'user_id',
                        $userId,
                    ),
                ),
            ];
        }

        $requiredContactRule = $hasSavedAddresses
            ? 'nullable'
            : 'required';

        $requiredAddressRule = $hasSavedAddresses
            ? 'nullable'
            : 'required';

        return [
            'customer_name' => [
                $requiredContactRule,
                'string',
                'max:255',
            ],
            'customer_email' => [
                $requiredContactRule,
                'email',
                'max:255',
            ],
            'customer_phone' => [
                $requiredContactRule,
                'string',
                'max:50',
            ],

            'shipping_address_id' => $shippingAddressIdRules,

            'shipping_address_line_1' => [
                $requiredAddressRule,
                'string',
                'max:255',
            ],
            'shipping_address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'shipping_city' => [
                $requiredAddressRule,
                'string',
                'max:255',
            ],
            'shipping_province' => [
                $requiredAddressRule,
                'string',
                'max:255',
            ],
            'shipping_postal_code' => [
                $requiredAddressRule,
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
