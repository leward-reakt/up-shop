<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * Use one column on smaller screens and two columns on wide screens.
     *
     * Full-width widgets still span both columns, while table widgets may
     * occupy one column each.
     *
     * @return int|array<string, int>
     */
    public function getColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 2,
        ];
    }
}
