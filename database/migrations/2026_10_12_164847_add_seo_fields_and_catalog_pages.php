<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SEO как на старом сайте (ключевые слова, Open Graph) и страницы-разделы каталога
     * (/uslugi/homes/, /uslugi/Bani/ …) со своими заголовком, вступлением и SEO.
     */
    public function up(): void
    {
        foreach (['articles', 'places'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('seo_keywords', 500)->nullable();
                $table->string('og_title')->nullable();
                $table->string('og_description', 500)->nullable();
                $table->string('og_image')->nullable();
            });
        }

        Schema::create('catalog_pages', function (Blueprint $table) {
            $table->id();
            $table->string('path')->nullable()->unique();
            $table->string('category')->unique();
            $table->string('h1')->nullable();
            $table->text('lead')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pages');
        foreach (['articles', 'places'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['seo_keywords', 'og_title', 'og_description', 'og_image']);
            });
        }
    }
};
