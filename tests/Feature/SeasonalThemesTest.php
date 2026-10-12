<?php

namespace Tests\Feature;

use App\Models\Theme;
use App\Support\ThemeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeasonalThemesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function datesAndThemes(): array
    {
        return [
            'середина октября — осень' => ['2026-10-12 12:00', 'autumn'],
            'июль — лето' => ['2026-07-15 12:00', 'summer'],
            'май — весна' => ['2026-05-10 12:00', 'spring'],
            'ноябрь днём — зима' => ['2026-11-20 12:00', 'winter'],
            'ноябрь вечером — зимний вечер' => ['2026-11-20 19:30', 'winter-evening'],
            'ранним утром ещё вечер' => ['2026-02-03 06:00', 'winter-evening'],
            'Новый год перекрывает зиму' => ['2026-12-31 12:00', 'new-year'],
            'период через Новый год' => ['2027-01-05 23:00', 'new-year'],
            'после праздников снова зима' => ['2027-01-11 12:00', 'winter'],
        ];
    }

    #[DataProvider('datesAndThemes')]
    public function test_theme_follows_calendar_and_time_of_day(string $moment, string $expected): void
    {
        $now = Carbon::parse($moment, 'Asia/Irkutsk');

        $this->assertSame($expected, app(ThemeResolver::class)->resolve(now: $now)?->slug);
    }

    public function test_manually_forced_theme_overrides_calendar_and_only_one_is_forced(): void
    {
        Theme::where('slug', 'summer')->first()->update(['is_forced' => true]);
        Theme::where('slug', 'spring')->first()->update(['is_forced' => true]);

        $this->assertSame(1, Theme::where('is_forced', true)->count());
        $this->assertSame('spring', app(ThemeResolver::class)->resolve(now: Carbon::parse('2026-10-12 12:00'))?->slug);
    }

    public function test_inactive_theme_is_skipped(): void
    {
        Theme::where('slug', 'autumn')->update(['is_active' => false]);

        $this->assertNotSame('autumn', app(ThemeResolver::class)->resolve(now: Carbon::parse('2026-10-12 12:00'))?->slug);
    }

    public function test_page_outputs_theme_colours_and_map(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 04:00', 'UTC'));

        $this->get('/')
            ->assertOk()
            ->assertSee('--t-page-bg: #FBF4E8;', false)
            ->assertSee('data-theme="autumn"', false)
            ->assertSee('/storage/themes/02-autumn.jpg', false);
    }

    public function test_any_theme_can_be_previewed_by_link(): void
    {
        $this->get('/?tema=new-year')
            ->assertOk()
            ->assertSee('data-theme="new-year"', false)
            ->assertSee('--festive-on: 1;', false);
    }

    public function test_unsafe_token_values_are_not_printed(): void
    {
        Theme::where('slug', 'autumn')->update(['tokens' => json_encode(['page-bg' => 'red;}</style><script>', 'text' => '#123456'])]);

        $this->get('/?tema=autumn')->assertSee('--t-text: #123456;', false)->assertDontSee('red;}</style><script>', false);
    }

    public function test_header_and_browser_tab_use_the_theme_logo(): void
    {
        $this->get('/?tema=winter')
            ->assertOk()
            ->assertSee('src="/storage/themes/logos/winter-logo.png"', false)
            ->assertSee('aria-label="Открой Шаманку — на главную"', false)
            ->assertSee('src="/storage/themes/logos/winter-wordmark.png"', false)
            ->assertSee('<link rel="icon" type="image/png" href="/storage/themes/logos/winter-icon.png">', false);

        $this->get('/uslugi/homes?tema=new-year')->assertSee('src="/storage/themes/logos/new-year-logo.png"', false);
    }
}
