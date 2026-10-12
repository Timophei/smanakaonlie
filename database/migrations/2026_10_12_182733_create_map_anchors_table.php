<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Привязка рисованной карты к местности: точка на картинке ↔ её широта и долгота.
 * По 4+ точкам считается перевод GPS-координат в место на рисунке.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_anchors', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100); // «Подвесной мост, правый берег»
            $table->unsignedInteger('x'); // пиксели исходной картинки
            $table->unsignedInteger('y');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_anchors');
    }
};
