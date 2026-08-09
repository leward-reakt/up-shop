<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CalculateCartTotals;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\ApplyCartDiscountRequest;
use App\Http\Requests\RemoveCartItemsRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(
        Request $request,
        CalculateCartTotals $calculateCartTotals,
    ): Response {
        $items = $this->resolveItems($request);

        $storedDiscountCode = $request
            ->session()
            ->get('cart.discount_code');

        $discountCode = is_string($storedDiscountCode)
            ? $storedDiscountCode
            : null;

        $totals = $calculateCartTotals->handle(
            items: $items,
            discountCode: $discountCode,
        );

        return Inertia::render('cart/index', [
            'items' => $items
                ->map(
                    fn (array $item): array => $this->cartItemData(
                        product: $item['product'],
                        quantity: $item['quantity'],
                    ),
                )
                ->values()
                ->all(),
            'totals' => $totals,
            'bulk_remove_enabled' => config(
                'features.cart.bulk_remove',
                false,
            ) === true,
        ]);
    }

    public function store(
        AddCartItemRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        $product = Product::query()
            ->with('category:id,is_active')
            ->findOrFail((int) $validated['product_id']);

        $requestedQuantity = (int) $validated['quantity'];

        $user = $request->user();

        if ($user instanceof User) {
            $cart = $user->cart()->firstOrCreate([]);

            $existingItem = $cart
                ->items()
                ->where('product_id', $product->id)
                ->first();

            $currentQuantity = $existingItem->quantity ?? 0;

            $newQuantity = $currentQuantity + $requestedQuantity;

            $this->ensureProductCanBePurchased(
                product: $product,
                quantity: $newQuantity,
            );

            $cart->items()->updateOrCreate(
                [
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $newQuantity,
                ],
            );
        } else {
            $items = $this->guestCartItems($request);

            $currentQuantity = $items[$product->id] ?? 0;

            $newQuantity = $currentQuantity + $requestedQuantity;

            $this->ensureProductCanBePurchased(
                product: $product,
                quantity: $newQuantity,
            );

            $items[$product->id] = $newQuantity;

            $request->session()->put('cart.items', $items);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$product->name} added to cart.",
        ]);

        return back();
    }

    public function update(
        UpdateCartItemRequest $request,
        Product $product,
    ): RedirectResponse {
        $validated = $request->validated();

        $quantity = (int) $validated['quantity'];

        $product->load('category:id,is_active');

        $this->ensureProductCanBePurchased(
            product: $product,
            quantity: $quantity,
        );

        $user = $request->user();

        if ($user instanceof User) {
            $cart = $user->cart()->first();

            if ($cart === null) {
                throw ValidationException::withMessages([
                    'quantity' => 'This product is not currently in your cart.',
                ]);
            }

            $cartItem = $cart
                ->items()
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem === null) {
                throw ValidationException::withMessages([
                    'quantity' => 'This product is not currently in your cart.',
                ]);
            }

            $cartItem->update([
                'quantity' => $quantity,
            ]);

            return to_route('cart.index');
        }

        $items = $this->guestCartItems($request);

        if (! array_key_exists($product->id, $items)) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is not currently in your cart.',
            ]);
        }

        $items[$product->id] = $quantity;

        $request->session()->put('cart.items', $items);

        return to_route('cart.index');
    }

    public function destroy(
        Request $request,
        int $productId,
    ): RedirectResponse {
        $user = $request->user();

        if ($user instanceof User) {
            $cart = $user->cart()->first();

            if ($cart !== null) {
                $cart
                    ->items()
                    ->where('product_id', $productId)
                    ->delete();

                if (! $cart->items()->exists()) {
                    $request
                        ->session()
                        ->forget('cart.discount_code');
                }
            }

            return to_route('cart.index');
        }

        $items = $this->guestCartItems($request);

        unset($items[$productId]);

        if ($items === []) {
            $request->session()->forget([
                'cart.items',
                'cart.discount_code',
            ]);

            return to_route('cart.index');
        }

        $request->session()->put('cart.items', $items);

        return to_route('cart.index');
    }

    public function destroyMany(
        RemoveCartItemsRequest $request,
    ): RedirectResponse {
        $productIds = $request->productIds();

        $user = $request->user();

        if ($user instanceof User) {
            $cart = $user->cart()->first();

            if ($cart !== null) {
                $cart
                    ->items()
                    ->whereIn('product_id', $productIds)
                    ->delete();

                if (! $cart->items()->exists()) {
                    $request
                        ->session()
                        ->forget('cart.discount_code');
                }
            }

            return to_route('cart.index');
        }

        $items = $this->guestCartItems($request);

        foreach ($productIds as $productId) {
            unset($items[$productId]);
        }

        if ($items === []) {
            $request->session()->forget([
                'cart.items',
                'cart.discount_code',
            ]);

            return to_route('cart.index');
        }

        $request->session()->put('cart.items', $items);

        return to_route('cart.index');
    }

    public function applyDiscount(
        ApplyCartDiscountRequest $request,
        CalculateCartTotals $calculateCartTotals,
    ): RedirectResponse {
        $validated = $request->validated();

        $discountCode = (string) $validated['discount_code'];

        $items = $this->resolveItems($request);

        $totals = $calculateCartTotals->handle(
            items: $items,
            discountCode: $discountCode,
        );

        if ($totals['discount_error'] !== null) {
            throw ValidationException::withMessages([
                'discount_code' => $totals['discount_error'],
            ]);
        }

        if ($totals['discount_code'] !== null) {
            $request
                ->session()
                ->put(
                    'cart.discount_code',
                    $totals['discount_code'],
                );
        }

        return to_route('cart.index');
    }

    public function removeDiscount(
        Request $request,
    ): RedirectResponse {
        $request
            ->session()
            ->forget('cart.discount_code');

        return to_route('cart.index');
    }

    /**
     * @return Collection<int, array{
     *     product: Product,
     *     quantity: int
     * }>
     */
    private function resolveItems(Request $request): Collection
    {
        $quantities = $this->cartQuantities($request);

        /** @var Collection<int, array{product: Product, quantity: int}> $items */
        $items = collect();

        if ($quantities === []) {
            return $items;
        }

        $products = Product::query()
            ->withTrashed()
            ->with([
                'category:id,name,slug,is_active',
                'images:id,product_id,path,alt_text,sort_order',
            ])
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                continue;
            }

            $items->push([
                'product' => $product,
                'quantity' => $quantity,
            ]);
        }

        return $items;
    }

    /**
     * @return array<int, int>
     */
    private function cartQuantities(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->guestCartItems($request);
        }

        $cart = $user->cart()->first();

        if ($cart === null) {
            return [];
        }

        $quantities = [];

        foreach (
            $cart->items()->get([
                'product_id',
                'quantity',
            ]) as $item
        ) {
            $quantities[(int) $item->product_id] = (int) $item->quantity;
        }

        return $quantities;
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

    private function ensureProductCanBePurchased(
        Product $product,
        int $quantity,
    ): void {
        if (! $this->productIsVisible($product)) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is currently unavailable.',
            ]);
        }

        if ($product->stock_quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is currently out of stock.',
            ]);
        }

        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} unit(s) are currently available.",
            ]);
        }
    }

    private function productIsVisible(Product $product): bool
    {
        if ($product->trashed() || ! $product->is_active) {
            return false;
        }

        if ($product->category_id === null) {
            return true;
        }

        return $product->category !== null
            && $product->category->is_active;
    }

    private function availabilityMessage(
        Product $product,
        int $quantity,
    ): ?string {
        if (! $this->productIsVisible($product)) {
            return 'This product is no longer available.';
        }

        if ($product->stock_quantity < 1) {
            return 'This product is currently out of stock.';
        }

        if ($quantity > $product->stock_quantity) {
            return "Only {$product->stock_quantity} unit(s) are currently available. Update the quantity before checkout.";
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function cartItemData(
        Product $product,
        int $quantity,
    ): array {
        $mainImage = $product->images->first();

        $productIsVisible = $this->productIsVisible($product);

        $availabilityMessage = $this->availabilityMessage(
            product: $product,
            quantity: $quantity,
        );

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $quantity,
            'stock_quantity' => $product->stock_quantity,
            'line_total' => $product->price * $quantity,
            'image_url' => $mainImage === null
                ? null
                : Storage::disk('public')->url($mainImage->path),
            'image_alt' => $mainImage?->alt_text,
            'is_product_visible' => $productIsVisible,
            'can_update_quantity' => $productIsVisible
                && $product->stock_quantity > 0,
            'is_available' => $availabilityMessage === null,
            'availability_message' => $availabilityMessage,
        ];
    }
}
