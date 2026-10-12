<?php

namespace App\Models;

use App\Support\MapProjection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Опорная точка привязки карты: место на рисунке и его координаты на местности.
 */
class MapAnchor extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['x' => 'integer', 'y' => 'integer', 'lat' => 'float', 'lng' => 'float'];
    }

    /**
     * Чистит точки из редактора: без координат или вне картинки — отбрасываются.
     * Координаты принимаются в виде «52.0791, 104.0352» (как копирует Яндекс Карты).
     *
     * @param  array<int, mixed>|null  $anchors
     * @return array<int, array{label: string, x: int, y: int, lat: float, lng: float}>
     */
    public static function sanitize(?array $anchors): array
    {
        return collect($anchors ?? [])->filter(fn ($anchor) => is_array($anchor))->map(function (array $anchor): ?array {
            preg_match('/(-?\d{1,2}(?:\.\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:\.\d+)?)/', (string) ($anchor['coords'] ?? ''), $match);
            $x = (int) round((float) ($anchor['x'] ?? -1));
            $y = (int) round((float) ($anchor['y'] ?? -1));

            if (! $match || $x < 0 || $y < 0 || $x > config('map.width') || $y > config('map.height')) {
                return null;
            }

            return [
                'label' => mb_substr(trim((string) ($anchor['label'] ?? '')) ?: 'Точка', 0, 100),
                'x' => $x,
                'y' => $y,
                'lat' => (float) $match[1],
                'lng' => (float) $match[2],
            ];
        })->filter(fn (?array $anchor) => $anchor && abs($anchor['lat']) <= 90 && abs($anchor['lng']) <= 180)->values()->all();
    }

    /**
     * Точки в формате редактора.
     *
     * @return array<int, array{label: string, x: int, y: int, coords: string, error: ?float}>
     */
    public static function forEditor(): array
    {
        $anchors = static::query()->orderBy('sort')->orderBy('id')->get();
        $errors = MapProjection::fromAnchors($anchors->toArray())?->errors($anchors->toArray()) ?? [];

        return $anchors->values()->map(fn (MapAnchor $anchor, int $i) => [
            'label' => $anchor->label, 'x' => $anchor->x, 'y' => $anchor->y,
            'coords' => $anchor->lat.', '.$anchor->lng, 'error' => $errors[$i] ?? null,
        ])->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $anchors  уже очищенные sanitize()
     */
    public static function replaceAll(array $anchors): void
    {
        DB::transaction(function () use ($anchors): void {
            static::query()->delete();
            foreach ($anchors as $sort => $anchor) {
                static::create([...$anchor, 'sort' => $sort]);
            }
        });
    }
}
