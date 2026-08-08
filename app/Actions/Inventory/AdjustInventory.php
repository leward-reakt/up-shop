<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustInventory
{
    public function handle(
        Product $product,
        int $quantityChange,
        User $user,
        ?string $notes = null,
    ): Product {
        if ($quantityChange === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'The inventory adjustment cannot be zero.',
            ]);
        }

        return DB::transaction(function () use (
            $product,
            $quantityChange,
            $user,
            $notes,
        ): Product {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            $newQuantity = $lockedProduct->stock_quantity
                + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => 'The adjustment would make the stock quantity negative.',
                ]);
            }

            $lockedProduct->update([
                'stock_quantity' => $newQuantity,
            ]);

            $lockedProduct->inventoryAdjustments()->create([
                'user_id' => $user->id,
                'quantity_change' => $quantityChange,
                'type' => 'manual',
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes,
            ]);

            return $lockedProduct->refresh();
        });
    }
}
