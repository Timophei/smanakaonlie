<?php

namespace App\Filament\Resources\BathResource\Pages;

use App\Filament\Resources\BathResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBath extends CreateRecord
{
    protected static string $resource = BathResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
