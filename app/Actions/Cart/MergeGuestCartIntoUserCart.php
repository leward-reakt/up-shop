<?php

namespace App\Actions\Cart;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MergeGuestCartIntoUserCart
{
    public function handle(
        Request $request,
        User $user,
    ): void {
        $guestItems = $this->guestCartItems($request);

        if ($guestItems === []) {
            $request->session()->forget([
                'cart.items',
                'cart.sync_on_login',
            ]);

            return;
        }

        DB::transaction(function () use ($guestItems, $user): void {
            $cart = $user->cart()->firstOrCreate([]);

            $existingProductIds = Product::query()
                ->withTrashed()
                ->whereIn('id', array_keys($guestItems))
                ->pluck('id')
                ->mapWithKeys(
                    fn (mixed $productId): array => [
                        (int) $productId => true,
                    ],
                )
                ->all();

            foreach ($guestItems as $productId => $guestQuantity) {
                // Ignore products that have been permanently removed.
                if (! isset($existingProductIds[$productId])) {
                    continue;
                }

                $existingItem = $cart
                    ->items()
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($existingItem === null) {
                    $cart->items()->create([
                        'product_id' => $productId,
                        'quantity' => $guestQuantity,
                    ]);

                    continue;
                }

                // Preserve both carts. Existing and guest quantities are added.
                // Inventory validation remains the responsibility of the cart
                // and checkout flows so quantities are never silently removed.
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $guestQuantity,
                ]);
            }
        });

        // Only clear the guest cart after the database merge succeeds.
        $request->session()->forget([
            'cart.items',
            'cart.sync_on_login',
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function guestCartItems(Request $request): array
    {
        $storedItems = $request
            ->session()
            ->get('cart.items', []);

        if (! is_array($storedItems)) {
            return [];
        }

        $items = [];

        foreach ($storedItems as $productId => $quantity) {
            if (
                ! is_numeric($productId)
                || ! is_numeric($quantity)
            ) {
                continue;
            }

            $normalizedProductId = (int) $productId;
            $normalizedQuantity = (int) $quantity;

            if (
                $normalizedProductId < 1
                || $normalizedQuantity < 1
            ) {
                continue;
            }

            $items[$normalizedProductId] = $normalizedQuantity;
        }

        return $items;
    }
}
