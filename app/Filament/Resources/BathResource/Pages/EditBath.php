<?php

namespace App\Filament\Resources\BathResource\Pages;

use App\Filament\PlaceResource;
use App\Filament\Resources\BathResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBath extends EditRecord
{
    protected static string $resource = BathResource::class;

    protected function getHeaderActions(): array
    {
        return [PlaceResource::qrCardAction(), Actions\DeleteAction::make()];
    }
}
