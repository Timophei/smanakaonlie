<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Общие зоны рисованной карты переезжают из config/map.php в базу, чтобы править их в админке.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_zones', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('text', 500)->nullable();
            $table->string('href')->nullable();
            $table->string('shape', 10)->default('polygon'); // polygon или line
            $table->text('points'); // "x,y x,y …" в пикселях исходной картинки
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        foreach (config('map.zones', []) as $sort => $zone) {
            DB::table('map_zones')->insert([
                ...collect($zone)->only(['title', 'text', 'href', 'shape', 'points'])->all(),
                'sort' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('map_zones');
    }
};
