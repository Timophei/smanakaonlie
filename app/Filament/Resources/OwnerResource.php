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

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema(self::fields());
    }

    /**
     * Поля хозяина. Используются и в его карточке, и во всплывающем окне из карточки объекта.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function fields(): array
    {
        return [
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('first_name')->label('Имя')->maxLength(100),
                Forms\Components\TextInput::make('last_name')->label('Фамилия')->maxLength(100),
                Forms\Components\TextInput::make('email')->label('Почта')->email()->maxLength(255),
                Forms\Components\TextInput::make('name')->label('Как подписывать')->maxLength(150)
                    ->placeholder('Пусто — «Имя Фамилия»')
                    ->helperText('Так хозяин называется в списках админки и на сайте: «Хозяин: …»'),
                Forms\Components\Textarea::make('admin_note')->label('Заметка админа')
                    ->helperText('Видна только в админке')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Телефоны')
                ->description('Все номера покажутся на сайте по кнопке «Показать телефон», с подписью, чей номер')
                ->schema([
                    Forms\Components\Repeater::make('phones')->label('')->relationship()
                        ->orderColumn('sort')->columns(2)->defaultItems(1)->addActionLabel('Добавить номер')
                        ->schema([
                            Forms\Components\TextInput::make('number')->label('Номер')->tel()->required()->maxLength(30)
                                ->placeholder('+7 900 123-45-67'),
                            Forms\Components\TextInput::make('person')->label('Чей номер')->maxLength(100)
                                ->placeholder('Алёна, жена'),
                        ]),
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Хозяин')->searchable(['name', 'first_name', 'last_name'])->sortable()
                    ->description(fn (Owner $record): ?string => trim($record->first_name.' '.$record->last_name) !== $record->name
                        ? trim($record->first_name.' '.$record->last_name) ?: null : null),
                Tables\Columns\TextColumn::make('phones.number')->label('Телефоны')->listWithLineBreaks()
                    ->formatStateUsing(fn (string $state, Owner $record): string => $state.(($person = $record->phones->firstWhere('number', $state)?->person) ? " — {$person}" : ''))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Почта')->searchable()->toggleable(),
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
