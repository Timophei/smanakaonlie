<?php

namespace App\Filament\Resources\BathResource\Pages;

use App\Filament\Resources\BathResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBaths extends ListRecords
{
    protected static string $resource = BathResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
