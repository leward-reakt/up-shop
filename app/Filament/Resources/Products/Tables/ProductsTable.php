<?php

namespace App\Filament\Resources\Products\Tables;

use App\Actions\Inventory\AdjustInventory;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('price')
                    ->money('PHP', divideBy: 100)
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Archived'),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active'),

                TernaryFilter::make('is_featured')
                    ->label('Featured'),

                Filter::make('low_stock')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->whereColumn(
                                'stock_quantity',
                                '<=',
                                'low_stock_threshold',
                            ),
                    ),

                Filter::make('out_of_stock')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('stock_quantity', 0),
                    ),
            ])
            ->recordActions([
                Action::make('adjustStock')
                    ->label('Adjust stock')
                    ->schema([
                        TextInput::make('quantity_change')
                            ->label('Quantity change')
                            ->helperText('Use a positive number to add stock or a negative number to remove stock.')
                            ->integer()
                            ->required()
                            ->notIn([0]),

                        Textarea::make('notes')
                            ->label('Reason')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (
                        Product $record,
                        array $data,
                    ): void {
                        $user = auth()->user();

                        abort_unless(
                            $user instanceof User,
                            403,
                        );

                        app(AdjustInventory::class)->handle(
                            product: $record,
                            quantityChange: (int) $data['quantity_change'],
                            user: $user,
                            notes: (string) $data['notes'],
                        );

                        Notification::make()
                            ->title('Inventory updated')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make()
                    ->label('Archive')
                    ->successNotificationTitle('Product archived'),

                RestoreAction::make()
                    ->successNotificationTitle('Product restored'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Archive selected')
                        ->successNotificationTitle('Products archived'),

                    RestoreBulkAction::make()
                        ->label('Restore selected')
                        ->successNotificationTitle('Products restored'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
