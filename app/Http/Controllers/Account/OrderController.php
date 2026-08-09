<?php

namespace App\Http\Controllers\Account;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request
            ->user()
            ->orders()
            ->latest()
            ->paginate(10)
            ->through(
                fn (Order $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                    'payment_status' => $order->payment_status->value,
                    'payment_status_label' => $order->payment_status->label(),
                    'order_status' => $order->order_status->value,
                    'order_status_label' => $order->order_status->label(),
                    'created_at' => $order->created_at?->toIso8601String(),
                ],
            );

        return Inertia::render('account/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(
        Request $request,
        Order $order,
    ): Response {
        Gate::authorize('view', $order);

        $order->load([
            'items',
            'payment',
        ]);

        $bankTransferInstructions = (
            $order->payment_method === PaymentMethod::BankTransfer
            && $order->payment_status === PaymentStatus::Pending
        )
            ? StoreSetting::currentBankTransferInstructions()
            : null;

        $pickupLocation =
            $order->shipping_method === ShippingMethod::StorePickup
                ? StoreSetting::currentBusinessAddress()
                : null;

        return Inertia::render('account/orders/show', [
            'bank_transfer_instructions' => $bankTransferInstructions,
            'pickup_location' => $pickupLocation,

            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,

                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,

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
                'payment_method_label' => $order->payment_method->label(),

                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),

                'order_status' => $order->order_status->value,
                'order_status_label' => $order->order_status->label(),

                'items' => $order
                    ->items
                    ->map(
                        fn ($item): array => [
                            'id' => $item->id,
                            'product_name' => $item->product_name,
                            'sku' => $item->sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ],
                    )
                    ->values()
                    ->all(),

                'payment' => $order->payment === null
                    ? null
                    : [
                        'method' => $order->payment->method->value,
                        'method_label' => $order->payment->method->label(),
                        'status' => $order->payment->status->value,
                        'status_label' => $order->payment->status->label(),
                        'amount' => $order->payment->amount,
                        'reference' => $order->payment->reference,
                        'paid_at' => $order->payment->paid_at?->toIso8601String(),
                    ],

                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'discount_code' => $order->discount_code,
                'shipping_total' => $order->shipping_total,
                'tax_total' => $order->tax_total,
                'grand_total' => $order->grand_total,

                'customer_notes' => $order->customer_notes,

                'created_at' => $order->created_at?->toIso8601String(),
            ],
        ]);
    }
}
