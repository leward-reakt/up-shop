<?php

namespace Tests\Feature\Phase0;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($customer)
            ->get('/admin');

        $response->assertForbidden();
    }

    public function test_verified_active_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
    }

    public function test_inactive_admin_cannot_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin');

        $response->assertForbidden();
    }
}
