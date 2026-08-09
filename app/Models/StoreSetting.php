<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    public const DEFAULT_CURRENCY = 'PHP';

    protected $fillable = [
        'store_name',
        'store_logo_path',
        'store_email',
        'contact_number',
        'business_address',
        'bank_transfer_instructions',
        'currency',
        'default_shipping_fee',
        'free_shipping_threshold',
        'tax_rate_basis_points',
        'social_links',
        'landing_page_theme',
    ];

    public static function currentCurrency(): string
    {
        $settings = static::query()->first();

        return $settings?->currencyCode()
            ?? self::DEFAULT_CURRENCY;
    }

    public static function currentBankTransferInstructions(): ?string
    {
        $settings = static::query()->first();

        $instructions = $settings?->getAttribute(
            'bank_transfer_instructions',
        );

        if (! is_string($instructions)) {
            return null;
        }

        $instructions = trim($instructions);

        return $instructions === ''
            ? null
            : $instructions;
    }

    public static function normalizeCurrency(
        ?string $currency,
    ): string {
        $currency = strtoupper(
            trim((string) $currency),
        );

        return preg_match('/^[A-Z]{3}$/', $currency) === 1
            ? $currency
            : self::DEFAULT_CURRENCY;
    }

    public function currencyCode(): string
    {
        $currency = $this->getAttribute('currency');

        return self::normalizeCurrency(
            is_string($currency)
                ? $currency
                : null,
        );
    }

    /**
     * Keep the persisted currency code normalized for every write path,
     * not only the Filament form.
     *
     * @return Attribute<string, string>
     */
    protected function currency(): Attribute
    {
        return Attribute::make(
            set: static fn (mixed $value): string => strtoupper(
                trim((string) $value),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_shipping_fee' => 'integer',
            'free_shipping_threshold' => 'integer',
            'tax_rate_basis_points' => 'integer',
            'social_links' => 'array',
        ];
    }
}
