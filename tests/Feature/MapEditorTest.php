<?php

namespace Tests\Feature;

use App\Filament\Pages\MapEditor;
use App\Models\MapZone;
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
        $this->assertSame(count(config('map.zones')), MapZone::count());
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
}
