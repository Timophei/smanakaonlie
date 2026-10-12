<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Подписи прямо на карте: белая плашка («Шаманский утёс», «Паромная переправа» с выноской)
 * или надпись по воде («Иркут»), плюс тонкий белый пунктир для линий вроде парома.
 * label — {style: pill|water, x, y, angle, leader}; outline — dashed или null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_zones', function (Blueprint $table) {
            $table->json('label')->nullable()->after('points');
            $table->string('outline', 10)->nullable()->after('label');
        });

        $labels = [
            'Шаманский утёс' => ['style' => 'pill', 'x' => 760, 'y' => 130, 'angle' => -6, 'leader' => false],
            'Шаманка' => ['style' => 'pill', 'x' => 600, 'y' => 420, 'angle' => 0, 'leader' => false],
            'Река Шаманка' => ['style' => 'water', 'x' => 860, 'y' => 228, 'angle' => -9, 'leader' => false],
            'Иркут' => ['style' => 'water', 'x' => 770, 'y' => 690, 'angle' => -12, 'leader' => false],
            'Паромная переправа' => ['style' => 'pill', 'x' => 820, 'y' => 440, 'angle' => 0, 'leader' => true],
            'Подвесной мост' => ['style' => 'pill', 'x' => 810, 'y' => 790, 'angle' => 0, 'leader' => true],
        ];
        foreach ($labels as $title => $label) {
            DB::table('map_zones')->where('title', $title)->whereNull('label')->update(['label' => json_encode($label)]);
        }
        DB::table('map_zones')->where('title', 'Паромная переправа')->update(['outline' => 'dashed']);
    }

    public function down(): void
    {
        Schema::table('map_zones', function (Blueprint $table) {
            $table->dropColumn(['label', 'outline']);
        });
    }
};
