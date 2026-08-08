<?php

namespace App\Enums;

enum LandingPageTheme: string
{
    case Default = 'default';
    case FashionEditorial = 'fashion_editorial';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default Storefront',
            self::FashionEditorial => 'Fashion Editorial',
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
