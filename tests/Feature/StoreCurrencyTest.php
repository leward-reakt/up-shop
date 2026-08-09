<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Filament\Resources\StoreSettings\Pages\CreateStoreSetting;
use App\Filament\Resources\StoreSettings\Pages\EditStoreSetting;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
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

    public function test_admin_can_change_store_currency_before_orders_exist(): void
    {
        $this->actingAsAdmin();

        $settings = $this->createStoreSettings();

        Livewire::test(
            EditStoreSetting::class,
            ['record' => $settings->id],
        )
            ->fillForm([
                'currency' => 'USD',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'USD',
            $settings->fresh()?->currency,
        );
    }

    public function test_admin_cannot_change_store_currency_after_order_exists(): void
    {
        $this->actingAsAdmin();

        $settings = $this->createStoreSettings();

        $this->createOrder();

        Livewire::test(
            EditStoreSetting::class,
            ['record' => $settings->id],
        )
            ->fillForm([
                'currency' => 'USD',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'currency',
            ])
            ->assertSee(
                'The store currency cannot be changed after the first '
                .'order is created because existing order amounts do not '
                .'store a currency snapshot and no currency conversion is '
                .'available.',
            );

        $this->assertSame(
            'PHP',
            $settings->fresh()?->currency,
        );
    }

    public function test_admin_can_update_other_store_settings_after_order_exists_when_currency_is_unchanged(): void
    {
        $this->actingAsAdmin();

        $settings = $this->createStoreSettings();

        $this->createOrder();

        Livewire::test(
            EditStoreSetting::class,
            ['record' => $settings->id],
        )
            ->fillForm([
                'store_name' => 'Updated Currency Test Store',
                'currency' => 'php',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings->refresh();

        $this->assertSame(
            'Updated Currency Test Store',
            $settings->store_name,
        );

        $this->assertSame(
            'PHP',
            $settings->currency,
        );
    }

    public function test_non_default_currency_cannot_be_configured_after_order_exists_when_store_settings_are_missing(): void
    {
        $this->actingAsAdmin();

        $this->createOrder();

        Livewire::test(CreateStoreSetting::class)
            ->fillForm([
                'store_name' => 'Late Currency Test Store',
                'currency' => 'USD',
                'default_shipping_fee' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'currency',
            ]);

        $this->assertDatabaseCount(
            'store_settings',
            0,
        );
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Filament::setCurrentPanel('admin');

        return $admin;
    }

    private function createStoreSettings(): StoreSetting
    {
        return StoreSetting::query()->create([
            'store_name' => 'Currency Test Store',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
        ]);
    }

    private function createOrder(): Order
    {
        return Order::query()->create([
            'order_number' => 'TEST-'.fake()
                ->unique()
                ->numerify('######'),

            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09171234567',

            'shipping_address_line_1' => '123 Test Street',
            'shipping_address_line_2' => null,
            'shipping_city' => 'Manila',
            'shipping_province' => 'Metro Manila',
            'shipping_postal_code' => '1000',
            'shipping_country' => 'PH',

            'shipping_method' => ShippingMethod::FlatRate,

            'discount_code' => null,

            'subtotal' => 100_000,
            'discount_total' => 0,
            'shipping_total' => 15_000,
            'tax_total' => 0,
            'grand_total' => 115_000,

            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,

            'customer_notes' => null,
            'admin_notes' => null,
        ]);
    }
}
