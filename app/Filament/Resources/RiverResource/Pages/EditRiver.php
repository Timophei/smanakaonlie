<?php

namespace App\Filament\Resources\RiverResource\Pages;

use App\Filament\PlaceResource;
use App\Filament\Resources\RiverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRiver extends EditRecord
{
    protected static string $resource = RiverResource::class;

    protected function getHeaderActions(): array
    {
        return [PlaceResource::qrCardAction(), Actions\DeleteAction::make()];
    }
}
