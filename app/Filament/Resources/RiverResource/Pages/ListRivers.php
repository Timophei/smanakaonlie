<?php

namespace App\Filament\Resources\RiverResource\Pages;

use App\Filament\Resources\RiverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRivers extends ListRecords
{
    protected static string $resource = RiverResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
