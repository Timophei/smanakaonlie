<?php

namespace Tests\Feature;

use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractiveMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_shows_zones(): void
    {
        $this->get('/karta/')
            ->assertOk()
            ->assertSee('data-title="Шаманский утёс"', false)
            ->assertSee('data-title="Подвесной мост"', false);
    }

    public function test_home_page_includes_the_map(): void
    {
        $this->get('/')->assertOk()->assertSee('data-map', false);
    }

    public function test_map_is_collapsed_on_home_and_open_on_its_own_page(): void
    {
        $this->get('/')->assertSee('data-collapsible', false)->assertSee('Развернуть карту')->assertSee('Свернуть карту');
        $this->get('/karta')->assertDontSee('data-collapsible', false);
    }

    public function test_map_shows_pins_only_for_published_places_with_a_point(): void
    {
        $onMap = Place::factory()->create(['map_x' => 40.5, 'map_y' => 37.85]);
        $withoutPoint = Place::factory()->create();
        $draft = Place::factory()->draft()->create(['map_x' => 10, 'map_y' => 10]);

        $this->get('/karta')
            ->assertOk()
            ->assertSee('aria-label="'.e($onMap->title).'"', false)
            ->assertSee('left: 40.5%; top: 37.85%', false)
            ->assertDontSee('aria-label="'.e($withoutPoint->title).'"', false)
            ->assertDontSee('aria-label="'.e($draft->title).'"', false);
    }
}
