<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Theme extends Model
{
    /**
     * Токены темы с подписями для админки. Значения выводятся на сайт как CSS-переменные --t-{токен}.
     *
     * @var array<string, string>
     */
    public const TOKENS = [
        'page-bg' => 'Фон страницы',
        'header-bg' => 'Шапка',
        'footer-bg' => 'Подвал',
        'logo' => 'Логотип и текст на шапке',
        'heading' => 'Заголовки',
        'text' => 'Основной текст',
        'text-muted' => 'Второстепенный текст',
        'primary' => 'Главная кнопка',
        'on-primary' => 'Текст на главной кнопке',
        'primary-hover' => 'Кнопка при наведении',
        'secondary-button' => 'Вторичная кнопка (рамка и текст)',
        'surface' => 'Карточки',
        'border' => 'Границы',
        'link' => 'Ссылки',
        'icon' => 'Иконки',
        'warning-bg' => 'Плашка-предупреждение: фон',
        'warning-text' => 'Плашка-предупреждение: текст',
        'ice-accent' => 'Ледяной акцент',
        'festive-red' => 'Праздничный красный (гирлянда под шапкой)',
    ];

    protected $guarded = ['id'];

    /**
     * Вручную включённой может быть только одна тема.
     */
    protected static function booted(): void
    {
        static::saved(function (Theme $theme): void {
            if ($theme->is_forced) {
                static::whereKeyNot($theme->getKey())->where('is_forced', true)->update(['is_forced' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tokens' => 'array',
            'map_zones' => 'array',
            'is_active' => 'boolean',
            'is_forced' => 'boolean',
        ];
    }

    public function nightTheme(): BelongsTo
    {
        return $this->belongsTo(self::class, 'night_theme_id');
    }

    /**
     * Попадает ли день в период темы. Период может переходить через Новый год (20.12–10.01).
     */
    public function coversDate(Carbon $date): bool
    {
        if (! $this->starts_on || ! $this->ends_on) {
            return false;
        }

        $day = $date->format('m-d');

        return $this->starts_on <= $this->ends_on
            ? $day >= $this->starts_on && $day <= $this->ends_on
            : $day >= $this->starts_on || $day <= $this->ends_on;
    }

    /**
     * Ночное ли время по часам темы. Интервал может переходить через полночь (17:00–08:00).
     */
    public function isNightAt(Carbon $time): bool
    {
        if (! $this->night_theme_id || ! $this->night_from || ! $this->night_to) {
            return false;
        }

        $now = $time->format('H:i');

        return $this->night_from <= $this->night_to
            ? $now >= $this->night_from && $now < $this->night_to
            : $now >= $this->night_from || $now < $this->night_to;
    }

    /**
     * Строка CSS-переменных для <style> в шаблоне. Берутся только корректные HEX-цвета.
     */
    public function cssVariables(): string
    {
        $lines = collect($this->tokens ?? [])
            ->filter(fn (mixed $value, string $key): bool => is_string($value)
                && preg_match('/^[a-z0-9-]+$/', $key) === 1
                && preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1)
            ->map(fn (string $value, string $key): string => "--t-{$key}: {$value};");

        if (! empty($this->tokens['festive-red'])) {
            $lines->push('--festive-on: 1;');
        }

        return $lines->implode(' ');
    }

    public function mapUrl(): ?string
    {
        return $this->storageUrl($this->map_image);
    }

    public function logoUrl(): ?string
    {
        return $this->storageUrl($this->logo_image);
    }

    public function iconUrl(): ?string
    {
        return $this->storageUrl($this->icon_image);
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? '/storage/'.ltrim($path, '/') : null;
    }
}
