<?php

namespace Tests\Feature\Payments;

use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayMongoStoreToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_paymongo_store_toggle_defaults_to_disabled(): void
    {
        config()->set(
            'services.paymongo.available',
            true,
        );

        $settings = StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
        ]);

        $this->assertFalse(
            $settings->paymongo_enabled,
        );

        $this->assertFalse(
            StoreSetting::payMongoAvailableForNewCheckout(),
        );
    }

    public function test_paymongo_new_checkout_requires_all_effective_gates(): void
    {
        config()->set(
            'services.paymongo.available',
            true,
        );

        $settings = StoreSetting::query()->create([
            'store_name' => 'Up Shop',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
            'paymongo_enabled' => true,
        ]);

        $this->assertTrue(
            StoreSetting::payMongoAvailableForNewCheckout(),
        );

        $settings->update([
            'currency' => 'USD',
        ]);

        $this->assertFalse(
            StoreSetting::payMongoAvailableForNewCheckout(),
        );

        $settings->update([
            'currency' => 'PHP',
        ]);

        config()->set(
            'services.paymongo.available',
            false,
        );

        $this->assertFalse(
            StoreSetting::payMongoAvailableForNewCheckout(),
        );
    }

    public function test_paymongo_is_unavailable_without_store_settings(): void
    {
        config()->set(
            'services.paymongo.available',
            true,
        );

        $this->assertFalse(
            StoreSetting::payMongoAvailableForNewCheckout(),
        );
    }
}
