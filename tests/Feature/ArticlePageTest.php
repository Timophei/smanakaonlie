<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Place;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_opens_by_its_old_address_with_or_without_slash(): void
    {
        $article = Article::factory()->create(['path' => 'stati/LesChudes', 'title' => 'Лес чудес']);

        $this->get('/stati/LesChudes/')->assertOk()->assertSee('<h1>Лес чудес</h1>', false);
        $this->get('/stati/LesChudes')->assertOk();
        $this->assertNotNull($article);
    }

    public function test_draft_is_visible_only_to_admin(): void
    {
        $this->get('/stati/tropa/')->assertNotFound();

        $this->actingAs(User::factory()->create());
        $this->get('/stati/tropa/')->assertOk()->assertSee('Тропа «Шаманка»')->assertSee('Черновик — видите только вы');
    }

    public function test_blocks_render_with_their_settings(): void
    {
        Article::factory()->create([
            'path' => 'stati/test',
            'facts' => [['label' => 'Время', 'value' => '4 часа']],
            'content' => [
                ['type' => 'image', 'data' => ['image' => 'articles/images/a.jpg', 'caption' => 'Поляна', 'width' => 'text', 'float' => 'right']],
                ['type' => 'image', 'data' => ['image' => 'articles/images/b.jpg', 'width' => 'full', 'float' => 'left']],
                ['type' => 'gallery', 'data' => ['images' => ['articles/images/c.jpg', 'articles/images/d.jpg'], 'layout' => 'carousel', 'width' => 'wide']],
                ['type' => 'video', 'data' => ['source' => 'link', 'link' => 'https://youtu.be/dQw4w9WgXcQ', 'width' => 'wide']],
                ['type' => 'video', 'data' => ['source' => 'file', 'file' => 'articles/videos/v.mp4', 'autoplay' => true, 'loop' => true, 'controls' => false, 'width' => 'full']],
                ['type' => 'callout', 'data' => ['kind' => 'warning', 'body' => '<p>Берите воду</p>']],
                ['type' => 'unknown', 'data' => []],
            ],
        ]);

        $this->get('/stati/test/')
            ->assertOk()
            ->assertSee('<dt>Время</dt><dd>4 часа</dd>', false)
            ->assertSee('class="b b--text  b--float-right "', false)
            ->assertSee('<figcaption>Поляна</figcaption>', false)
            ->assertSee('class="b b--full "', false)
            ->assertSee('carousel--always', false)
            ->assertSee('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', false)
            ->assertSee('src="/storage/articles/videos/v.mp4"', false)
            ->assertSee('autoplay muted', false)
            ->assertSee('callout--warning', false);
    }

    public function test_related_places_come_from_article_tags(): void
    {
        $tag = Tag::create(['name' => 'Рядом с утёсом']);
        $nearby = Place::factory()->create(['title' => 'Баня у утёса', 'category' => 'bath']);
        $nearby->tags()->attach($tag);
        Place::factory()->create(['title' => 'Дом в другом месте']);
        $article = Article::factory()->create(['path' => 'stati/tag-test']);
        $article->tags()->attach($tag);

        $this->get('/stati/tag-test/')->assertSee('Рядом с маршрутом')->assertSee('Баня у утёса')->assertDontSee('Дом в другом месте');
    }

    public function test_place_pages_still_open(): void
    {
        $place = Place::factory()->create(['path' => 'uslugi/gostinitsy/domik']);

        $this->get('/uslugi/gostinitsy/domik/')->assertOk()->assertSee($place->title);
    }
}
