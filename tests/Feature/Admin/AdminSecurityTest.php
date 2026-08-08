<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this
            ->get('/admin')
            ->assertRedirect(
                route('filament.admin.auth.login'),
            );
    }

    public function test_unverified_admin_cannot_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertForbidden();
    }
}
