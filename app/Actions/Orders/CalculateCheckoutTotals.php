<?php

namespace App\Actions\Orders;

use App\Actions\Cart\CalculateCartTotals;
use App\Enums\ShippingMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;

class CalculateCheckoutTotals
{
    public function __construct(
        private readonly CalculateCartTotals $calculateCartTotals,
    ) {}

    /**
     * @param  Collection<int, array{product: Product, quantity: int}>  $items
     * @return array{
     *     subtotal: int,
     *     discount_total: int,
     *     discount_code: string|null,
     *     shipping_total: int,
     *     tax_total: int,
     *     grand_total: int
     * }
     */
    public function handle(
        Collection $items,
        ?string $discountCode,
        ShippingMethod $shippingMethod,
    ): array {
        $cartTotals = $this->calculateCartTotals->handle(
            $items,
            $discountCode,
        );

        // CalculateCartTotals already guarantees these values and types.
        $subtotal = $cartTotals['subtotal'];
        $discountTotal = $cartTotals['discount_total'];
        $appliedDiscountCode = $cartTotals['discount_code'];

        $settings = StoreSetting::query()->first();

        $shippingTotal = $this->calculateShipping(
            subtotal: $subtotal,
            shippingMethod: $shippingMethod,
            settings: $settings,
        );

        $taxableAmount = max(
            0,
            $subtotal - $discountTotal,
        );

        // The null-coalescing operator safely handles a missing settings row.
        $taxRateBasisPoints = (int) (
            $settings->tax_rate_basis_points ?? 0
        );

        $taxTotal = $taxRateBasisPoints > 0
            ? intdiv(
                $taxableAmount * $taxRateBasisPoints,
                10_000,
            )
            : 0;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'discount_code' => $appliedDiscountCode,
            'shipping_total' => $shippingTotal,
            'tax_total' => $taxTotal,
            'grand_total' => (
                $taxableAmount
                + $shippingTotal
                + $taxTotal
            ),
        ];
    }

    private function calculateShipping(
        int $subtotal,
        ShippingMethod $shippingMethod,
        ?StoreSetting $settings,
    ): int {
        if ($shippingMethod === ShippingMethod::StorePickup) {
            return 0;
        }

        $freeShippingThreshold = $settings?->free_shipping_threshold;

        if (
            $freeShippingThreshold !== null
            && $subtotal >= $freeShippingThreshold
        ) {
            return 0;
        }

        // The null-coalescing operator also safely handles $settings === null.
        return (int) (
            $settings->default_shipping_fee ?? 0
        );
    }
}
