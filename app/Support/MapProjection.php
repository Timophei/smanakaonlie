<?php

namespace App\Support;

use App\Models\MapAnchor;

/**
 * Перевод GPS-координат в точку на рисованной карте.
 *
 * Рисунок — вид сверху под углом, поэтому используется проективное преобразование
 * (гомография) по 4+ опорным точкам; по 3 точкам — аффинное. Широта и долгота сначала
 * переводятся в километры от центра опорных точек, чтобы числа были соизмеримы с пикселями.
 * Та же формула повторяется в public/js/map.js для положения гостя.
 */
class MapProjection
{
    private const KM_PER_DEGREE_LAT = 110.574;

    private const KM_PER_DEGREE_LNG_AT_EQUATOR = 111.320;

    /**
     * @param  array<int, float>  $h  8 коэффициентов: x = (h0·e + h1·n + h2) / (h6·e + h7·n + 1), y = (h3·e + h4·n + h5) / (…)
     */
    private function __construct(
        public readonly float $lat0,
        public readonly float $lng0,
        public readonly array $h,
    ) {}

    /** Привязка по точкам из базы; null — если точек меньше трёх или они на одной линии. */
    public static function current(): ?self
    {
        return static::fromAnchors(MapAnchor::query()->get(['x', 'y', 'lat', 'lng'])->toArray());
    }

    /**
     * @param  array<int, array{x: int|float, y: int|float, lat: float, lng: float}>  $anchors
     */
    public static function fromAnchors(array $anchors): ?self
    {
        if (count($anchors) < 3) {
            return null;
        }

        $lat0 = array_sum(array_column($anchors, 'lat')) / count($anchors);
        $lng0 = array_sum(array_column($anchors, 'lng')) / count($anchors);
        $probe = new self($lat0, $lng0, array_fill(0, 8, 0.0));

        $rows = [];
        $values = [];
        $perspective = count($anchors) >= 4;
        foreach ($anchors as $anchor) {
            [$e, $n] = $probe->local((float) $anchor['lat'], (float) $anchor['lng']);
            [$x, $y] = [(float) $anchor['x'], (float) $anchor['y']];
            $rows[] = [$e, $n, 1, 0, 0, 0, ...($perspective ? [-$e * $x, -$n * $x] : [])];
            $values[] = $x;
            $rows[] = [0, 0, 0, $e, $n, 1, ...($perspective ? [-$e * $y, -$n * $y] : [])];
            $values[] = $y;
        }

        $solution = self::leastSquares($rows, $values);
        if ($solution === null) {
            return null;
        }

        return new self($lat0, $lng0, $perspective ? $solution : [...$solution, 0.0, 0.0]);
    }

    /**
     * Точка на рисунке (в пикселях исходной картинки).
     *
     * @return array{0: float, 1: float}
     */
    public function project(float $lat, float $lng): array
    {
        [$e, $n] = $this->local($lat, $lng);
        $h = $this->h;
        $w = $h[6] * $e + $h[7] * $n + 1;

        return [($h[0] * $e + $h[1] * $n + $h[2]) / $w, ($h[3] * $e + $h[4] * $n + $h[5]) / $w];
    }

    /**
     * Насколько опорные точки расходятся с расчётом, в пикселях — для проверки привязки в админке.
     *
     * @param  array<int, array{x: int|float, y: int|float, lat: float, lng: float}>  $anchors
     * @return array<int, float>
     */
    public function errors(array $anchors): array
    {
        return array_map(function (array $anchor): float {
            [$x, $y] = $this->project((float) $anchor['lat'], (float) $anchor['lng']);

            return round(hypot($x - $anchor['x'], $y - $anchor['y']), 1);
        }, $anchors);
    }

    /**
     * Данные для браузера: та же формула в map.js.
     *
     * @return array{lat0: float, lng0: float, h: array<int, float>, width: int, height: int}
     */
    public function toArray(): array
    {
        return ['lat0' => $this->lat0, 'lng0' => $this->lng0, 'h' => $this->h, 'width' => (int) config('map.width'), 'height' => (int) config('map.height')];
    }

    /**
     * Широта и долгота → километры на восток и на север от центра привязки.
     *
     * @return array{0: float, 1: float}
     */
    private function local(float $lat, float $lng): array
    {
        return [
            ($lng - $this->lng0) * self::KM_PER_DEGREE_LNG_AT_EQUATOR * cos(deg2rad($this->lat0)),
            ($lat - $this->lat0) * self::KM_PER_DEGREE_LAT,
        ];
    }

    /**
     * Метод наименьших квадратов: решает (AᵀA)·h = Aᵀb методом Гаусса.
     *
     * @param  array<int, array<int, float>>  $rows
     * @param  array<int, float>  $values
     * @return array<int, float>|null
     */
    private static function leastSquares(array $rows, array $values): ?array
    {
        $size = count($rows[0]);
        $matrix = array_fill(0, $size, array_fill(0, $size + 1, 0.0));
        foreach ($rows as $r => $row) {
            for ($i = 0; $i < $size; $i++) {
                for ($j = 0; $j < $size; $j++) {
                    $matrix[$i][$j] += $row[$i] * $row[$j];
                }
                $matrix[$i][$size] += $row[$i] * $values[$r];
            }
        }

        for ($col = 0; $col < $size; $col++) {
            $pivot = $col;
            for ($r = $col + 1; $r < $size; $r++) {
                if (abs($matrix[$r][$col]) > abs($matrix[$pivot][$col])) {
                    $pivot = $r;
                }
            }
            if (abs($matrix[$pivot][$col]) < 1e-12) {
                return null; // точки на одной линии или совпадают
            }
            [$matrix[$col], $matrix[$pivot]] = [$matrix[$pivot], $matrix[$col]];
            for ($r = 0; $r < $size; $r++) {
                if ($r !== $col) {
                    $factor = $matrix[$r][$col] / $matrix[$col][$col];
                    for ($c = $col; $c <= $size; $c++) {
                        $matrix[$r][$c] -= $factor * $matrix[$col][$c];
                    }
                }
            }
        }

        return array_map(fn (int $i): float => $matrix[$i][$size] / $matrix[$i][$i], range(0, $size - 1));
    }
}
