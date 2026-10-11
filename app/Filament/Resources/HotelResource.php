<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelResource\Pages;
use App\Models\Place;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class HotelResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $slug = 'hotels';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Гостиницы';

    protected static ?string $modelLabel = 'гостиница';

    protected static ?string $pluralModelLabel = 'Гостиницы';

    protected static ?string $recordTitleAttribute = 'title';

    public const PATH_PREFIX = 'uslugi/gostinitsy/';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'hotel');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('category')->default('hotel'),
            Forms\Components\Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([

                Forms\Components\Tabs\Tab::make('Основное')->columns(2)->schema([
                    Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            if (blank($get('path')) && filled($state)) {
                                $set('path', self::PATH_PREFIX.Str::slug($state));
                            }
                        }),
                    Forms\Components\Select::make('type')->label('Тип')->options(Place::HOTEL_TYPES)->required(),
                    Forms\Components\TextInput::make('path')->label('Адрес страницы')->required()->maxLength(200)
                        ->unique(ignoreRecord: true)->prefix('shamanka.online/')
                        ->regex('/^[A-Za-z0-9_\-\/]+$/')
                        ->helperText('Для перенесённых объектов — старый адрес один в один, чтобы не потерять позиции в поиске'),
                    Forms\Components\Select::make('status')->label('Статус')->options(Place::STATUSES)->default('draft')->required(),
                    Forms\Components\Textarea::make('short_description')->label('Коротко, для карточки в списке')
                        ->maxLength(160)->rows(2)->columnSpanFull(),
                    Forms\Components\RichEditor::make('description')->label('Полное описание')->columnSpanFull()
                        ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo']),
                ]),

                Forms\Components\Tabs\Tab::make('Размещение и цены')->columns(4)->schema([
                    Forms\Components\TextInput::make('guests_min')->label('Гостей от')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('guests_max')->label('Гостей до')->numeric()->minValue(1)->gte('guests_min'),
                    Forms\Components\TextInput::make('bedrooms')->label('Спален')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('beds')->label('Спальных мест')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('price_from')->label('Цена от')->numeric()->minValue(0)->suffix('₽'),
                    Forms\Components\Select::make('price_unit')->label('Единица')->options(Place::PRICE_UNITS),
                    Forms\Components\TextInput::make('checkin_time')->label('Заезд')->placeholder('14:00')->maxLength(5),
                    Forms\Components\TextInput::make('checkout_time')->label('Выезд')->placeholder('12:00')->maxLength(5),
                    Forms\Components\TextInput::make('price_note')->label('Примечание к цене')
                        ->placeholder('В выходные дороже, минимум две ночи')->maxLength(255)->columnSpanFull(),
                    Forms\Components\Repeater::make('variants')->label('Варианты размещения')->relationship()
                        ->helperText('Домики турбазы или номера. Если объект сдаётся целиком, оставьте пустым')
                        ->orderColumn('sort')->columns(6)->defaultItems(0)->columnSpanFull()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('Добавить вариант')
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(150)->columnSpan(2),
                            Forms\Components\TextInput::make('guests')->label('Гостей')->numeric()->minValue(1),
                            Forms\Components\TextInput::make('price')->label('Цена')->numeric()->minValue(0)->suffix('₽'),
                            Forms\Components\TextInput::make('quantity')->label('Сколько таких')->numeric()->minValue(1)->default(1),
                            Forms\Components\TextInput::make('note')->label('Отличия')->maxLength(255),
                            Forms\Components\FileUpload::make('photos')->label('Фото')->image()->multiple()->maxFiles(3)
                                ->reorderable()->disk('public')->directory('places/variants')->maxSize(8192)->columnSpanFull(),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Удобства')->schema([
                    Forms\Components\CheckboxList::make('amenities')->label('Что есть')->relationship(
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('sort'),
                    )->columns(3)->bulkToggleable(),
                    Forms\Components\Fieldset::make('Сезон')->columns(2)->schema([
                        Forms\Components\Toggle::make('all_year')->label('Работает круглый год')->default(true)->live(),
                        Forms\Components\Toggle::make('is_open')->label('Сейчас принимает гостей')->default(true),
                        Forms\Components\CheckboxList::make('season_months')->label('Месяцы работы')->columns(6)->columnSpanFull()
                            ->options([1 => 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'])
                            ->hidden(fn (Get $get): bool => (bool) $get('all_year')),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make('Расположение')->columns(2)->schema([
                    Forms\Components\TextInput::make('address')->label('Адрес')->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('bank')->label('Берег Иркута')->options(Place::BANKS)
                        ->helperText('От этого зависит, нужен ли паром'),
                    Forms\Components\Grid::make(2)->columnSpan(1)->schema([
                        Forms\Components\TextInput::make('distance_trail_m')->label('До тропы')->numeric()->minValue(0)->suffix('м'),
                        Forms\Components\TextInput::make('distance_river_m')->label('До реки')->numeric()->minValue(0)->suffix('м'),
                    ]),
                    Forms\Components\TextInput::make('lat')->label('Широта')->numeric()->minValue(-90)->maxValue(90)->placeholder('52.0000000'),
                    Forms\Components\TextInput::make('lng')->label('Долгота')->numeric()->minValue(-180)->maxValue(180)->placeholder('103.0000000'),
                ]),

                Forms\Components\Tabs\Tab::make('Фото')->schema([
                    Forms\Components\FileUpload::make('cover')->label('Обложка')->image()->imageEditor()
                        ->disk('public')->directory('places/covers')->maxSize(8192),
                    Forms\Components\FileUpload::make('gallery')->label('Галерея')->image()->multiple()->reorderable()
                        ->appendFiles()->panelLayout('grid')->disk('public')->directory('places/gallery')->maxSize(8192)->maxFiles(40)
                        ->helperText('Можно перетащить сразу несколько файлов, порядок меняется мышью'),
                ]),

                Forms\Components\Tabs\Tab::make('Хозяин')->schema([
                    Forms\Components\Select::make('owner_id')->label('Хозяин')->relationship('owner', 'name')
                        ->searchable()->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Имя')->required()->maxLength(150),
                            Forms\Components\TextInput::make('phone')->label('Телефон')->tel()->maxLength(30),
                        ])
                        ->helperText('Телефон и мессенджеры задаются в карточке хозяина и общие для всех его объектов'),
                ]),

                Forms\Components\Tabs\Tab::make('Подбор и поиск')->columns(2)->schema([
                    Forms\Components\Select::make('tags')->label('Метки для подбора предложений')->relationship('tags', 'name')
                        ->multiple()->preload()->columnSpanFull()
                        ->createOptionForm([Forms\Components\TextInput::make('name')->label('Метка')->required()->maxLength(100)]),
                    Forms\Components\TextInput::make('seo_title')->label('Заголовок для поисковиков')->maxLength(255)->columnSpanFull(),
                    Forms\Components\Textarea::make('seo_description')->label('Описание для поисковиков')->maxLength(300)->rows(2)->columnSpanFull(),
                    Forms\Components\Toggle::make('is_featured')->label('Рекомендуем'),
                    Forms\Components\TextInput::make('sort')->label('Порядок в списке')->numeric()->default(0)
                        ->helperText('Меньше — выше'),
                    Forms\Components\DatePicker::make('checked_at')->label('Цены и контакты проверены')->native(false)->displayFormat('d.m.Y'),
                    Forms\Components\Textarea::make('admin_note')->label('Заметка админа')->helperText('Видна только в админке')->rows(3)->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->disk('public')->square()->size(48),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable()
                    ->description(fn (Place $record): ?string => $record->owner?->name),
                Tables\Columns\TextColumn::make('type')->label('Тип')
                    ->formatStateUsing(fn (?string $state): string => Place::HOTEL_TYPES[$state] ?? '—')->toggleable(),
                Tables\Columns\TextColumn::make('guests_max')->label('Гостей')
                    ->formatStateUsing(fn (Place $record): string => $record->guests_min && $record->guests_min != $record->guests_max
                        ? "{$record->guests_min}–{$record->guests_max}" : (string) $record->guests_max)
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('price_from')->label('Цена от, ₽')->type('number')->rules(['nullable', 'integer', 'min:0'])->sortable(),
                Tables\Columns\SelectColumn::make('status')->label('Статус')->options(Place::STATUSES)->selectablePlaceholder(false),
                Tables\Columns\ToggleColumn::make('is_open')->label('Принимает'),
                Tables\Columns\TextColumn::make('clicks_count')->label('Нажатий за 30 дней')
                    ->counts(['clicks' => fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(30))])
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('checked_at')->label('Проверено')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->defaultSort('sort')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Тип')->options(Place::HOTEL_TYPES),
                Tables\Filters\SelectFilter::make('status')->label('Статус')->options(Place::STATUSES),
                Tables\Filters\SelectFilter::make('amenities')->label('Удобства')->relationship('amenities', 'name')->multiple()->preload(),
                Tables\Filters\TernaryFilter::make('is_open')->label('Принимает сейчас'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()->label('Дублировать')
                    ->excludeAttributes(['clicks_count'])
                    ->beforeReplicaSaved(function (Place $replica): void {
                        $replica->title .= ' (копия)';
                        $replica->path .= '-'.Str::lower(Str::random(4));
                        $replica->status = 'draft';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')->label('Опубликовать')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'published']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('hide')->label('Скрыть')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'hidden']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('addTag')->label('Добавить метку')->icon('heroicon-o-tag')
                        ->form([Forms\Components\Select::make('tag_id')->label('Метка')->options(fn () => Tag::orderBy('name')->pluck('name', 'id'))->required()])
                        ->action(fn (Collection $records, array $data) => $records->each(fn (Place $place) => $place->tags()->syncWithoutDetaching([$data['tag_id']])))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
