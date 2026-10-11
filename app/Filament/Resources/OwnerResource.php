<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Models\Owner;
use App\Models\OwnerContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'хозяин';

    protected static ?string $pluralModelLabel = 'Хозяева';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Имя')->required()->maxLength(150),
                Forms\Components\TextInput::make('phone')->label('Телефон')->tel()->maxLength(30),
                Forms\Components\Textarea::make('admin_note')->label('Заметка админа')
                    ->helperText('Видна только в админке')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Мессенджеры и ссылки')
                ->description('На сайте появятся кнопки только заполненных контактов')
                ->schema([
                    Forms\Components\Repeater::make('contacts')->label('')->relationship()
                        ->orderColumn('sort')->columns(4)->defaultItems(0)->addActionLabel('Добавить контакт')
                        ->schema([
                            Forms\Components\Select::make('type')->label('Тип')->options(OwnerContact::TYPES)->required(),
                            Forms\Components\TextInput::make('value')->label('Ссылка или номер')->required()->maxLength(255)->columnSpan(2),
                            Forms\Components\Toggle::make('is_primary')->label('Основной')->inline(false),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Телефон')->searchable(),
                Tables\Columns\TextColumn::make('places_count')->label('Объектов')->counts('places')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }
}
