<?php

namespace App\Filament\Resources\LandingPageSections\Tables;

use App\Models\LandingPageSection;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandingPageSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Section')
                    ->formatStateUsing(
                        fn (mixed $state): string => LandingPageSection::labelFor(
                            is_string($state) ? $state : '',
                        ),
                    ),

                TextColumn::make('title')
                    ->limit(60),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('id');
    }
}
