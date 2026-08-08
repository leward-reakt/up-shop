<?php

namespace App\Actions\Cart;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;

class CalculateCartTotals
{
    public function __construct(
        private readonly ApplyDiscount $applyDiscount,
    ) {}

    /**
     * @param  Collection<int, array{product: Product, quantity: int}>  $items
     * @return array{
     *     subtotal: int,
     *     discount_total: int,
     *     shipping_total: int,
     *     grand_total: int,
     *     discount_code: string|null,
     *     discount_error: string|null
     * }
     */
    public function handle(
        Collection $items,
        ?string $discountCode = null,
    ): array {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['product']->price * $item['quantity'];
        }

        $discount = $this->applyDiscount->handle(
            code: $discountCode,
            subtotal: $subtotal,
        );

        $shippingTotal = $this->shippingTotal($subtotal);

        $grandTotal = max(
            0,
            $subtotal
                - $discount['amount']
                + $shippingTotal,
        );

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discount['amount'],
            'shipping_total' => $shippingTotal,
            'grand_total' => $grandTotal,
            'discount_code' => $discount['code'],
            'discount_error' => $discount['error'],
        ];
    }

    private function shippingTotal(int $subtotal): int
    {
        if ($subtotal <= 0) {
            return 0;
        }

        $settings = StoreSetting::query()->first();

        if ($settings === null) {
            return 0;
        }

        if (
            $settings->free_shipping_threshold !== null
            && $subtotal >= $settings->free_shipping_threshold
        ) {
            return 0;
        }

        return $settings->default_shipping_fee;
    }
}
