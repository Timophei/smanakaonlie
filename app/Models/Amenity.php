<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    protected $guarded = ['id'];

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class);
    }
}
