<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_currency_is_normalized_and_shared_with_storefront(): void
    {
        $settings = StoreSetting::query()->create([
            'store_name' => 'Currency Test Store',
            'currency' => ' usd ',
            'default_shipping_fee' => 0,
        ]);

        $this->assertSame(
            'USD',
            $settings->fresh()?->currency,
        );

        $this->assertSame(
            'USD',
            StoreSetting::currentCurrency(),
        );

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->where(
                        'store.currency',
                        'USD',
                    ),
            );
    }

    public function test_missing_store_settings_falls_back_to_php(): void
    {
        $this->assertSame(
            'PHP',
            StoreSetting::currentCurrency(),
        );

        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('home')
                    ->where(
                        'store.currency',
                        'PHP',
                    ),
            );
    }

    public function test_invalid_currency_format_uses_safe_fallback(): void
    {
        $this->assertSame(
            'PHP',
            StoreSetting::normalizeCurrency(
                'US',
            ),
        );

        $this->assertSame(
            'PHP',
            StoreSetting::normalizeCurrency(
                'US1',
            ),
        );
    }
}
