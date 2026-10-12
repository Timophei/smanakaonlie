<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile/15E148';

    private function hit(array $data, string $host = 'shamanka.online', string $agent = self::BROWSER): void
    {
        $this->withHeader('User-Agent', $agent)
            ->postJson("https://{$host}/a/hit", $data)
            ->assertNoContent();
    }

    public function test_page_shows_counter_script_only_on_main_domain_for_guests(): void
    {
        $this->get('https://shamanka.online/')->assertSee("sendBeacon('/a/hit'", false);
        $this->get('https://t.shamanka.online/')->assertDontSee('/a/hit');
    }

    public function test_view_is_recorded_with_source_and_utm(): void
    {
        $this->hit(['p' => '/uslugi/Bani', 'q' => '?utm_source=vk&utm_medium=post&utm_campaign=leto', 'r' => 'https://vk.com/wall-1']);

        $view = PageView::sole();
        $this->assertSame('/uslugi/Bani/', $view->path);
        $this->assertSame('social', $view->source);
        $this->assertSame('ВКонтакте', $view->source_name);
        $this->assertSame('leto', $view->utm_campaign);
        $this->assertSame('mobile', $view->device);
        $this->assertTrue($view->is_entry);
        $this->assertSame(16, strlen($view->visitor));
    }

    public function test_bots_admin_and_test_domain_are_not_counted(): void
    {
        $this->hit(['p' => '/'], agent: 'Mozilla/5.0 (compatible; YandexBot/3.0)');
        $this->hit(['p' => '/'], host: 't.shamanka.online');
        $this->hit(['p' => 'https://evil.example/']);
        $this->actingAs(User::factory()->create());
        $this->hit(['p' => '/']);

        $this->assertSame(0, PageView::count());
    }

    public function test_admin_report_shows_visitors_sources_and_pages(): void
    {
        $this->hit(['p' => '/', 'r' => 'https://yandex.ru/search/?text=шаманка']);
        $this->hit(['p' => '/stati/tropa/', 'r' => 'https://shamanka.online/']);
        $this->hit(['p' => '/stati/tropa/', 'q' => '?utm_source=telegram&utm_campaign=osen']);

        $this->actingAs(User::factory()->create());
        $this->get('/admin/analitika')->assertOk()
            ->assertSee('Аналитика')
            ->assertSee('Яндекс')
            ->assertSee('Поиск')
            ->assertSee('osen')
            ->assertSee('/stati/tropa/')
            ->assertSee('Телефон');
    }
}
