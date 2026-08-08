<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $recentOrders = $user
            ->orders()
            ->latest()
            ->limit(5)
            ->get();

        $defaultAddress = $user
            ->addresses()
            ->where('is_default', true)
            ->first();

        return Inertia::render('dashboard', [
            'summary' => [
                'total_orders' => $user
                    ->orders()
                    ->count(),

                'active_orders' => $user
                    ->orders()
                    ->whereNotIn('order_status', [
                        OrderStatus::Completed->value,
                        OrderStatus::Cancelled->value,
                    ])
                    ->count(),

                'completed_orders' => $user
                    ->orders()
                    ->where(
                        'order_status',
                        OrderStatus::Completed->value,
                    )
                    ->count(),
            ],

            'recent_orders' => $recentOrders
                ->map(
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
                )
                ->values()
                ->all(),

            'default_address' => $defaultAddress === null
                ? null
                : $this->addressData($defaultAddress),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function addressData(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'province' => $address->province,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'is_default' => $address->is_default,
        ];
    }
}
