<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * При переносе хозяевам дали имена по названию объекта. После правки кавычек и опечаток
 * в названиях имена разошлись — подтягиваем их к названию, чтобы на сайте не было
 * «Хозяин: Баня у реки "ШАманка"». Хозяев с настоящими именами это не касается.
 */
return new class extends Migration
{
    public function up(): void
    {
        $normalize = fn (?string $text): string => mb_strtolower(preg_replace('/["«»\s.]+/u', '', (string) $text));

        foreach (DB::table('places')->whereNotNull('owner_id')->get(['owner_id', 'title']) as $place) {
            $owner = DB::table('owners')->where('id', $place->owner_id)->first(['id', 'name', 'first_name', 'last_name']);
            if ($owner && ! $owner->first_name && ! $owner->last_name && $owner->name !== $place->title
                && $normalize($owner->name) === $normalize($place->title)) {
                DB::table('owners')->where('id', $owner->id)->update(['name' => $place->title]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
