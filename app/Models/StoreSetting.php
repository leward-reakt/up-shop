<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'store_logo_path',
        'store_email',
        'contact_number',
        'business_address',
        'currency',
        'default_shipping_fee',
        'free_shipping_threshold',
        'tax_rate_basis_points',
        'social_links',
    ];

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
