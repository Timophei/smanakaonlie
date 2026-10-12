<?php

namespace App\Filament\Resources;

use App\Filament\PlaceResource;
use App\Filament\Resources\FoodResource\Pages;

class FoodResource extends PlaceResource
{
    protected static string $category = 'food';

    protected static string $pathPrefix = 'uslugi/produkty/';

    protected static ?string $slug = 'produkty';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Продукты';

    protected static ?string $modelLabel = 'продукты';

    protected static ?string $pluralModelLabel = 'Продукты';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoods::route('/'),
            'create' => Pages\CreateFood::route('/create'),
            'edit' => Pages\EditFood::route('/{record}/edit'),
        ];
    }
}
