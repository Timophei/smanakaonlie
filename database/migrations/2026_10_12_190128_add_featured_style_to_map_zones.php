<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Подсвеченные» зоны: видны на карте всегда, своим цветом (линия — сплошная или пунктир),
 * и попадают в легенду под картой. Сразу подсвечиваются паром и мост и добавляется
 * тропа «33 богатыря» — примерно по гребню утёса, точнее её рисуют в редакторе.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_zones', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('points');
            $table->string('color', 7)->nullable()->after('featured');
            $table->boolean('dashed')->default(false)->after('color');
        });

        DB::table('map_zones')->where('title', 'Паромная переправа')->update(['featured' => true, 'color' => '#facc15', 'dashed' => true]);
        DB::table('map_zones')->where('title', 'Подвесной мост')->update(['featured' => true, 'color' => '#f8fafc', 'dashed' => false]);

        if (DB::table('map_zones')->exists() && ! DB::table('map_zones')->where('title', 'like', 'Тропа%')->exists()) {
            DB::table('map_zones')->insert([
                'title' => 'Тропа «33 богатыря»',
                'text' => 'Главный маршрут: по гребню Шаманского утёса с видами на Иркут.',
                'href' => '/stati/tropa/',
                'shape' => 'line',
                'points' => '200,430 293,383 400,330 533,270 667,217 800,170 933,130 1067,109 1200,105 1333,108 1467,113',
                'featured' => true,
                'color' => '#e11d48',
                'dashed' => true,
                'sort' => (int) DB::table('map_zones')->max('sort') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('map_zones')->where('title', 'Тропа «33 богатыря»')->delete();
        Schema::table('map_zones', function (Blueprint $table) {
            $table->dropColumn(['featured', 'color', 'dashed']);
        });
    }
};
