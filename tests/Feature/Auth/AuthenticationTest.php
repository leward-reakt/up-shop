<?php

namespace Tests\Feature\Auth;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_guest_cart_sync_intent_is_recorded_when_customer_continues_to_login(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession([
                'cart.items' => [
                    $product->id => 2,
                ],
            ])
            ->get('/login?sync_cart=1')
            ->assertOk()
            ->assertSessionHas(
                'cart.sync_on_login',
                true,
            );
    }

    public function test_guest_cart_is_merged_into_account_after_confirmed_login(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->withSession([
            'cart.items' => [
                $product->id => 2,
            ],
        ]);

        $this
            ->get('/login?sync_cart=1')
            ->assertOk()
            ->assertSessionHas(
                'cart.sync_on_login',
                true,
            );

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(
            route('dashboard', absolute: false),
        );

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response
            ->assertSessionMissing('cart.items')
            ->assertSessionMissing('cart.sync_on_login');
    }

    public function test_guest_cart_appends_new_items_and_adds_existing_quantities(): void
    {
        $user = User::factory()->create();

        $existingProduct = Product::factory()->create([
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $guestOnlyProduct = Product::factory()->create([
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $existingProduct->id,
            'quantity' => 2,
        ]);

        $this->withSession([
            'cart.items' => [
                $existingProduct->id => 3,
                $guestOnlyProduct->id => 2,
            ],
        ]);

        $this
            ->get('/login?sync_cart=1')
            ->assertOk();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $existingProduct->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $guestOnlyProduct->id,
            'quantity' => 2,
        ]);

        $this->assertSame(
            2,
            $cart->items()->count(),
        );

        $response
            ->assertSessionMissing('cart.items')
            ->assertSessionMissing('cart.sync_on_login');
    }

    public function test_guest_cart_is_not_synced_without_customer_confirmation(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->withSession([
            'cart.items' => [
                $product->id => 2,
            ],
        ]);

        $this
            ->get(route('login'))
            ->assertOk()
            ->assertSessionMissing('cart.sync_on_login');

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseMissing('carts', [
            'user_id' => $user->id,
        ]);

        $response->assertSessionHas(
            "cart.items.{$product->id}",
            2,
        );
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge()
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        RateLimiter::increment(
            md5(
                'login'.implode('|', [
                    $user->email,
                    '127.0.0.1',
                ]),
            ),
            amount: 5,
        );

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }
}
