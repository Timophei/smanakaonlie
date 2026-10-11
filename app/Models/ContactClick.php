<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactClick extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
