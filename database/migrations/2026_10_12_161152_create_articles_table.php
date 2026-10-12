<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Статьи: маршруты, советы, «как добраться». Текст собирается из блоков (JSON).
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('path')->unique(); // адрес страницы, напр. stati/tropa
            $table->string('status')->default('draft')->index();
            $table->string('lead', 400)->nullable();
            $table->string('cover')->nullable();
            $table->json('facts')->nullable();
            $table->json('content')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('articles');
    }
};
