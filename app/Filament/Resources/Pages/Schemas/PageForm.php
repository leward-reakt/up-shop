<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    ])
                    ->unique(ignoreRecord: true)
                    ->helperText(
                        'Use lowercase letters, numbers, and hyphens only.',
                    ),

                Textarea::make('content')
                    ->rows(16)
                    ->columnSpanFull(),

                TextInput::make('meta_title')
                    ->label('SEO title')
                    ->maxLength(60)
                    ->helperText(
                        'Optional. The page title is used when this is empty.',
                    )
                    ->columnSpanFull(),

                Textarea::make('meta_description')
                    ->label('SEO description')
                    ->rows(3)
                    ->maxLength(160)
                    ->helperText(
                        'Optional. Keep this concise and useful in search results.',
                    )
                    ->columnSpanFull(),

                Toggle::make('is_published')
                    ->label('Published')
                    ->default(false)
                    ->helperText(
                        'Draft pages are not accessible on the public storefront.',
                    ),
            ]);
    }
}
