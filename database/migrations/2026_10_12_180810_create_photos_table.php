<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Шаманка в фото»: снимки для карусели на главной с подписью и ссылкой на автора.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('image'); // путь в storage/app/public или /img/… для встроенных
            $table->string('alt', 255); // что на фото — для поиска и незрячих
            $table->string('author_name', 150)->nullable();
            $table->string('author_url')->nullable(); // Instagram, VK, Telegram, сайт
            $table->string('location', 150)->nullable(); // где снято
            $table->boolean('is_published')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        // Три снимка, которые уже стояли на главной в «Шаманке в деталях»
        foreach ([
            ['/img/gallery-1.jpg', 'Двое туристов на утёсе над Иркутом'],
            ['/img/gallery-2.jpg', 'Гостья в красном шарфе на фоне утёса зимой'],
            ['/img/gallery-3.jpg', 'Рябина у деревенского дома'],
        ] as [$image, $alt]) {
            DB::table('photos')->insert(['image' => $image, 'alt' => $alt, 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
