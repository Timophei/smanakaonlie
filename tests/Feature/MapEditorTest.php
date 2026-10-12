<?php

namespace Tests\Feature;

use App\Filament\Pages\MapEditor;
use App\Models\MapAnchor;
use App\Models\MapZone;
use App\Models\Place;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MapEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_zones_moved_from_config_to_database(): void
    {
        $this->assertSame(count(config('map.zones')) + 1, MapZone::count()); // + тропа «33 богатыря»
        $this->assertSame(config('map.zones')[0]['points'], MapZone::forMap()[0]['points']);
    }

    public function test_sanitize_drops_broken_zones_and_rounds_points(): void
    {
        $zones = MapZone::sanitize([
            ['title' => 'Лес чудес', 'text' => '', 'href' => '/stati/LesChudes/', 'shape' => 'polygon', 'points' => '10.4,20 30,40.6 50,60'],
            ['title' => 'Мало точек', 'shape' => 'polygon', 'points' => '1,2 3,4'],
            ['title' => '', 'shape' => 'line', 'points' => '1,2 3,4'],
            ['title' => 'Тропинка', 'shape' => 'line', 'points' => '1,2 3,4'],
        ]);

        $this->assertCount(2, $zones);
        $this->assertSame('10,20 30,41 50,60', $zones[0]['points']);
        $this->assertNull($zones[0]['text']);
        $this->assertSame('line', $zones[1]['shape']);
    }

    public function test_admin_saves_zones_and_site_shows_them(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/karta')->assertOk()->assertSee('mapZonesEditor', false);

        Livewire::test(MapEditor::class)
            ->set('data.zones', [
                ['title' => 'Лес чудес', 'text' => 'Поляна у утёса', 'href' => '/stati/LesChudes/', 'shape' => 'polygon', 'points' => '100,100 200,100 150,200'],
            ])
            ->call('save')
            ->assertNotified();

        $this->assertSame(['Лес чудес'], MapZone::pluck('title')->all());
        Theme::query()->update(['map_zones' => null]);
        $this->get('/')->assertSee('data-title="Лес чудес"', false)->assertDontSee('data-title="Иркут"', false);
    }

    public function test_theme_editor_opens_and_own_zones_override_common(): void
    {
        $this->actingAs(User::factory()->create());
        $theme = Theme::firstWhere('slug', 'autumn') ?? Theme::first();

        $this->get("/admin/themes/{$theme->id}/edit")->assertOk()->assertSee('mapZonesEditor', false);

        $theme->update(['map_zones' => [['title' => 'Своя зона', 'text' => null, 'href' => null, 'shape' => 'line', 'points' => '0,0 10,10']]]);
        $this->get("/?tema={$theme->slug}")->assertSee('data-title="Своя зона"', false);
    }

    public function test_admin_saves_anchors_and_site_offers_where_am_i_and_places_by_coordinates(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/karta')->assertSee('mapAnchorsEditor', false);

        Livewire::test(MapEditor::class)
            ->set('data.anchors', [
                ['label' => 'Мост', 'x' => 100, 'y' => 900, 'coords' => '52.10, 103.90'],
                ['label' => 'Паром', 'x' => 1400, 'y' => 950, 'coords' => '52.1;103.96'],
                ['label' => 'Утёс', 'x' => 1300, 'y' => 150, 'coords' => '52.13 103.95'],
                ['label' => 'Устье', 'x' => 200, 'y' => 100, 'coords' => '52.13, 103.91'],
                ['label' => 'Без координат', 'x' => 10, 'y' => 10, 'coords' => ''],
            ])
            ->call('save')
            ->assertNotified();

        $this->assertSame(4, MapAnchor::count());

        auth()->logout();
        Place::factory()->create(['title' => 'Дом по координатам', 'lat' => 52.115, 'lng' => 103.93, 'map_x' => null, 'map_y' => null]);
        Place::factory()->create(['title' => 'Дом в Иркутске', 'lat' => 52.28, 'lng' => 104.28, 'map_x' => null, 'map_y' => null]);

        $this->get('/')->assertOk()
            ->assertSee('data-projection=', false)
            ->assertSee('Где я?')
            ->assertSee('data-title="Дом по координатам"', false)
            ->assertDontSee('data-title="Дом в Иркутске"', false);
    }

    public function test_featured_zones_are_highlighted_and_listed_in_legend(): void
    {
        Theme::query()->update(['map_zones' => null]);
        MapZone::replaceAll(MapZone::sanitize([
            ['title' => 'Тропа «33 богатыря»', 'href' => '/stati/tropa/', 'shape' => 'line', 'points' => '0,0 100,100', 'featured' => true, 'color' => '#E11D48', 'dashed' => true],
            ['title' => 'Обычная зона', 'shape' => 'polygon', 'points' => '0,0 10,0 10,10'],
        ]));

        $this->get('/')->assertOk()
            ->assertSee('class="imap-hl imap-hl--line  is-dashed "', false)
            ->assertSee('--zone-color: #e11d48', false)
            ->assertSee('data-zone-show="z0"', false)
            ->assertDontSee('data-zone-show="z1"', false);
    }
}
