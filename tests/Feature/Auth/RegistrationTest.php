<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(
            Features::registration(),
        );
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this
            ->get(route('register'))
            ->assertOk();
    }

    public function test_new_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->post(
            route('register.store'),
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        );

        $user = User::query()
            ->where('email', 'test@example.com')
            ->firstOrFail();

        $this->assertAuthenticatedAs($user);

        $this->assertNull(
            $user->email_verified_at,
        );

        $response->assertRedirect(
            route('dashboard', absolute: false),
        );

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }
}
