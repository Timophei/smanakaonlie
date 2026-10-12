<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\CatalogPage;
use App\Models\Owner;
use App\Models\Place;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Переносит содержимое старого сайта shamanka.online из database/legacy/site.json.
 *
 * Тексты, адреса страниц и SEO берутся с оригинала. Новые записи заводятся черновиками;
 * у уже существующих (по адресу) содержимое обновляется, а статус не трогается.
 */
class ImportLegacySite extends Command
{
    protected $signature = 'legacy:import {--file=database/legacy/site.json}';

    protected $description = 'Перенести статьи, объекты и разделы со старого сайта';

    public function handle(): int
    {
        $file = base_path($this->option('file'));
        if (! File::exists($file)) {
            $this->error("Нет файла {$file}");

            return self::FAILURE;
        }

        $site = json_decode(File::get($file), true);
        $cards = $this->cardsByPath($site['listings'] ?? []);

        foreach ($site['articles'] ?? [] as $data) {
            $this->importArticle($data);
        }
        foreach ($site['places'] ?? [] as $data) {
            $this->importPlace($data, $cards);
        }
        foreach ($site['listings'] ?? [] as $data) {
            $this->importListing($data);
        }
        if (! empty($site['home_seo'])) {
            $this->importHome($site['home_seo']);
        }

        $this->info('Готово: статей '.count($site['articles'] ?? []).', объектов '.count($site['places'] ?? []).', разделов '.count($site['listings'] ?? []).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importArticle(array $data): void
    {
        $content = collect($data['content'])->map(function (array $block): array {
            foreach (['image', 'file', 'poster'] as $key) {
                if (! empty($block['data'][$key])) {
                    $block['data'][$key] = $this->image($block['data'][$key]);
                }
            }
            if (! empty($block['data']['images'])) {
                $block['data']['images'] = array_values(array_filter(array_map(fn (string $path) => $this->image($path), $block['data']['images'])));
            }

            return $block;
        })->all();

        $article = Article::firstOrNew(['path' => $data['path']]);
        $article->fill([
            'title' => $data['title'],
            'lead' => $data['lead'] ? Str::limit($data['lead'], 400, '') : null,
            'cover' => $this->image($data['cover']),
            'content' => $content,
            'facts' => null,
            ...$this->seo($data['seo']),
        ]);
        $article->status ??= 'draft';
        $article->save();
        $this->line("статья  /{$article->path}/");
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $cards
     */
    private function importPlace(array $data, array $cards): void
    {
        $card = $cards[$data['path']] ?? $this->cardByTitle($cards, $data);
        $gallery = array_values(array_filter(array_map(fn (string $path) => $this->image($path), $data['gallery'])));
        $cover = array_shift($gallery) ?? $this->image($card['image'] ?? null);

        $place = Place::firstOrNew(['path' => $data['path']]);
        $place->fill([
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'short_description' => ($card['text'] ?? '') !== '' ? Str::limit(str_replace("\n", ' ', $card['text']), 200, '') : $place->short_description,
            'address' => $data['address'] ?? $place->address,
            'cover' => $cover,
            'gallery' => $gallery,
            ...$this->seo($data['seo']),
        ]);
        $place->status ??= 'draft';

        if (! $place->owner_id && ! empty($data['phones'])) {
            $place->owner_id = Owner::firstOrCreate(['phone' => $data['phones'][0]], ['name' => $data['title']])->id;
        }

        $place->save();
        $this->line("объект  /{$place->path}/");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importListing(array $data): void
    {
        $page = CatalogPage::firstOrNew(['category' => $data['category']]);
        $page->fill([
            'path' => $data['path'],
            'h1' => $data['h1'],
            'lead' => $data['lead'],
            ...$this->seo($data['seo']),
        ]);

        // /uslugi/Bani/ на старом сайте был страницей единственной бани — для раздела берём текст с главной
        if ($data['category'] === 'bath') {
            $page->h1 = 'Бани';
            $page->lead = 'После тропы, рыбалки или длинного дня у реки баня здесь особенно к месту. В разделе собраны варианты для отдыха вдвоём, семьёй или компанией.';
        }

        $page->save();
        $this->line("раздел  /{$page->path}/");
    }

    /**
     * SEO главной страницы.
     *
     * @param  array<string, ?string>  $seo
     */
    private function importHome(array $seo): void
    {
        $page = CatalogPage::firstOrNew(['category' => 'home']);
        $page->fill([...$this->seo($seo), 'og_image' => $page->og_image ?? $this->image($seo['og_image_file'] ?? null)]);
        $page->save();
        $this->line('главная /');
    }

    /**
     * @param  array<string, ?string>  $seo
     * @return array<string, ?string>
     */
    private function seo(array $seo): array
    {
        return [
            'seo_title' => $seo['title'] ?? null,
            'seo_description' => isset($seo['description']) ? Str::limit($seo['description'], 300, '') : null,
            'seo_keywords' => isset($seo['keywords']) ? Str::limit($seo['keywords'], 500, '') : null,
            // Заглушки конструктора Битрикса, а не настоящие заголовки
            'og_title' => in_array($seo['og_title'] ?? null, ['Новость детально', 'Страница категрии услуг'], true) ? null : ($seo['og_title'] ?? null),
            'og_description' => isset($seo['og_description']) ? Str::limit($seo['og_description'], 500, '') : null,
        ];
    }

    /**
     * Кладёт фото из database/legacy/images в папку загрузок и возвращает путь в ней.
     */
    private function image(?string $legacyPath): ?string
    {
        if (! $legacyPath) {
            return null;
        }

        $source = base_path('database/legacy/images/'.basename($legacyPath));
        if (! File::exists($source)) {
            return null;
        }

        if (! Storage::disk('public')->exists($legacyPath)) {
            Storage::disk('public')->put($legacyPath, File::get($source));
        }

        return $legacyPath;
    }

    /**
     * Карточки из списков старого сайта (короткое описание и фото), по адресу объекта.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<string, array<string, mixed>>
     */
    private function cardsByPath(array $listings): array
    {
        $cards = [];
        foreach ($listings as $listing) {
            foreach ($listing['cards'] as $card) {
                $key = $card['href'] ?: 'title:'.$card['title'];
                $cards[$key] ??= $card;
            }
        }

        return $cards;
    }

    /**
     * Для карточек без ссылки (продукты) — ищем по имени хозяйки в заголовке.
     *
     * @param  array<string, array<string, mixed>>  $cards
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function cardByTitle(array $cards, array $data): ?array
    {
        foreach ($cards as $key => $card) {
            if (! str_starts_with($key, 'title:')) {
                continue;
            }
            $name = Str::before($card['title'], '.');
            $name = Str::of($name)->replace(['Продукты и цветы у ', 'у '], '')->trim()->substr(0, 3)->lower()->toString();
            if ($name !== '' && Str::contains(Str::lower($data['title']), $name)) {
                return $card;
            }
        }

        return null;
    }
}
