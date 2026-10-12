<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Надпись логотипа без знака: в мобильной шапке знак и надпись стоят раздельно.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('wordmark_image')->nullable();
        });

        foreach (DB::table('themes')->pluck('slug') as $slug) {
            $source = public_path("img/logos/{$slug}-wordmark.png");
            if (! File::exists($source)) {
                continue;
            }

            $path = "themes/logos/{$slug}-wordmark.png";
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, File::get($source));
            }

            DB::table('themes')->where('slug', $slug)->update(['wordmark_image' => $path]);
        }
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('wordmark_image');
        });
    }
};
