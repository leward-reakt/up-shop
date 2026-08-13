<?php

namespace App\Filament\Resources\LandingPageSections\Pages;

use App\Filament\Resources\LandingPageSections\LandingPageSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingPageSections extends ListRecords
{
    protected static string $resource = LandingPageSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
