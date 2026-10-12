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
     * Сезонные логотипы: надпись для шапки и знак для иконки вкладки.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('logo_image')->nullable();
            $table->string('icon_image')->nullable();
        });

        foreach (DB::table('themes')->pluck('slug') as $slug) {
            DB::table('themes')->where('slug', $slug)->update([
                'logo_image' => $this->copyLogo("{$slug}-logo.png"),
                'icon_image' => $this->copyLogo("{$slug}-icon.png"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['logo_image', 'icon_image']);
        });
    }

    /**
     * Логотип кладётся в папку загрузок, чтобы его можно было заменить из админки.
     */
    private function copyLogo(string $file): ?string
    {
        $source = public_path('img/logos/'.$file);
        if (! File::exists($source)) {
            return null;
        }

        $path = 'themes/logos/'.$file;
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, File::get($source));
        }

        return $path;
    }
};
