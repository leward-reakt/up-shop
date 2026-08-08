<?php

namespace App\Enums;

enum LandingPageTheme: string
{
    case Default = 'default';

    /**
     * Keep the persisted value for backward compatibility.
     *
     * The customer-facing design is now Fashion Elegant rather than
     * Fashion Editorial, but changing the stored value would require
     * unnecessary data migration for an MVP-only presentation change.
     */
    case FashionEditorial = 'fashion_editorial';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default Storefront',
            self::FashionEditorial => 'Fashion Elegant',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $theme) {
            $options[$theme->value] = $theme->label();
        }

        return $options;
    }
}
