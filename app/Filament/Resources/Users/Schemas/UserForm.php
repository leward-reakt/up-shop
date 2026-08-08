<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->saved(false),

                TextInput::make('email')
                    ->disabled()
                    ->saved(false),

                TextInput::make('phone')
                    ->disabled()
                    ->saved(false),

                TextInput::make('email_verified_at')
                    ->label('Email verified at')
                    ->disabled()
                    ->saved(false),

                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Inactive customers are signed out and cannot use an authenticated account.'),
            ]);
    }
}
