<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case BankTransfer = 'bank_transfer';
    case GCash = 'gcash';
    case Maya = 'maya';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
            self::BankTransfer => 'Bank Transfer',
            self::GCash => 'GCash',
            self::Maya => 'Maya',
        };
    }

    public function usesPayMongo(): bool
    {
        return match ($this) {
            self::CashOnDelivery,
            self::BankTransfer => false,

            self::GCash,
            self::Maya => true,
        };
    }
}
