<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Точка объекта на рисованной карте: проценты от ширины и высоты картинки.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->decimal('map_x', 5, 2)->nullable();
            $table->decimal('map_y', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['map_x', 'map_y']);
        });
    }
};
