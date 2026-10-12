<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Перенос содержимого старого сайта при обновлении — один раз.
     * Повторно перенести можно вручную: php artisan legacy:import
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Artisan::call('legacy:import');
    }

    public function down(): void
    {
        //
    }
};
