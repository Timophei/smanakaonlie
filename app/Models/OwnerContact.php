<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerContact extends Model
{
    public const TYPES = [
        'max' => 'MAX',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'site' => 'Сайт или соцсеть',
        'other' => 'Другое',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
