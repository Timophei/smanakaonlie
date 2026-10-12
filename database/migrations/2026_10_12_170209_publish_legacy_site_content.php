<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Перед переводом shamanka.online на новый сайт публикуем перенесённые страницы,
 * чтобы все старые адреса из поиска открывались. Скрытое вручную не трогаем.
 */
return new class extends Migration
{
    public function up(): void
    {
        $file = database_path('legacy/site.json');
        if (app()->runningUnitTests() || ! File::exists($file)) {
            return;
        }

        $site = json_decode(File::get($file), true);

        DB::table('articles')->whereIn('path', array_column($site['articles'] ?? [], 'path'))
            ->where('status', 'draft')->update(['status' => 'published']);
        DB::table('places')->whereIn('path', array_column($site['places'] ?? [], 'path'))
            ->where('status', 'draft')->update(['status' => 'published']);
    }

    public function down(): void
    {
        //
    }
};
