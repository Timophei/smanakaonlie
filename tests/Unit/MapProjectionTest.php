<?php

namespace Tests\Unit;

use App\Support\MapProjection;
use PHPUnit\Framework\TestCase;

class MapProjectionTest extends TestCase
{
    /**
     * @return array<int, array{lat: float, lng: float, x: int, y: int}>
     */
    private function anchors(): array
    {
        return [
            ['lat' => 52.10, 'lng' => 103.90, 'x' => 100, 'y' => 900],
            ['lat' => 52.10, 'lng' => 103.96, 'x' => 1400, 'y' => 950],
            ['lat' => 52.13, 'lng' => 103.95, 'x' => 1300, 'y' => 150],
            ['lat' => 52.13, 'lng' => 103.91, 'x' => 200, 'y' => 100],
        ];
    }

    public function test_four_anchors_give_exact_perspective_fit(): void
    {
        $projection = MapProjection::fromAnchors($this->anchors());

        $this->assertNotNull($projection);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $projection->errors($this->anchors()));
        [$x, $y] = $projection->project(52.115, 103.93);
        $this->assertGreaterThan(100, $x);
        $this->assertLessThan(1400, $x);
        $this->assertGreaterThan(100, $y);
        $this->assertLessThan(950, $y);
    }

    public function test_three_anchors_fall_back_to_affine_and_too_few_or_collinear_give_null(): void
    {
        $this->assertNotNull(MapProjection::fromAnchors(array_slice($this->anchors(), 0, 3)));
        $this->assertNull(MapProjection::fromAnchors(array_slice($this->anchors(), 0, 2)));
        $this->assertNull(MapProjection::fromAnchors([
            ['lat' => 52.10, 'lng' => 103.90, 'x' => 100, 'y' => 100],
            ['lat' => 52.11, 'lng' => 103.91, 'x' => 200, 'y' => 200],
            ['lat' => 52.12, 'lng' => 103.92, 'x' => 300, 'y' => 300],
        ]));
    }
}
