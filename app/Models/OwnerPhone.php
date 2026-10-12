<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Один из телефонов хозяина, с подписью, чей он.
 */
class OwnerPhone extends Model
{
    protected $guarded = ['id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /** Номер для ссылки tel: — только цифры и плюс. */
    public function telUri(): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', $this->number);
    }
}
