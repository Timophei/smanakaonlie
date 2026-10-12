<?php

namespace App\Filament\Resources;

use App\Filament\PlaceResource;
use App\Filament\Resources\RiverResource\Pages;

class RiverResource extends PlaceResource
{
    protected static string $category = 'river';

    protected static string $pathPrefix = 'uslugi/reka/';

    protected static ?string $slug = 'reka';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Река';

    protected static ?string $modelLabel = 'услуга на реке';

    protected static ?string $pluralModelLabel = 'Река';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRivers::route('/'),
            'create' => Pages\CreateRiver::route('/create'),
            'edit' => Pages\EditRiver::route('/{record}/edit'),
        ];
    }
}
