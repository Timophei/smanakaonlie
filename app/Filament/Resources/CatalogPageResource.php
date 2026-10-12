<?php

namespace App\Filament\Resources;

use App\Filament\Forms\SeoFields;
use App\Filament\Resources\CatalogPageResource\Pages;
use App\Models\CatalogPage;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Разделы каталога (/uslugi/homes/, /uslugi/Bani/ …) и SEO главной страницы.
 */
class CatalogPageResource extends Resource
{
    protected static ?string $model = CatalogPage::class;

    protected static ?string $slug = 'razdely';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Разделы и главная';

    protected static ?string $modelLabel = 'раздел';

    protected static ?string $pluralModelLabel = 'Разделы';

    protected static ?string $recordTitleAttribute = 'h1';

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return ['home' => 'Главная страница (только SEO)', ...Place::CATEGORIES];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Раздел')->columns(2)->schema([
                    Forms\Components\Select::make('category')->label('Что показывает')->options(self::categoryOptions())
                        ->required()->unique(ignoreRecord: true)->live(),
                    Forms\Components\TextInput::make('path')->label('Адрес страницы')->maxLength(200)
                        ->required(fn (Forms\Get $get): bool => $get('category') !== 'home')
                        ->hidden(fn (Forms\Get $get): bool => $get('category') === 'home')
                        ->unique(ignoreRecord: true)->prefix('shamanka.online/')->regex('/^[A-Za-z0-9_\-\/]+$/')
                        ->helperText('Старый адрес раздела один в один, например uslugi/homes'),
                    Forms\Components\TextInput::make('h1')->label('Заголовок на странице')->maxLength(150)->columnSpanFull()
                        ->hidden(fn (Forms\Get $get): bool => $get('category') === 'home'),
                    Forms\Components\Textarea::make('lead')->label('Вступление')->rows(4)->columnSpanFull()
                        ->hidden(fn (Forms\Get $get): bool => $get('category') === 'home'),
                ]),
                Forms\Components\Tabs\Tab::make('SEO')->schema(SeoFields::make('catalog/og')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')->label('Раздел')
                    ->formatStateUsing(fn (string $state): string => self::categoryOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('h1')->label('Заголовок'),
                Tables\Columns\TextColumn::make('path')->label('Адрес')
                    ->formatStateUsing(fn (?string $state): string => '/'.trim((string) $state, '/').($state ? '/' : '')),
                Tables\Columns\TextColumn::make('seo_title')->label('Title')->limit(50)->toggleable(),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogPages::route('/'),
            'create' => Pages\CreateCatalogPage::route('/create'),
            'edit' => Pages\EditCatalogPage::route('/{record}/edit'),
        ];
    }
}
