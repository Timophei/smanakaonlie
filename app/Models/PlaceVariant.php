<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceVariant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['photos' => 'array'];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
