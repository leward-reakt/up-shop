<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    public const HERO = 'hero';

    public const COLLECTIONS = 'collections';

    public const NEW_ARRIVALS = 'new_arrivals';

    public const STORY = 'story';

    public const SIGNATURE = 'signature';

    public const FINAL_CTA = 'final_cta';

    protected $fillable = [
        'key',
        'eyebrow',
        'title',
        'body',
        'button_label',
        'button_url',
        'image_path',
        'image_alt',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::HERO => 'Hero',
            self::COLLECTIONS => 'Collections',
            self::NEW_ARRIVALS => 'New Arrivals',
            self::STORY => 'Story',
            self::SIGNATURE => 'Signature Selection',
            self::FINAL_CTA => 'Final CTA',
        ];
    }

    public static function labelFor(string $key): string
    {
        return self::options()[$key] ?? $key;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
