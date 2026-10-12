<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Зона рисованной карты: область или линия с подсказкой и ссылкой.
 */
class MapZone extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'dashed' => 'boolean'];
    }

    /**
     * Общие зоны в формате карты; если в базе пусто — из config/map.php.
     *
     * @return array<int, array{title: string, text: ?string, href: ?string, shape: string, points: string, featured?: bool, color?: ?string, dashed?: bool}>
     */
    public static function forMap(): array
    {
        $zones = static::query()->orderBy('sort')->orderBy('id')
            ->get(['title', 'text', 'href', 'shape', 'points', 'featured', 'color', 'dashed'])->toArray();

        return $zones ?: config('map.zones', []);
    }

    /**
     * Чистит зоны из редактора: без названия или с нехваткой точек — отбрасываются,
     * координаты приводятся к целым и к формату "x,y x,y".
     *
     * @param  array<int, mixed>|null  $zones
     * @return array<int, array{title: string, text: ?string, href: ?string, shape: string, points: string, featured?: bool, color?: ?string, dashed?: bool}>
     */
    public static function sanitize(?array $zones): array
    {
        return collect($zones ?? [])->filter(fn ($zone) => is_array($zone))->map(function (array $zone): ?array {
            $shape = ($zone['shape'] ?? '') === 'line' ? 'line' : 'polygon';
            preg_match_all('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', (string) ($zone['points'] ?? ''), $pairs, PREG_SET_ORDER);
            $points = array_map(fn (array $pair) => (int) round($pair[1]).','.(int) round($pair[2]), $pairs);
            $title = trim((string) ($zone['title'] ?? ''));

            if ($title === '' || count($points) < ($shape === 'line' ? 2 : 3)) {
                return null;
            }

            return [
                'title' => mb_substr($title, 0, 255),
                'text' => filled($zone['text'] ?? null) ? mb_substr(trim($zone['text']), 0, 500) : null,
                'href' => filled($zone['href'] ?? null) ? mb_substr(trim($zone['href']), 0, 255) : null,
                'shape' => $shape,
                'points' => implode(' ', $points),
                'featured' => (bool) ($zone['featured'] ?? false),
                'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($zone['color'] ?? '')) ? strtolower($zone['color']) : null,
                'dashed' => (bool) ($zone['dashed'] ?? false),
            ];
        })->filter()->values()->all();
    }

    /**
     * Заменяет все общие зоны новым списком (порядок сохраняется).
     *
     * @param  array<int, array<string, mixed>>  $zones  уже очищенные sanitize()
     */
    public static function replaceAll(array $zones): void
    {
        DB::transaction(function () use ($zones): void {
            static::query()->delete();
            foreach ($zones as $sort => $zone) {
                static::create([...$zone, 'sort' => $sort]);
            }
        });
    }
}
