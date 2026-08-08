<?php

namespace App\Actions\Cart;

use App\Models\Discount;

class ApplyDiscount
{
    /**
     * @return array{
     *     code: string|null,
     *     amount: int,
     *     error: string|null
     * }
     */
    public function handle(?string $code, int $subtotal): array
    {
        $normalizedCode = $code === null
            ? ''
            : strtoupper(trim($code));

        if ($normalizedCode === '') {
            return [
                'code' => null,
                'amount' => 0,
                'error' => null,
            ];
        }

        if ($subtotal <= 0) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'Add products to your cart before applying a discount.',
            ];
        }

        $discount = Discount::query()
            ->where('code', $normalizedCode)
            ->first();

        if ($discount === null || ! $discount->is_active) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'This discount code is invalid or inactive.',
            ];
        }

        if (
            $discount->starts_at !== null
            && now()->lt($discount->starts_at)
        ) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'This discount code is not active yet.',
            ];
        }

        if (
            $discount->expires_at !== null
            && now()->gt($discount->expires_at)
        ) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'This discount code has expired.',
            ];
        }

        if (
            $discount->minimum_purchase !== null
            && $subtotal < $discount->minimum_purchase
        ) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'Your cart does not meet the minimum purchase required for this discount.',
            ];
        }

        $amount = match ($discount->type) {
            'percentage' => $this->percentageAmount(
                subtotal: $subtotal,
                percentage: $discount->value,
            ),
            'fixed' => $discount->value > 0
                ? min($discount->value, $subtotal)
                : null,
            default => null,
        };

        if ($amount === null || $amount <= 0) {
            return [
                'code' => $normalizedCode,
                'amount' => 0,
                'error' => 'This discount code is not configured correctly.',
            ];
        }

        return [
            'code' => $normalizedCode,
            'amount' => $amount,
            'error' => null,
        ];
    }

    private function percentageAmount(
        int $subtotal,
        int $percentage,
    ): ?int {
        if ($percentage < 1 || $percentage > 100) {
            return null;
        }

        // Money remains in integer minor units.
        return intdiv($subtotal * $percentage, 100);
    }
}
