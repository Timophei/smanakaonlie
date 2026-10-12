<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages;
use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * «Шаманка в фото»: снимки для карусели на главной с подписью автора.
 */
class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static ?string $slug = 'foto';

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Оформление';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Шаманка в фото';

    protected static ?string $modelLabel = 'фото';

    protected static ?string $pluralModelLabel = 'Шаманка в фото';

    /**
     * Загрузка снимка: уменьшаем до 2000 px по длинной стороне, имя файла — латиницей из исходного.
     */
    public static function imageUpload(string $name): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)->image()->disk('public')->directory('photos')
            ->maxSize(20480)
            ->imageResizeMode('contain')->imageResizeTargetWidth('2000')->imageResizeTargetHeight('2000')->imageResizeUpscale(false)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => (Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'shamanka')
                .'-'.Str::lower(Str::random(5)).'.'.$file->guessExtension());
    }

    /**
     * Поля автора — общие для формы снимка и для загрузки пачкой.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function authorFields(): array
    {
        return [
            Forms\Components\TextInput::make('author_name')->label('Автор')->maxLength(150)
                ->helperText('Как подписать: имя или ник. Пусто и есть ссылка на Instagram/Telegram — подпишем @ником.'),
            Forms\Components\TextInput::make('author_url')->label('Ссылка на автора')->url()->maxLength(255)
                ->placeholder('https://instagram.com/…')->helperText('Instagram, VK, Telegram или сайт — откроется по нажатию на подпись'),
            Forms\Components\TextInput::make('location')->label('Где снято')->maxLength(150)->placeholder('Шаманский утёс'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            self::imageUpload('image')->label('Фото')->required()->imageEditor()->columnSpanFull()
                ->hidden(fn (?Photo $record): bool => (bool) $record && str_starts_with((string) $record->image, '/')),
            Forms\Components\Placeholder::make('preview')->label('Фото')->columnSpanFull()
                ->visible(fn (?Photo $record): bool => (bool) $record && str_starts_with((string) $record->image, '/'))
                ->content(fn (Photo $record) => new HtmlString('<img src="'.e($record->url()).'" style="max-width: 480px; border-radius: 12px">')),
            Forms\Components\Textarea::make('alt')->label('Что на фото')->required()->maxLength(255)->rows(2)->columnSpanFull()
                ->helperText('Коротко и по делу: «Вид с утёса на Иркут осенью». Это видят поисковики и незрячие посетители.'),
            ...self::authorFields(),
            Forms\Components\Toggle::make('is_published')->label('Показывать на сайте')->default(true),
            Forms\Components\TextInput::make('sort')->label('Порядок')->numeric()->default(0)
                ->helperText('На сайте фото идут вперемешку; порядок — только для списка здесь'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->size(72)
                    ->getStateUsing(fn (Photo $record): string => $record->url()),
                Tables\Columns\TextInputColumn::make('alt')->label('Что на фото')->rules(['required', 'max:255']),
                Tables\Columns\TextColumn::make('author_name')->label('Автор')->searchable()
                    ->getStateUsing(fn (Photo $record): ?string => $record->authorLabel())
                    ->url(fn (Photo $record): ?string => $record->author_url, shouldOpenInNewTab: true),
                Tables\Columns\ToggleColumn::make('is_published')->label('На сайте'),
                Tables\Columns\TextColumn::make('created_at')->label('Добавлено')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('На сайте'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('author')->label('Указать автора')->icon('heroicon-o-user')
                        ->form(self::authorFields())
                        ->action(fn (Collection $records, array $data) => $records->each->update(array_filter($data, fn ($value) => filled($value))))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('show')->label('Показывать')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('hide')->label('Скрыть')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotos::route('/'),
            'create' => Pages\CreatePhoto::route('/create'),
            'edit' => Pages\EditPhoto::route('/{record}/edit'),
        ];
    }
}
