<?php

namespace Tests\Feature\Actions\Orders;

use App\Actions\Orders\CalculateCheckoutTotals;
use App\Enums\ShippingMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCheckoutTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_default_shipping_fee_enables_store_wide_free_shipping(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
            'free_shipping_threshold' => null,
            'tax_rate_basis_points' => null,
        ]);

        $product = Product::factory()->create([
            'price' => 125_000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $totals = app(CalculateCheckoutTotals::class)->handle(
            items: collect([
                [
                    'product' => $product,
                    'quantity' => 2,
                ],
            ]),
            discountCode: null,
            shippingMethod: ShippingMethod::FlatRate,
        );

        $this->assertSame(
            250_000,
            $totals['subtotal'],
        );

        $this->assertSame(
            0,
            $totals['discount_total'],
        );

        $this->assertSame(
            0,
            $totals['shipping_total'],
        );

        $this->assertSame(
            0,
            $totals['tax_total'],
        );

        $this->assertSame(
            250_000,
            $totals['grand_total'],
        );
    }
}
