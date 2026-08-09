<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CheckoutRateLimitTest extends TestCase
{
    public function test_excessive_checkout_requests_are_throttled(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->post(route('checkout.store'))
                ->assertSessionHasErrors('customer_name');
        }

        $this
            ->post(route('checkout.store'))
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
