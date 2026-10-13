<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CatalogPage;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->artisan('legacy:import')->assertSuccessful();
    }

    public function test_import_keeps_old_addresses_as_drafts(): void
    {
        $this->assertSame(4, Article::count());
        $this->assertSame(9, Place::count());
        $this->assertSame(0, Place::where('status', '!=', 'draft')->count());
        $this->assertNotNull(Place::firstWhere('path', 'uslugi/bani/tehno-bania'));
        $this->assertNotNull(Article::firstWhere('path', 'stati/tropa'));
        $this->assertEqualsCanonicalizing(['hotel', 'bath', 'food', 'river', 'home'], CatalogPage::pluck('category')->all());
        Storage::disk('public')->assertExists(Place::firstWhere('path', 'uslugi/reka/naLodke')->cover);
    }

    public function test_import_is_repeatable_and_keeps_admin_status(): void
    {
        Place::query()->update(['status' => 'published']);

        $this->artisan('legacy:import')->assertSuccessful();

        $this->assertSame(9, Place::count());
        $this->assertSame(9, Place::where('status', 'published')->count());
    }

    public function test_catalog_sections_open_on_old_addresses_with_their_seo(): void
    {
        Place::query()->update(['status' => 'published']);

        $this->get('/uslugi/Bani/')->assertOk()
            ->assertSee('<h1>Бани</h1>', false)
            ->assertSee('Баня у реки')
            ->assertSee('<meta name="keywords" content="Шамана жилье утес, икрут,">', false);

        $this->get('/uslugi/homes/')->assertOk()
            ->assertSee('<title>Жильё в Шаманке — гостевые дома и домики у Иркута</title>', false)
            ->assertSee('Домик в Шаманке')
            ->assertDontSee('Баня у реки "ШАманка"');

        $this->get('/uslugi/river_gulh/')->assertOk()->assertSee('Молоко у Любы');
        $this->get('/uslugi/reka/river/')->assertOk()->assertSee('Прогулки на лодке по Иркуту');
    }

    public function test_place_links_back_to_its_section_and_shares_with_cover(): void
    {
        $place = Place::firstWhere('path', 'uslugi/reka/naLodke');
        $place->update(['status' => 'published']);

        $this->get('/uslugi/reka/naLodke/')->assertOk()
            ->assertSee('href="/uslugi/reka/river/"', false)
            ->assertSee('<meta property="og:image" content="https://shamanka.online/storage/'.$place->cover.'">', false)
            ->assertSee('<link rel="canonical" href="https://shamanka.online/uslugi/reka/naLodke/">', false);
    }

    public function test_article_and_home_keep_old_seo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/stati/tropa/')->assertOk()
            ->assertSee('<meta name="keywords" content="тропа Шаманка', false);

        $this->get('/')->assertOk()
            ->assertSee('<meta name="keywords" content="Шаманка Открой шаманку', false)
            ->assertSee('https://shamanka.online/img/og/', false);
    }

    public function test_admin_sections_for_every_category_open(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['hotels', 'bani', 'produkty', 'reka', 'razdely', 'articles'] as $slug) {
            $this->get("/admin/{$slug}")->assertOk();
        }
        $this->get('/admin/bani/'.Place::firstWhere('category', 'bath')->id.'/edit')->assertOk();
        $this->get('/admin/razdely/'.CatalogPage::firstWhere('category', 'home')->id.'/edit')->assertOk();
    }
}
