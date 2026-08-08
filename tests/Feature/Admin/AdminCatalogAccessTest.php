<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_product_management(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/products')
            ->assertOk();
    }

    public function test_admin_can_access_category_management(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/categories')
            ->assertOk();
    }

    public function test_customer_cannot_access_product_management(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this
            ->actingAs($customer)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_inactive_admin_cannot_access_product_management(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => false,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/products')
            ->assertForbidden();
    }
}
