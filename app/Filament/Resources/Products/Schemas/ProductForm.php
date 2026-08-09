<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\StoreSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = StoreSetting::currentCurrency();

        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText(
                        'Used in the public product URL.',
                    ),

                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->rows(6)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->label('Price')
                    ->prefix($currency)
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->formatStateUsing(
                        fn (int|string|null $state): ?string => $state === null
                            ? null
                            : number_format(
                                ((int) $state) / 100,
                                2,
                                '.',
                                '',
                            ),
                    )
                    ->dehydrateStateUsing(
                        fn (
                            int|float|string|null $state,
                        ): int => (int) round(
                            ((float) $state) * 100,
                        ),
                    )
                    ->rules([
                        'decimal:0,2',
                    ])
                    ->helperText(
                        "Displayed in {$currency}; stored internally "
                        .'in 1/100 currency units.',
                    ),

                TextInput::make('stock_quantity')
                    ->label('Stock quantity')
                    ->required(
                        fn (string $operation): bool => $operation === 'create',
                    )
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->disabled(
                        fn (string $operation): bool => $operation === 'edit',
                    )
                    ->saved(
                        fn (string $operation): bool => $operation === 'create',
                    )
                    ->helperText(
                        fn (string $operation): string => $operation === 'edit'
                            ? 'Use the Adjust stock action from the Products list so manual inventory changes are audited.'
                            : 'Set the initial stock quantity. Future manual changes must use Adjust stock.',
                    ),

                TextInput::make('low_stock_threshold')
                    ->label('Low-stock threshold')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(5),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText(
                        'Inactive products are hidden from the storefront.',
                    ),

                Toggle::make('is_featured')
                    ->label('Featured')
                    ->default(false),

                Repeater::make('images')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->schema([
                        FileUpload::make('path')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->preventFilePathTampering(
                                allowFilePathUsing: fn (
                                    string $file,
                                ): bool => str_starts_with(
                                    $file,
                                    'products/',
                                ) && ! str_contains(
                                    $file,
                                    '..',
                                ),
                            )
                            ->required(),

                        TextInput::make('alt_text')
                            ->label('Alternative text')
                            ->maxLength(255),
                    ])
                    ->afterDelete(
                        function (Model $record): void {
                            $path = $record->getAttribute(
                                'path',
                            );

                            if (
                                is_string($path)
                                && $path !== ''
                            ) {
                                Storage::disk('public')
                                    ->delete($path);
                            }
                        },
                    )
                    ->maxItems(8)
                    ->collapsible()
                    ->columnSpanFull()
                    ->helperText(
                        'Drag images to reorder them. The first image '
                        .'is used as the main storefront image.',
                    ),

                TextInput::make('meta_title')
                    ->label('SEO title')
                    ->maxLength(255),

                Textarea::make('meta_description')
                    ->label('SEO description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
