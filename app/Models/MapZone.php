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
        return ['label' => 'array'];
    }

    /**
     * Общие зоны в формате карты; если в базе пусто — из config/map.php.
     *
     * @return array<int, array{title: string, text: ?string, href: ?string, shape: string, points: string, label?: ?array, outline?: ?string}>
     */
    public static function forMap(): array
    {
        $zones = static::query()->orderBy('sort')->orderBy('id')
            ->get(['title', 'text', 'href', 'shape', 'points', 'label', 'outline'])->toArray();

        return $zones ?: config('map.zones', []);
    }

    /**
     * Чистит зоны из редактора: без названия или с нехваткой точек — отбрасываются,
     * координаты приводятся к целым и к формату "x,y x,y".
     *
     * @param  array<int, mixed>|null  $zones
     * @return array<int, array{title: string, text: ?string, href: ?string, shape: string, points: string, label?: ?array, outline?: ?string}>
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
                'label' => self::sanitizeLabel($zone['label'] ?? null),
                'outline' => $shape === 'line' && ($zone['outline'] ?? null) === 'dashed' ? 'dashed' : null,
            ];
        })->filter()->values()->all();
    }

    /**
     * Подпись на карте: плашка или надпись по воде, место, наклон, выноска к зоне.
     *
     * @return array{style: string, x: int, y: int, angle: int, leader: bool}|null
     */
    private static function sanitizeLabel(mixed $label): ?array
    {
        if (! is_array($label) || ! isset($label['x'], $label['y'])) {
            return null;
        }

        return [
            'style' => ($label['style'] ?? '') === 'water' ? 'water' : 'pill',
            'x' => (int) max(0, min(config('map.width'), round((float) $label['x']))),
            'y' => (int) max(0, min(config('map.height'), round((float) $label['y']))),
            'angle' => (int) max(-90, min(90, round((float) ($label['angle'] ?? 0)))),
            'leader' => (bool) ($label['leader'] ?? false),
        ];
    }

    /**
     * Куда указывает выноска подписи: середина линии или центр области.
     *
     * @param  array{shape: string, points: string}  $zone
     * @return array{0: float, 1: float}
     */
    public static function anchorPoint(array $zone): array
    {
        preg_match_all('/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $zone['points'], $pairs, PREG_SET_ORDER);
        $points = array_map(fn (array $pair) => [(float) $pair[1], (float) $pair[2]], $pairs);
        if (! $points) {
            return [0, 0];
        }
        if ($zone['shape'] === 'line') {
            $middle = intdiv(count($points) - 1, 2);
            $next = $points[min($middle + 1, count($points) - 1)];

            return [($points[$middle][0] + $next[0]) / 2, ($points[$middle][1] + $next[1]) / 2];
        }

        return [array_sum(array_column($points, 0)) / count($points), array_sum(array_column($points, 1)) / count($points)];
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
