<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\StoreSetting;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $currency = StoreSetting::currentCurrency();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),

                TextColumn::make(
                    'completed_spending',
                )
                    ->label('Total spending')
                    ->money(
                        $currency,
                        divideBy: 100,
                    )
                    ->default(0),

                TextColumn::make('created_at')
                    ->label('Customer since')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort(
                'created_at',
                'desc',
            );
    }
}
