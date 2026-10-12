<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * У хозяина — имя, фамилия, почта и сколько угодно телефонов с подписью
 * («Алёна, жена»). Единственный старый телефон переносится первым номером.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('email')->nullable()->after('last_name');
        });

        Schema::create('owner_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('person', 100)->nullable(); // чей номер: «Алёна, жена»
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        foreach (DB::table('owners')->whereNotNull('phone')->where('phone', '!=', '')->get(['id', 'phone']) as $owner) {
            DB::table('owner_phones')->insert([
                'owner_id' => $owner->id, 'number' => $owner->phone, 'sort' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('phone')->nullable();
        });
        foreach (DB::table('owner_phones')->orderBy('sort')->get() as $phone) {
            DB::table('owners')->where('id', $phone->owner_id)->whereNull('phone')->update(['phone' => $phone->number]);
        }
        Schema::dropIfExists('owner_phones');
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'email']);
        });
    }
};
