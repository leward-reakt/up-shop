<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCurrencyFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_table_uses_configured_store_currency(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        StoreSetting::query()->create([
            'store_name' => 'USD Test Store',
            'currency' => 'USD',
            'default_shipping_fee' => 0,
        ]);

        $product = Product::factory()->create([
            'name' => 'Currency Test Product',
            'price' => 123_456,
        ]);

        $this->actingAs($admin);

        Filament::setCurrentPanel('admin');

        Livewire::test(ListProducts::class)
            ->assertSee($product->name)
            ->assertSee('$1,234.56');
    }
}
