<?php

namespace App\Models;

use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'hotel' => 'Жильё',
        'bath' => 'Бани',
        'food' => 'Продукты',
        'river' => 'Река',
    ];

    public const STATUSES = [
        'draft' => 'Черновик',
        'published' => 'Опубликовано',
        'hidden' => 'Скрыто',
    ];

    public const HOTEL_TYPES = [
        'house' => 'Дом целиком',
        'cabin' => 'Домик',
        'room' => 'Комната или номер',
        'camp' => 'Турбаза',
    ];

    public const PRICE_UNITS = [
        'house_night' => 'за дом в сутки',
        'person_night' => 'за человека в сутки',
        'room_night' => 'за номер в сутки',
        'evening' => 'за вечер',
        'hour' => 'за час',
        'person' => 'с человека',
        'piece' => 'за штуку',
    ];

    public const BANKS = [
        'right' => 'Правый берег',
        'left' => 'Левый берег',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'season_months' => 'array',
            'all_year' => 'boolean',
            'is_open' => 'boolean',
            'is_featured' => 'boolean',
            'checked_at' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PlaceVariant::class)->orderBy('sort');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ContactClick::class);
    }
}
