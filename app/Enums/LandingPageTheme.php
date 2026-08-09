<?php

namespace App\Enums;

enum LandingPageTheme: string
{
    /**
     * Keep the persisted value for backward compatibility.
     *
     * The customer-facing name is Fashion Elegant. Renaming the stored value
     * would require unnecessary database and frontend changes for the MVP.
     */
    case FashionEditorial = 'fashion_editorial';

    public function label(): string
    {
        return 'Fashion Elegant';
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
