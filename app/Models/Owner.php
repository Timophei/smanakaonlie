<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Подпись в списках и на сайте: если не задана — «Имя Фамилия»
        static::saving(function (Owner $owner): void {
            if (blank($owner->name)) {
                $owner->name = trim($owner->first_name.' '.$owner->last_name) ?: 'Без имени';
            }
        });
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OwnerContact::class)->orderBy('sort');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(OwnerPhone::class)->orderBy('sort');
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
