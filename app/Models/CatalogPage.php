<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Страница-раздел каталога: список объектов одной категории по своему адресу.
 * Запись с категорией home хранит SEO главной страницы.
 */
class CatalogPage extends Model
{
    /**
     * Адрес и заголовок раздела, если в админке его ещё не завели.
     *
     * @var array<string, array{path: string, h1: string, lead: string}>
     */
    public const DEFAULTS = [
        'hotel' => ['path' => 'uslugi/homes', 'h1' => 'Жильё в Шаманке', 'lead' => 'Домики, комнаты и места для ночлега рядом с рекой и тропами.'],
        'bath' => ['path' => 'uslugi/Bani', 'h1' => 'Бани', 'lead' => 'Бани в Шаманке для отдыха вдвоём, семьёй или компанией.'],
        'food' => ['path' => 'uslugi/river_gulh', 'h1' => 'Деревенские продукты', 'lead' => 'Молоко, овощи и домашние продукты от местных жителей.'],
        'river' => ['path' => 'uslugi/reka/river', 'h1' => 'Активности на реке', 'lead' => 'Лодки, сплавы и рыбалка на Иркуте.'],
    ];

    protected $guarded = ['id'];

    public static function forCategory(string $category): self
    {
        return static::firstWhere('category', $category)
            ?? new static(['category' => $category, ...(self::DEFAULTS[$category] ?? ['path' => '', 'h1' => '', 'lead' => ''])]);
    }

    public function url(): string
    {
        return '/'.trim($this->path, '/').'/';
    }
}
