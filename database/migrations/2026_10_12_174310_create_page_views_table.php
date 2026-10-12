<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Своя простая аналитика: один просмотр страницы — одна строка. IP не храним,
 * посетитель — обезличенный отпечаток, который меняется каждые сутки.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->index();
            $table->date('day')->index(); // по иркутскому времени
            $table->string('path')->index();
            $table->string('visitor', 16);
            $table->boolean('is_entry')->default(false); // пришёл извне, а не перешёл со своей страницы
            $table->string('source', 20); // direct, search, social, maps, ads, campaign, referral, internal
            $table->string('source_name', 100)->nullable();
            $table->string('referrer_host', 150)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('device', 10);
            $table->index(['day', 'visitor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
