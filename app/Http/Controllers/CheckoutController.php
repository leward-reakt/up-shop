<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CalculateCheckoutTotals;
use App\Actions\Orders\PlaceOrder;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Http\Requests\CheckoutRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const CHECKOUT_LOCK_SECONDS = 60;

    public function index(
        Request $request,
        CalculateCheckoutTotals $calculateCheckoutTotals,
    ): Response|RedirectResponse {
        $cartQuantities = $this->cartQuantities($request);

        if ($cartQuantities === []) {
            return to_route('cart.index')
                ->withErrors([
                    'cart' => 'Your cart is empty.',
                ]);
        }

        $items = $this->cartItems($cartQuantities);

        if ($items->count() !== count($cartQuantities)) {
            return to_route('cart.index')
                ->withErrors([
                    'cart' => 'One or more products in your cart are no longer available.',
                ]);
        }

        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];

            if ($product->stock_quantity < 1) {
                return to_route('cart.index')
                    ->withErrors([
                        'cart' => "{$product->name} is currently out of stock. Update your cart before checkout.",
                    ]);
            }

            if ($quantity > $product->stock_quantity) {
                return to_route('cart.index')
                    ->withErrors([
                        'cart' => "{$product->name} only has {$product->stock_quantity} unit(s) available. Update the quantity before checkout.",
                    ]);
            }
        }

        $pickupLocation = StoreSetting::currentBusinessAddress();

        $shippingMethod = ShippingMethod::tryFrom(
            (string) $request->query(
                'shipping_method',
                ShippingMethod::FlatRate->value,
            ),
        ) ?? ShippingMethod::FlatRate;

        if (
            $shippingMethod === ShippingMethod::StorePickup
            && $pickupLocation === null
        ) {
            $shippingMethod = ShippingMethod::FlatRate;
        }

        $discountCode = $this->discountCode($request);

        try {
            $totals = $calculateCheckoutTotals->handle(
                items: $items,
                discountCode: $discountCode,
                shippingMethod: $shippingMethod,
            );
        } catch (ValidationException $exception) {
            return to_route('cart.index')
                ->withErrors($exception->errors());
        }

        $user = $request->user();

        $savedAddresses = $user === null
            ? []
            : $user
                ->addresses()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get()
                ->map(
                    fn (Address $address): array => [
                        'id' => $address->id,
                        'label' => $address->label,
                        'recipient_name' => $address->recipient_name,
                        'email' => $address->email,
                        'phone' => $address->phone,
                        'address_line_1' => $address->address_line_1,
                        'address_line_2' => $address->address_line_2,
                        'city' => $address->city,
                        'province' => $address->province,
                        'postal_code' => $address->postal_code,
                        'country' => $address->country,
                        'is_default' => $address->is_default,
                    ],
                )
                ->values()
                ->all();

        $shippingMethods = [
            ShippingMethod::FlatRate,
        ];

        if ($pickupLocation !== null) {
            $shippingMethods[] = ShippingMethod::StorePickup;
        }

        $bankTransferInstructions =
            StoreSetting::currentBankTransferInstructions();

        $paymentOptions = [
            [
                'value' => PaymentMethod::CashOnDelivery->value,
                'label' => PaymentMethod::CashOnDelivery->label(),
            ],
        ];

        if ($bankTransferInstructions !== null) {
            $paymentOptions[] = [
                'value' => PaymentMethod::BankTransfer->value,
                'label' => PaymentMethod::BankTransfer->label(),
            ];
        }

        return Inertia::render('checkout/index', [
            'items' => $items
                ->map(
                    function (array $item): array {
                        $product = $item['product'];
                        $quantity = $item['quantity'];

                        $mainImage = $product->images->first();

                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'quantity' => $quantity,
                            'unit_price' => $product->price,
                            'line_total' => (
                                $product->price
                                * $quantity
                            ),
                            'image_url' => $mainImage === null
                                ? null
                                : Storage::disk('public')
                                    ->url($mainImage->path),
                        ];
                    },
                )
                ->values()
                ->all(),

            'totals' => $totals,

            'shipping_options' => array_map(
                fn (ShippingMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ],
                $shippingMethods,
            ),

            'pickup_location' => $pickupLocation,

            'payment_options' => $paymentOptions,

            'bank_transfer_instructions' => $bankTransferInstructions,

            'selected_shipping_method' => $shippingMethod->value,

            'is_authenticated' => $user !== null,

            'saved_addresses' => $savedAddresses,

            'customer' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
            ],
        ]);
    }

    public function store(
        CheckoutRequest $request,
        PlaceOrder $placeOrder,
    ): RedirectResponse {
        $response = Cache::lock(
            $this->checkoutLockKey($request),
            self::CHECKOUT_LOCK_SECONDS,
        )->get(function () use (
            $request,
            $placeOrder,
        ): RedirectResponse {
            $cartQuantities = $this->cartQuantities($request);

            if ($cartQuantities === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $order = $placeOrder->handle(
                user: $request->user(),
                cartQuantities: $cartQuantities,
                data: $request->validated(),
                discountCode: $this->discountCode($request),
            );

            if ($request->user() === null) {
                $request->session()->forget('cart.items');
            }

            $request->session()->forget('cart.discount_code');

            $request->session()->put(
                'checkout.order_id',
                $order->id,
            );

            return to_route('checkout.success');
        });

        if (! $response instanceof RedirectResponse) {
            throw ValidationException::withMessages([
                'cart' => 'Your order is already being processed. Please wait a moment before trying again.',
            ]);
        }

        return $response;
    }

    public function success(Request $request): Response
    {
        $orderId = $request->session()->get(
            'checkout.order_id',
        );

        abort_unless(
            is_numeric($orderId),
            404,
        );

        $order = Order::query()
            ->with([
                'items',
                'payment',
            ])
            ->findOrFail((int) $orderId);

        if (
            $order->user_id !== null
            && $order->user_id !== $request->user()?->id
        ) {
            abort(403);
        }

        $bankTransferInstructions = (
            $order->payment_method === PaymentMethod::BankTransfer
            && $order->payment_status === PaymentStatus::Pending
        )
            ? StoreSetting::currentBankTransferInstructions()
            : null;

        $pickupLocation =
            $order->shipping_method === ShippingMethod::StorePickup
                ? $order->pickup_location
                : null;

        return Inertia::render('checkout/success', [
            'bank_transfer_instructions' => $bankTransferInstructions,
            'pickup_location' => $pickupLocation,

            'order' => [
                'order_number' => $order->order_number,

                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,

                'shipping_address' => [
                    'address_line_1' => $order->shipping_address_line_1,
                    'address_line_2' => $order->shipping_address_line_2,
                    'city' => $order->shipping_city,
                    'province' => $order->shipping_province,
                    'postal_code' => $order->shipping_postal_code,
                    'country' => $order->shipping_country,
                ],

                'shipping_method' => $order->shipping_method->value,
                'shipping_method_label' => $order->shipping_method->label(),

                'payment_method' => $order->payment_method->value,
                'payment_method_label' => $this->paymentMethodLabel(
                    $order->payment_method,
                ),

                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $this->statusLabel(
                    $order->payment_status->value,
                ),

                'order_status' => $order->order_status->value,
                'order_status_label' => $this->statusLabel(
                    $order->order_status->value,
                ),

                'payment_reference' => $order->payment?->reference,

                'items' => $order->items
                    ->map(
                        fn ($item): array => [
                            'product_name' => $item->product_name,
                            'sku' => $item->sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ],
                    )
                    ->values()
                    ->all(),

                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'discount_code' => $order->discount_code,
                'shipping_total' => $order->shipping_total,
                'tax_total' => $order->tax_total,
                'grand_total' => $order->grand_total,

                'created_at' => $order->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function cartQuantities(Request $request): array
    {
        $user = $request->user();

        if ($user !== null) {
            $cart = $user
                ->cart()
                ->with('items')
                ->first();

            if ($cart === null) {
                return [];
            }

            $quantities = [];

            foreach ($cart->items as $cartItem) {
                $quantity = (int) $cartItem->quantity;

                if ($quantity > 0) {
                    $quantities[(int) $cartItem->product_id] = $quantity;
                }
            }

            return $quantities;
        }

        $rawItems = $request->session()->get(
            'cart.items',
            [],
        );

        if (! is_array($rawItems)) {
            return [];
        }

        $quantities = [];

        foreach ($rawItems as $productId => $quantity) {
            if (! is_numeric($productId)) {
                continue;
            }

            if (! is_numeric($quantity)) {
                continue;
            }

            $quantity = (int) $quantity;

            if ($quantity < 1) {
                continue;
            }

            $quantities[(int) $productId] = $quantity;
        }

        return $quantities;
    }

    /**
     * @param  array<int, int>  $cartQuantities
     * @return Collection<int, array{
     *     product: Product,
     *     quantity: int
     * }>
     */
    private function cartItems(
        array $cartQuantities,
    ): Collection {
        $products = Product::query()
            ->with([
                'category:id,is_active',
                'images:id,product_id,path,alt_text,sort_order',
            ])
            ->whereIn(
                'id',
                array_keys($cartQuantities),
            )
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
            ->get()
            ->keyBy('id');

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
                continue;
            }

            $items->push([
                'product' => $product,
                'quantity' => $quantity,
            ]);
        }

        return $items;
    }

    private function discountCode(
        Request $request,
    ): ?string {
        $discountCode = $request->session()->get(
            'cart.discount_code',
        );

        if (! is_string($discountCode)) {
            return null;
        }

        $discountCode = trim($discountCode);

        return $discountCode === ''
            ? null
            : $discountCode;
    }

    private function checkoutLockKey(Request $request): string
    {
        $userId = $request->user()?->getAuthIdentifier();

        $scope = $userId === null
            ? 'session:'.$request->session()->getId()
            : 'user:'.$userId;

        return 'checkout:place-order:'.$scope;
    }

    private function paymentMethodLabel(
        PaymentMethod $method,
    ): string {
        return $method->label();
    }

    private function statusLabel(string $status): string
    {
        return ucwords(
            str_replace('_', ' ', $status),
        );
    }
}
