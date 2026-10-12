<?php

namespace App\Filament\Forms;

use Filament\Forms;

/**
 * Поля для поисковиков и превью в соцсетях — общие для статей, объектов и разделов.
 */
class SeoFields
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function make(string $imageDirectory = 'seo'): array
    {
        return [
            Forms\Components\Fieldset::make('Поисковики')->columns(1)->schema([
                Forms\Components\TextInput::make('seo_title')->label('Заголовок (title)')->maxLength(255),
                Forms\Components\Textarea::make('seo_description')->label('Описание (description)')->maxLength(300)->rows(2),
                Forms\Components\Textarea::make('seo_keywords')->label('Ключевые слова (keywords)')->maxLength(500)->rows(2),
            ]),
            Forms\Components\Fieldset::make('Превью в соцсетях и мессенджерах')->columns(1)->schema([
                Forms\Components\TextInput::make('og_title')->label('Заголовок')->maxLength(255)
                    ->helperText('Пусто — возьмётся заголовок для поисковиков'),
                Forms\Components\Textarea::make('og_description')->label('Описание')->maxLength(500)->rows(2),
                Forms\Components\FileUpload::make('og_image')->label('Картинка')->image()
                    ->disk('public')->directory($imageDirectory)->maxSize(8192)
                    ->helperText('Пусто — возьмётся обложка'),
            ]),
        ];
    }
}
