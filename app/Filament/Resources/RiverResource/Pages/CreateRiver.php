<?php

namespace App\Filament\Resources\RiverResource\Pages;

use App\Filament\Resources\RiverResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRiver extends CreateRecord
{
    protected static string $resource = RiverResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
