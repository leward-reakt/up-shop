<?php

namespace App\Filament\Resources\LandingPageSections;

use App\Filament\Resources\LandingPageSections\Pages\EditLandingPageSection;
use App\Filament\Resources\LandingPageSections\Pages\ListLandingPageSections;
use App\Filament\Resources\LandingPageSections\Schemas\LandingPageSectionForm;
use App\Filament\Resources\LandingPageSections\Tables\LandingPageSectionsTable;
use App\Models\LandingPageSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LandingPageSectionResource extends Resource
{
    protected static ?string $model = LandingPageSection::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Homepage Sections';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return LandingPageSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandingPageSectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingPageSections::route('/'),
            'edit' => EditLandingPageSection::route('/{record}/edit'),
        ];
    }
}
