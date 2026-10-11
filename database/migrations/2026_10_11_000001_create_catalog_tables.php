<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Хозяева: один человек может держать дом, баню и лодки
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        // Мессенджеры и прочие контакты хозяина: тип + ссылка
        Schema::create('owner_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // max, telegram, whatsapp, site, other
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Объекты каталога: жильё, бани, продукты, река
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->index(); // hotel, bath, food, river
            $table->string('type')->nullable();
            $table->string('title');
            $table->string('path')->unique(); // адрес страницы, напр. uslugi/gostinitsy/dobryy
            $table->string('status')->default('draft')->index(); // draft, published, hidden
            $table->string('short_description', 200)->nullable();
            $table->longText('description')->nullable();

            $table->unsignedSmallInteger('guests_min')->nullable();
            $table->unsignedSmallInteger('guests_max')->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('beds')->nullable();

            $table->unsignedInteger('price_from')->nullable();
            $table->string('price_unit')->nullable();
            $table->string('price_note')->nullable();
            $table->string('checkin_time', 5)->nullable();
            $table->string('checkout_time', 5)->nullable();

            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('bank')->nullable(); // right, left
            $table->unsignedInteger('distance_trail_m')->nullable();
            $table->unsignedInteger('distance_river_m')->nullable();

            $table->boolean('all_year')->default(true);
            $table->json('season_months')->nullable();
            $table->boolean('is_open')->default(true);

            // Картинки лежат файлами в storage/app/public, здесь только пути
            $table->string('cover')->nullable();
            $table->json('gallery')->nullable();

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort')->default(0);
            $table->text('admin_note')->nullable();
            $table->date('checked_at')->nullable();
            $table->timestamps();
        });

        // Варианты размещения внутри объекта (домики турбазы, номера)
        Schema::create('place_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->nullable();
            $table->unsignedSmallInteger('guests')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('note')->nullable();
            $table->json('photos')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('amenity_place', function (Blueprint $table) {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'place_id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('place_tag', function (Blueprint $table) {
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['place_id', 'tag_id']);
        });

        // Нажатия на «Показать телефон» и «Написать»
        Schema::create('contact_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind'); // phone, max, telegram, whatsapp, site, other
            $table->string('from_page')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['contact_clicks', 'place_tag', 'tags', 'amenity_place', 'amenities', 'place_variants', 'places', 'owner_contacts', 'owners'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
