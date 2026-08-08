<?php

namespace App\Listeners;

use App\Actions\Cart\MergeGuestCartIntoUserCart;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class MergeGuestCartOnLogin
{
    public function __construct(
        private readonly MergeGuestCartIntoUserCart $mergeGuestCartIntoUserCart,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $request = request();

        if (! $request->hasSession()) {
            return;
        }

        if (
            $request->session()->get(
                'cart.sync_on_login',
                false,
            ) !== true
        ) {
            return;
        }

        $this->mergeGuestCartIntoUserCart->handle(
            request: $request,
            user: $event->user,
        );
    }
}
