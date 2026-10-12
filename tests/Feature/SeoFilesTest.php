<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_open_on_main_domain_and_closed_on_test_domain(): void
    {
        $this->get('https://shamanka.online/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: https://shamanka.online/sitemap.xml')
            ->assertDontSee("Disallow: /\n", false);

        $this->get('https://t.shamanka.online/robots.txt')->assertOk()
            ->assertSee("Disallow: /\n", false);
    }

    public function test_sitemap_lists_only_published_pages_on_main_domain(): void
    {
        Place::factory()->create(['path' => 'uslugi/gostinitsy/domik', 'status' => 'published']);
        Place::factory()->create(['path' => 'uslugi/gostinitsy/chernovik', 'status' => 'draft']);
        Article::create(['title' => 'Проверка', 'path' => 'stati/proverka', 'status' => 'published', 'content' => []]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>https://shamanka.online/</loc>', false)
            ->assertSee('<loc>https://shamanka.online/uslugi/homes/</loc>', false)
            ->assertSee('<loc>https://shamanka.online/stati/proverka/</loc>', false)
            ->assertSee('<loc>https://shamanka.online/uslugi/gostinitsy/domik/</loc>', false)
            ->assertDontSee('chernovik');
    }

    public function test_metrika_only_on_main_domain_and_not_for_admin(): void
    {
        $this->get('https://shamanka.online/')->assertOk()->assertSee('ym(110251801, "init"', false);
        $this->get('https://t.shamanka.online/')->assertOk()->assertDontSee('mc.yandex.ru');

        $this->actingAs(User::factory()->create());
        $this->get('https://shamanka.online/')->assertOk()->assertDontSee('mc.yandex.ru');
    }

    public function test_www_redirects_to_main_domain_keeping_path(): void
    {
        $this->get('http://www.shamanka.online/uslugi/Bani/?tag=1')
            ->assertStatus(301)
            ->assertRedirect('https://shamanka.online/uslugi/Bani/?tag=1');
    }
}
