<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Новое превью главной для мессенджеров и соцсетей: картинка 1200×630 с картой и логотипом,
 * понятные заголовок и описание. Со старого сайта были крошечный логотип и текст с опечатками.
 * Картинка кладётся в загрузки, чтобы её можно было заменить в админке («Разделы и главная»).
 */
return new class extends Migration
{
    public function up(): void
    {
        $source = public_path('img/og-home.jpg');
        if (File::exists($source) && ! Storage::disk('public')->exists('seo/og-home.jpg')) {
            Storage::disk('public')->put('seo/og-home.jpg', File::get($source));
        }

        $home = DB::table('catalog_pages')->where('category', 'home')->first();
        $values = [
            'og_title' => 'Шаманка на Иркуте — путеводитель: тропа на утёс, жильё, бани',
            'og_description' => 'Тропа на утёс «33 богатыря», Лес чудес, места для пикника, жильё, бани, рыбалка и сплавы. Как добраться из Иркутска — всё о Шаманке на одном сайте.',
            'og_image' => 'seo/og-home.jpg',
        ];

        if (! $home) {
            DB::table('catalog_pages')->insert([...$values, 'category' => 'home', 'created_at' => now(), 'updated_at' => now()]);

            return;
        }

        // Меняем только то, что осталось со старого сайта
        $isLegacy = $home->og_image === null || str_starts_with((string) $home->og_image, 'legacy/og-home');
        if ($isLegacy) {
            DB::table('catalog_pages')->where('id', $home->id)->update([...$values, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        //
    }
};
