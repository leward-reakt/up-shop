<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property int $amount
 * @property string|null $currency
 * @property string|null $provider
 * @property string|null $provider_checkout_id
 * @property string|null $provider_payment_intent_id
 * @property string|null $provider_payment_id
 * @property string|null $reference
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property string|null $refund_reference
 * @property Carbon|null $refunded_at
 * @property array<string, mixed>|null $metadata
 * @property string|null $notes
 */
class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount',
        'currency',
        'provider',
        'provider_checkout_id',
        'provider_payment_intent_id',
        'provider_payment_id',
        'reference',
        'paid_at',
        'failed_at',
        'refund_reference',
        'refunded_at',
        'metadata',
        'notes',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPayMongoManaged(): bool
    {
        return $this->provider === 'paymongo'
            || $this->method->usesPayMongo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
