<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case FlatRate = 'flat_rate';
    case StorePickup = 'store_pickup';

    public function label(): string
    {
        return match ($this) {
            self::FlatRate => 'Standard Shipping',
            self::StorePickup => 'Store Pickup',
        };
    }
}
