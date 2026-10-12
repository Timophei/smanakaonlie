<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThemeResource\Pages;
use App\Models\MapZone;
use App\Models\Theme;
use App\Support\ThemeResolver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Оформление';

    protected static ?string $modelLabel = 'тема';

    protected static ?string $pluralModelLabel = 'Темы';

    protected static ?string $recordTitleAttribute = 'name';

    /** Токены, без которых тема не собирается. */
    private const REQUIRED_TOKENS = ['page-bg', 'header-bg', 'footer-bg', 'logo', 'heading', 'text', 'text-muted', 'primary', 'on-primary', 'primary-hover', 'surface', 'border', 'link', 'icon', 'warning-bg', 'warning-text'];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Forms\Components\Tabs\Tab::make('Основное')->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Название')->required()->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, ?string $state) => blank($get('slug')) && $state ? $set('slug', Str::slug($state)) : null),
                    Forms\Components\TextInput::make('slug')->label('Код темы')->required()->maxLength(50)
                        ->unique(ignoreRecord: true)->regex('/^[a-z0-9-]+$/')
                        ->helperText('Латиница и дефис. По нему тему можно посмотреть заранее: /?tema=код'),
                    Forms\Components\Toggle::make('is_active')->label('Тема используется')->default(true),
                    Forms\Components\Toggle::make('is_forced')->label('Включить сейчас вручную')
                        ->helperText('Перекрывает расписание по датам. Может быть включена только одна тема.'),
                    Forms\Components\Fieldset::make('Когда включается сама')->columns(3)->schema([
                        self::dayField('starts_on', 'С'),
                        self::dayField('ends_on', 'По'),
                        Forms\Components\TextInput::make('priority')->label('Приоритет')->numeric()->default(10)
                            ->helperText('Если периоды пересекаются, побеждает больший. У Нового года больше, чем у Зимы.'),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make('Цвета')->schema([
                    Forms\Components\Grid::make(3)->schema(
                        collect(Theme::TOKENS)->map(fn (string $label, string $token) => Forms\Components\ColorPicker::make("tokens.{$token}")
                            ->label($label)
                            ->required(in_array($token, self::REQUIRED_TOKENS, true))
                            ->regex('/^#[0-9a-fA-F]{6}$/'))->values()->all()
                    ),
                ]),

                Forms\Components\Tabs\Tab::make('Логотип')->columns(2)->schema([
                    Forms\Components\FileUpload::make('logo_image')->label('Логотип с надписью (шапка)')->image()
                        ->disk('public')->directory('themes/logos')->maxSize(4096)
                        ->helperText('PNG с прозрачным фоном, цвет — как «Логотип и текст на шапке».'),
                    Forms\Components\FileUpload::make('icon_image')->label('Знак (иконка вкладки и мобильная шапка)')->image()
                        ->disk('public')->directory('themes/logos')->maxSize(2048),
                    Forms\Components\FileUpload::make('wordmark_image')->label('Надпись без знака (мобильная шапка)')->image()
                        ->disk('public')->directory('themes/logos')->maxSize(2048),
                ]),

                Forms\Components\Tabs\Tab::make('Карта')->schema([
                    Forms\Components\FileUpload::make('map_image')->label('Подложка карты')->image()
                        ->disk('public')->directory('themes')->maxSize(12288)
                        ->helperText('Лучше тот же рисунок, перекрашенный под сезон: контуры зон привязаны к расположению утёса, рек, парома и моста.'),
                    Forms\Components\ViewField::make('map_zones')->label('Зоны на карте этой темы')
                        ->view('filament.forms.map-zones-editor')
                        ->viewData(fn (?Theme $record): array => [
                            'image' => $record?->mapUrl() ?? config('map.image'),
                            'width' => config('map.width'),
                            'height' => config('map.height'),
                            'nullable' => true,
                            'defaults' => MapZone::forMap(),
                        ])
                        ->dehydrateStateUsing(fn (?array $state): ?array => MapZone::sanitize($state) ?: null)
                        ->helperText('Обычно не нужно: общие зоны правятся в «Оформление → Карта». Свои — только если у темы другой рисунок.'),
                ]),

                Forms\Components\Tabs\Tab::make('Вечер')->columns(3)->schema([
                    Forms\Components\Select::make('night_theme_id')->label('Вечерний вариант')
                        ->relationship('nightTheme', 'name', fn (Builder $query, ?Theme $record) => $record ? $query->whereKeyNot($record->getKey()) : $query)
                        ->helperText('Например, у Зимы — «Зимний вечер».'),
                    self::timeField('night_from', 'Вечер с'),
                    self::timeField('night_to', 'До утра'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $current = app(ThemeResolver::class)->resolve();

        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('tokens.header-bg')->label(''),
                Tables\Columns\ColorColumn::make('tokens.primary')->label(''),
                Tables\Columns\TextColumn::make('name')->label('Тема')->searchable()
                    ->description(fn (Theme $record): ?string => $record->is($current) ? 'Сейчас на сайте' : null),
                Tables\Columns\TextColumn::make('starts_on')->label('Период')
                    ->state(fn (Theme $record): string => $record->starts_on && $record->ends_on
                        ? self::toDay($record->starts_on).' – '.self::toDay($record->ends_on)
                        : 'только как вечерний вариант или вручную'),
                Tables\Columns\TextColumn::make('priority')->label('Приоритет')->sortable(),
                Tables\Columns\IconColumn::make('is_forced')->label('Вручную')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Используется'),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->actions([
                Tables\Actions\Action::make('preview')->label('Посмотреть')->icon('heroicon-o-eye')
                    ->url(fn (Theme $record): string => '/?tema='.$record->slug, shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()->label('Дублировать')
                    ->beforeReplicaSaved(function (Theme $replica): void {
                        $replica->name .= ' (копия)';
                        $replica->slug .= '-'.Str::lower(Str::random(4));
                        $replica->is_forced = false;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }

    /**
     * День в админке вводится как ДД.ММ, в базе хранится как ММ-ДД (так даты удобно сравнивать).
     */
    private static function dayField(string $name, string $label): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)->label($label)->placeholder('01.09')->mask('99.99')
            ->regex('/^(0[1-9]|[12][0-9]|3[01])\.(0[1-9]|1[0-2])$/')
            ->formatStateUsing(fn (?string $state): ?string => $state ? self::toDay($state) : null)
            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? substr($state, 3, 2).'-'.substr($state, 0, 2) : null);
    }

    private static function timeField(string $name, string $label): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)->label($label)->placeholder('17:00')->mask('99:99')
            ->regex('/^([01][0-9]|2[0-3]):[0-5][0-9]$/');
    }

    private static function toDay(string $monthDay): string
    {
        return substr($monthDay, 3, 2).'.'.substr($monthDay, 0, 2);
    }
}
