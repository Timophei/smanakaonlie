<?php

namespace App\Filament\Resources;

use App\Filament\PlaceResource;
use App\Filament\Resources\BathResource\Pages;

class BathResource extends PlaceResource
{
    protected static string $category = 'bath';

    protected static string $pathPrefix = 'uslugi/bani/';

    protected static ?string $slug = 'bani';

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Бани';

    protected static ?string $modelLabel = 'баня';

    protected static ?string $pluralModelLabel = 'Бани';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBaths::route('/'),
            'create' => Pages\CreateBath::route('/create'),
            'edit' => Pages\EditBath::route('/{record}/edit'),
        ];
    }
}
