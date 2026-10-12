<?php

namespace App\Filament\Resources;

use App\Filament\PlaceResource;
use App\Filament\Resources\HotelResource\Pages;

class HotelResource extends PlaceResource
{
    protected static string $category = 'hotel';

    protected static string $pathPrefix = 'uslugi/gostinitsy/';

    protected static ?string $slug = 'hotels';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Гостиницы';

    protected static ?string $modelLabel = 'гостиница';

    protected static ?string $pluralModelLabel = 'Гостиницы';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
