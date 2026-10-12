<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Place;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'статья';

    protected static ?string $pluralModelLabel = 'Статьи';

    protected static ?string $recordTitleAttribute = 'title';

    /** Ширина фото и видео в статье. */
    public const WIDTHS = ['text' => 'По ширине текста', 'wide' => 'Шире текста', 'full' => 'Во всю ширину'];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Forms\Components\Tabs\Tab::make('Текст статьи')->schema([
                    Forms\Components\TextInput::make('title')->label('Заголовок')->required()->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => blank($get('path')) && $state ? $set('path', 'stati/'.Str::slug($state)) : null),
                    Forms\Components\Textarea::make('lead')->label('Вступление под заголовком')->rows(2)->maxLength(400),
                    self::contentBuilder(),
                ]),
                Forms\Components\Tabs\Tab::make('Обложка и факты')->schema([
                    Forms\Components\FileUpload::make('cover')->label('Обложка')->image()->imageEditor()
                        ->disk('public')->directory('articles/covers')->maxSize(12288),
                    Forms\Components\Repeater::make('facts')->label('Коротко о маршруте')->columns(2)->defaultItems(0)
                        ->helperText('Плашки под заголовком: «Время — 4–6 часов», «Сложность — средняя».')
                        ->addActionLabel('Добавить факт')->reorderable()
                        ->schema([
                            Forms\Components\TextInput::make('label')->label('Что')->required()->maxLength(40),
                            Forms\Components\TextInput::make('value')->label('Значение')->required()->maxLength(60),
                        ]),
                ]),
                Forms\Components\Tabs\Tab::make('Публикация и поиск')->columns(2)->schema([
                    Forms\Components\TextInput::make('path')->label('Адрес страницы')->required()->maxLength(200)
                        ->unique(ignoreRecord: true)->prefix('shamanka.online/')->regex('/^[A-Za-z0-9_\-\/]+$/')
                        ->helperText('Для перенесённых статей — старый адрес один в один, например stati/tropa'),
                    Forms\Components\Select::make('status')->label('Статус')->options(Place::STATUSES)->default('draft')->required(),
                    Forms\Components\Select::make('tags')->label('Метки')->relationship('tags', 'name')->multiple()->preload()
                        ->helperText('По меткам подбираются предложения в конце статьи и в блоке «Подборка».'),
                    Forms\Components\TextInput::make('sort')->label('Порядок')->numeric()->default(0),
                    Forms\Components\TextInput::make('seo_title')->label('Заголовок для поисковиков')->maxLength(255)->columnSpanFull(),
                    Forms\Components\Textarea::make('seo_description')->label('Описание для поисковиков')->maxLength(300)->rows(2)->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    /**
     * Редактор статьи: блоки можно добавлять в любом месте, переставлять, дублировать и сворачивать.
     */
    private static function contentBuilder(): Builder
    {
        return Builder::make('content')->label('Блоки статьи')
            ->collapsible()->cloneable()->reorderableWithButtons()->blockNumbers(false)
            ->addActionLabel('Добавить блок')->addBetweenActionLabel('Вставить блок сюда')
            ->blocks([
                Builder\Block::make('text')->label('Текст')->icon('heroicon-o-bars-3-bottom-left')->schema([
                    Forms\Components\RichEditor::make('body')->label('')->required()
                        ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'link', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo']),
                ]),

                Builder\Block::make('image')->label('Фото')->icon('heroicon-o-photo')->columns(2)->schema([
                    Forms\Components\FileUpload::make('image')->label('Фото')->image()->imageEditor()->required()
                        ->disk('public')->directory('articles/images')->maxSize(12288)->columnSpanFull(),
                    Forms\Components\TextInput::make('caption')->label('Подпись')->maxLength(200),
                    Forms\Components\TextInput::make('alt')->label('Что на фото (для поиска и незрячих)')->maxLength(200),
                    Forms\Components\Select::make('width')->label('Ширина')->options(self::WIDTHS)->default('text')->live(),
                    Forms\Components\Select::make('float')->label('Положение')->default('none')
                        ->options(['none' => 'Отдельной строкой', 'left' => 'Слева, текст обтекает', 'right' => 'Справа, текст обтекает'])
                        ->helperText('Обтекание — только на компьютере, на телефоне фото всегда во всю ширину.')
                        ->visible(fn (Get $get): bool => $get('width') === 'text'),
                ]),

                Builder\Block::make('gallery')->label('Галерея')->icon('heroicon-o-squares-2x2')->columns(2)->schema([
                    Forms\Components\FileUpload::make('images')->label('Фото')->image()->multiple()->reorderable()->appendFiles()
                        ->panelLayout('grid')->required()->disk('public')->directory('articles/images')->maxSize(12288)->maxFiles(30)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('layout')->label('Вид')->options(['grid' => 'Сетка', 'carousel' => 'Карусель'])->default('grid'),
                    Forms\Components\Select::make('width')->label('Ширина')->options(self::WIDTHS)->default('wide'),
                    Forms\Components\TextInput::make('caption')->label('Подпись')->maxLength(200)->columnSpanFull(),
                ]),

                Builder\Block::make('video')->label('Видео')->icon('heroicon-o-film')->columns(2)->schema([
                    Forms\Components\Radio::make('source')->label('Откуда')->inline()->default('link')->live()
                        ->options(['link' => 'Ссылка (VK Видео, Rutube, YouTube)', 'file' => 'Файл с компьютера']),
                    Forms\Components\TextInput::make('link')->label('Ссылка на видео')->url()->columnSpanFull()
                        ->helperText('Вставьте обычную ссылку со страницы видео — плеер встроится сам.')
                        ->visible(fn (Get $get): bool => $get('source') !== 'file')
                        ->required(fn (Get $get): bool => $get('source') !== 'file'),
                    Forms\Components\FileUpload::make('file')->label('Видеофайл (MP4, до 60 МБ)')->columnSpanFull()
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])->maxSize(61440)
                        ->disk('public')->directory('articles/videos')
                        ->visible(fn (Get $get): bool => $get('source') === 'file')
                        ->required(fn (Get $get): bool => $get('source') === 'file'),
                    Forms\Components\FileUpload::make('poster')->label('Обложка видео')->image()
                        ->disk('public')->directory('articles/images')->maxSize(12288)
                        ->visible(fn (Get $get): bool => $get('source') === 'file'),
                    Forms\Components\Fieldset::make('Как воспроизводить')->columns(3)
                        ->visible(fn (Get $get): bool => $get('source') === 'file')
                        ->schema([
                            Forms\Components\Toggle::make('autoplay')->label('Запускать сам, без звука'),
                            Forms\Components\Toggle::make('loop')->label('По кругу'),
                            Forms\Components\Toggle::make('controls')->label('Показывать кнопки')->default(true),
                        ]),
                    Forms\Components\Select::make('width')->label('Ширина')->options(self::WIDTHS)->default('wide'),
                    Forms\Components\TextInput::make('caption')->label('Подпись')->maxLength(200),
                ]),

                Builder\Block::make('callout')->label('Совет / важно')->icon('heroicon-o-light-bulb')->columns(2)->schema([
                    Forms\Components\Select::make('kind')->label('Тип')->default('tip')
                        ->options(['tip' => 'Совет', 'warning' => 'Важно', 'info' => 'На заметку']),
                    Forms\Components\TextInput::make('title')->label('Заголовок')->maxLength(120),
                    Forms\Components\RichEditor::make('body')->label('Текст')->required()->columnSpanFull()
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList']),
                ]),

                Builder\Block::make('steps')->label('Этапы маршрута')->icon('heroicon-o-map')->schema([
                    Forms\Components\TextInput::make('title')->label('Заголовок блока')->maxLength(120)->placeholder('Как идти'),
                    Forms\Components\Repeater::make('items')->label('Этапы')->required()->columns(2)->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->addActionLabel('Добавить этап')
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Этап')->required()->maxLength(120),
                            Forms\Components\TextInput::make('time')->label('Время или расстояние')->maxLength(40)->placeholder('20–30 минут'),
                            Forms\Components\Textarea::make('text')->label('Описание')->rows(3)->columnSpanFull(),
                            Forms\Components\FileUpload::make('photo')->label('Фото')->image()->disk('public')
                                ->directory('articles/images')->maxSize(12288)->columnSpanFull(),
                        ]),
                ]),

                Builder\Block::make('places')->label('Подборка жилья и услуг')->icon('heroicon-o-home-modern')->columns(2)->schema([
                    Forms\Components\TextInput::make('title')->label('Заголовок')->maxLength(120)->placeholder('После тропы — в баню'),
                    Forms\Components\Select::make('tag_id')->label('Метка')->options(fn () => Tag::orderBy('name')->pluck('name', 'id'))
                        ->helperText('Покажутся опубликованные объекты с этой меткой.'),
                    Forms\Components\Select::make('category')->label('Только раздел')->options(Place::CATEGORIES)->placeholder('Все разделы'),
                    Forms\Components\Select::make('format')->label('Вид')->default('cards')
                        ->options(['cards' => 'Карточки', 'banner' => 'Баннер с одним объектом']),
                    Forms\Components\TextInput::make('limit')->label('Сколько показать')->numeric()->minValue(1)->maxValue(6)->default(3),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')->label('')->disk('public')->square()->size(48),
                Tables\Columns\TextColumn::make('title')->label('Заголовок')->searchable()->sortable()
                    ->description(fn (Article $record): string => '/'.$record->path.'/'),
                Tables\Columns\SelectColumn::make('status')->label('Статус')->options(Place::STATUSES)->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('updated_at')->label('Изменена')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('sort')
            ->actions([
                Tables\Actions\Action::make('view')->label('На сайте')->icon('heroicon-o-eye')
                    ->url(fn (Article $record): string => '/'.$record->path.'/', shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
