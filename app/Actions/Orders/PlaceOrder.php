<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(
        private readonly CalculateCheckoutTotals $calculateCheckoutTotals,
    ) {}

    /**
     * @param  array<int, int>  $cartQuantities
     * @param  array<string, mixed>  $data
     */
    public function handle(
        ?User $user,
        array $cartQuantities,
        array $data,
        ?string $discountCode,
    ): Order {
        if ($cartQuantities === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        return DB::transaction(
            function () use (
                $user,
                $cartQuantities,
                $data,
                $discountCode,
            ): Order {
                $productIds = array_keys($cartQuantities);

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->where(
                        function (Builder $query): void {
                            $query
                                ->whereNull('category_id')
                                ->orWhereHas(
                                    'category',
                                    fn (Builder $categoryQuery): Builder => $categoryQuery
                                        ->where('is_active', true),
                                );
                        },
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== count($productIds)) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products in your cart are no longer available.',
                    ]);
                }

                /**
                 * @var Collection<int, array{
                 *     product: Product,
                 *     quantity: int
                 * }> $items
                 */
                $items = collect();

                foreach ($cartQuantities as $productId => $quantity) {
                    $product = $products->get($productId);

                    if (! $product instanceof Product) {
                        throw ValidationException::withMessages([
                            'cart' => 'One or more products in your cart are no longer available.',
                        ]);
                    }

                    $quantity = (int) $quantity;

                    if ($quantity < 1) {
                        throw ValidationException::withMessages([
                            'cart' => "The quantity for {$product->name} is invalid.",
                        ]);
                    }

                    if ($quantity > $product->stock_quantity) {
                        throw ValidationException::withMessages([
                            'cart' => "{$product->name} only has {$product->stock_quantity} item(s) available.",
                        ]);
                    }

                    $items->push([
                        'product' => $product,
                        'quantity' => $quantity,
                    ]);
                }

                $shippingMethod = ShippingMethod::from(
                    (string) $data['shipping_method'],
                );

                $paymentMethod = PaymentMethod::from(
                    (string) $data['payment_method'],
                );

                // Totals are recalculated inside the transaction using
                // current product prices and the current discount rules.
                $totals = $this->calculateCheckoutTotals->handle(
                    items: $items,
                    discountCode: $discountCode,
                    shippingMethod: $shippingMethod,
                );

                $appliedDiscountCode = $totals['discount_code'];

                $discountId = null;

                if ($appliedDiscountCode !== null) {
                    $value = Discount::query()
                        ->where('code', $appliedDiscountCode)
                        ->value('id');

                    if (is_numeric($value)) {
                        $discountId = (int) $value;
                    }
                }

                $order = Order::query()->create([
                    'order_number' => 'UP-'.Str::upper(
                        (string) Str::ulid(),
                    ),

                    'user_id' => $user?->id,
                    'discount_id' => $discountId,

                    'customer_name' => (string) $data['customer_name'],
                    'customer_email' => (string) $data['customer_email'],
                    'customer_phone' => (string) $data['customer_phone'],

                    'shipping_address_line_1' => (string) $data['shipping_address_line_1'],
                    'shipping_address_line_2' => $this->nullableString(
                        $data['shipping_address_line_2'] ?? null,
                    ),
                    'shipping_city' => (string) $data['shipping_city'],
                    'shipping_province' => (string) $data['shipping_province'],
                    'shipping_postal_code' => (string) $data['shipping_postal_code'],
                    'shipping_country' => 'PH',

                    'shipping_method' => $shippingMethod,

                    'discount_code' => $appliedDiscountCode,

                    'subtotal' => $totals['subtotal'],
                    'discount_total' => $totals['discount_total'],
                    'shipping_total' => $totals['shipping_total'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],

                    'payment_method' => $paymentMethod,
                    'payment_status' => PaymentStatus::Pending,
                    'order_status' => OrderStatus::Pending,

                    'customer_notes' => $this->nullableString(
                        $data['customer_notes'] ?? null,
                    ),
                    'admin_notes' => null,
                ]);

                foreach ($items as $item) {
                    $product = $item['product'];
                    $quantity = $item['quantity'];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'subtotal' => $product->price * $quantity,
                    ]);

                    // Keep a conditional stock guard even after lockForUpdate.
                    // This keeps checkout safe if locking semantics differ
                    // between the local SQLite and production database.
                    $updatedRows = Product::query()
                        ->whereKey($product->id)
                        ->where(
                            'stock_quantity',
                            '>=',
                            $quantity,
                        )
                        ->decrement(
                            'stock_quantity',
                            $quantity,
                        );

                    if ($updatedRows !== 1) {
                        throw ValidationException::withMessages([
                            'cart' => "{$product->name} no longer has enough stock.",
                        ]);
                    }

                    $product
                        ->inventoryAdjustments()
                        ->create([
                            'user_id' => $user?->id,
                            'quantity_change' => -$quantity,
                            'type' => 'order',
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'notes' => "Stock deducted for {$order->order_number}.",
                        ]);
                }

                $order->payment()->create([
                    'method' => $paymentMethod,
                    'status' => PaymentStatus::Pending,
                    'amount' => $totals['grand_total'],
                    'reference' => null,
                    'paid_at' => null,
                    'notes' => null,
                ]);

                // Authenticated carts are database-backed and therefore can
                // be cleared as part of the same transaction.
                if ($user !== null) {
                    $cart = $user->cart()->first();

                    $cart?->items()->delete();
                }

                return $order->load([
                    'items',
                    'payment',
                ]);
            },
            3,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
