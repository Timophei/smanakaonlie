<?php

namespace App\Filament\Resources\PhotoResource\Pages;

use App\Filament\Resources\PhotoResource;
use App\Models\Photo;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPhotos extends ListRecords
{
    protected static string $resource = PhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulkUpload')->label('Загрузить пачкой')->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Загрузить несколько фото')
                ->form([
                    PhotoResource::imageUpload('images')->label('Фото')->multiple()->maxFiles(50)->reorderable()->required()
                        ->helperText('Можно перетащить сразу всю папку снимков'),
                    Forms\Components\TextInput::make('alt')->label('Что на фото (общее)')->required()->maxLength(255)
                        ->placeholder('Шаманка осенью')->helperText('Потом уточните у каждого прямо в списке'),
                    ...PhotoResource::authorFields(),
                ])
                ->action(function (array $data): void {
                    foreach ($data['images'] as $image) {
                        Photo::create([
                            'image' => $image,
                            'alt' => $data['alt'],
                            'author_name' => $data['author_name'] ?: null,
                            'author_url' => $data['author_url'] ?: null,
                            'location' => $data['location'] ?: null,
                        ]);
                    }
                    Notification::make()->success()->title('Добавлено фото: '.count($data['images']))->send();
                }),
            Actions\CreateAction::make()->label('Добавить одно'),
        ];
    }
}
