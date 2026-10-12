<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Откат подсвеченных зон: если сервер успел их получить — убираем добавленную тропу
 * и лишние колонки, карта возвращается к прежнему виду.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('map_zones', 'featured')) {
            return;
        }

        DB::table('map_zones')->where('title', 'Тропа «33 богатыря»')->where('href', '/stati/tropa/')->where('shape', 'line')->delete();
        Schema::table('map_zones', function (Blueprint $table) {
            $table->dropColumn(['featured', 'color', 'dashed']);
        });
        DB::table('migrations')->where('migration', 'like', '%add_featured_style_to_map_zones')->delete();
    }

    public function down(): void
    {
        //
    }
};
