<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    /**
     * Public content pages approved by the locked MVP scope.
     *
     * Home and Shop use dedicated storefront routes and are intentionally
     * excluded from the generic content-page CMS.
     *
     * @var array<string, string>
     */
    private const PUBLIC_SLUG_OPTIONS = [
        'about' => 'About',
        'contact' => 'Contact',
        'privacy-policy' => 'Privacy Policy',
        'terms-and-conditions' => 'Terms & Conditions',
        'shipping-policy' => 'Shipping Policy',
        'return-refund-policy' => 'Return / Refund Policy',
    ];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    public static function publicSlugOptions(): array
    {
        return self::PUBLIC_SLUG_OPTIONS;
    }

    /**
     * @return list<string>
     */
    public static function publicSlugs(): array
    {
        return array_keys(self::PUBLIC_SLUG_OPTIONS);
    }

    public static function isPublicSlug(string $slug): bool
    {
        return isset(self::PUBLIC_SLUG_OPTIONS[$slug]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
