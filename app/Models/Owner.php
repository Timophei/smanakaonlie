<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $guarded = ['id'];

    public function contacts(): HasMany
    {
        return $this->hasMany(OwnerContact::class)->orderBy('sort');
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
