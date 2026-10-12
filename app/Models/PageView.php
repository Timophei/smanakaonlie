<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Просмотр страницы сайта для встроенной аналитики.
 */
class PageView extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var array<string, string>
     */
    public const SOURCES = [
        'search' => 'Поиск',
        'social' => 'Соцсети и мессенджеры',
        'maps' => 'Карты',
        'ads' => 'Реклама',
        'campaign' => 'Ссылки с метками',
        'referral' => 'Другие сайты',
        'direct' => 'Прямые заходы',
        'internal' => 'Переходы по сайту',
    ];

    /**
     * @var array<string, string>
     */
    public const DEVICES = [
        'mobile' => 'Телефон',
        'tablet' => 'Планшет',
        'desktop' => 'Компьютер',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_entry' => 'boolean',
        ];
    }
}
