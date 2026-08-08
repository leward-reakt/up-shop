<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label'),

                TextColumn::make('recipient_name')
                    ->label('Recipient'),

                TextColumn::make('phone'),

                TextColumn::make('address_line_1')
                    ->label('Address'),

                TextColumn::make('city'),

                TextColumn::make('province'),

                TextColumn::make('postal_code')
                    ->label('Postal code'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ]);
    }
}
