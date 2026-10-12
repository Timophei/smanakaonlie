<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')->label('Посмотреть на сайте')->icon('heroicon-o-eye')->color('gray')
                ->url(fn (): string => '/'.$this->record->path.'/', shouldOpenInNewTab: true),
            Actions\DeleteAction::make(),
        ];
    }
}
