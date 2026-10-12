<?php

namespace App\Models;

use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Снимок для «Шаманки в фото» с указанием автора.
 */
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** Относительный адрес картинки на сайте. */
    public function url(): string
    {
        return str_starts_with($this->image, '/') ? $this->image : '/storage/'.$this->image;
    }

    /** Как показать автора: для ссылок на Instagram и Telegram — @ник, если имя не задано. */
    public function authorLabel(): ?string
    {
        if ($this->author_name) {
            return $this->author_name;
        }
        if ($this->author_url && preg_match('~(?:instagram\.com|t\.me|vk\.com)/([A-Za-z0-9_.]+)~', $this->author_url, $match)) {
            return '@'.$match[1];
        }

        return null;
    }

    /**
     * Разметка schema.org для поиска по картинкам: описание, автор, указание авторства.
     *
     * @return array<string, mixed>
     */
    public function structuredData(): array
    {
        $author = $this->authorLabel();

        return array_filter([
            '@type' => 'ImageObject',
            'contentUrl' => config('app.canonical_root').$this->url(),
            'description' => $this->alt,
            'contentLocation' => $this->location ?: 'Шаманка, Иркутская область',
            'creator' => $author ? array_filter(['@type' => 'Person', 'name' => $author, 'url' => $this->author_url]) : null,
            'creditText' => $author,
            'copyrightNotice' => $author ? '© '.$author : null,
        ]);
    }
}
