<?php

namespace Tests\Feature;

use App\Filament\Resources\PhotoResource\Pages\ListPhotos;
use App\Models\Photo;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_photos_with_author_credit_and_structured_data(): void
    {
        Photo::factory()->create(['alt' => 'Утёс в тумане', 'author_name' => null, 'author_url' => 'https://instagram.com/baikal_shots']);
        Photo::factory()->hidden()->create(['alt' => 'Скрытое фото']);

        $this->get('/')->assertOk()
            ->assertSee('Шаманка в фото')
            ->assertSee('data-lightbox', false)
            ->assertSee('alt="Утёс в тумане"', false)
            ->assertSee('href="https://instagram.com/baikal_shots" target="_blank" rel="nofollow noopener">@baikal_shots</a>', false)
            ->assertSee('"@type":"ImageObject"', false)
            ->assertSee('"creditText":"@baikal_shots"', false)
            ->assertSee('alt="Двое туристов на утёсе над Иркутом"', false)
            ->assertDontSee('Скрытое фото');
    }

    public function test_home_services_have_quick_menu_and_offers_carousel(): void
    {
        Place::factory()->create(['title' => 'Баня у реки', 'category' => 'bath', 'path' => 'uslugi/bani/test', 'cover' => 'places/covers/a.jpg']);
        Place::factory()->draft()->create(['title' => 'Черновик', 'cover' => 'places/covers/b.jpg']);

        $this->get('/')->assertOk()
            ->assertSee('href="/uslugi/homes/"', false)
            ->assertSee('Пар и отдых')
            ->assertSee('Лодки, сплавы, рыбалка')
            ->assertSee('Баня у реки')
            ->assertSee('href="/uslugi/bani/test/"', false)
            ->assertDontSee('Черновик');
    }

    public function test_admin_uploads_a_batch_of_photos_with_one_author(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/foto')->assertOk();

        Livewire::test(ListPhotos::class)
            ->callAction('bulkUpload', data: [
                'images' => [UploadedFile::fake()->image('Утёс осенью.jpg'), UploadedFile::fake()->image('river.jpg')],
                'alt' => 'Шаманка осенью',
                'author_name' => 'Анна',
                'author_url' => 'https://instagram.com/anna',
                'location' => '',
            ])
            ->assertHasNoActionErrors();

        $uploaded = Photo::where('author_name', 'Анна')->get();
        $this->assertCount(2, $uploaded);
        $this->assertMatchesRegularExpression('~^photos/utes-oseniu-[a-z0-9]{5}\.jpe?g$~', $uploaded->first()->image);
        Storage::disk('public')->assertExists($uploaded->first()->image);
    }
}
