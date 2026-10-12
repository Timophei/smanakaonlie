<?php

namespace Tests\Feature;

use App\Models\ContactClick;
use App\Models\Owner;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_hotel_list_open(): void
    {
        $place = Place::factory()->create();

        $this->get('/')->assertOk();
        $this->get('/uslugi/homes/')->assertOk()->assertSee($place->title);
    }

    public function test_place_opens_by_its_path_with_or_without_trailing_slash(): void
    {
        $place = Place::factory()->create(['path' => 'uslugi/gostinitsy/domikivSham']);

        $this->get('/uslugi/gostinitsy/domikivSham')->assertOk()->assertSee($place->title);
        $this->get('/uslugi/gostinitsy/domikivSham/')->assertOk();
    }

    public function test_drafts_are_hidden_from_guests_and_visible_to_admin(): void
    {
        $draft = Place::factory()->draft()->create();

        $this->get('/'.$draft->path)->assertNotFound();
        $this->get('/uslugi/homes')->assertDontSee($draft->title);

        $this->actingAs(User::factory()->create());
        $this->get('/'.$draft->path)->assertOk();
    }

    public function test_links_are_relative_and_canonical_points_to_main_domain(): void
    {
        $place = Place::factory()->create();

        $html = $this->get('http://t.shamanka.online/uslugi/homes')->assertOk()->getContent();

        $this->assertStringNotContainsString('t.shamanka.online', $html);
        $this->assertStringContainsString('href="/'.$place->path.'/"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://shamanka.online/uslugi/homes/">', $html);
    }

    public function test_showing_phone_returns_all_numbers_with_owners_and_counts_click(): void
    {
        $owner = Owner::create(['first_name' => 'Тимофей', 'last_name' => 'Иванов']);
        $owner->phones()->create(['number' => '+7 900 000-00-00', 'sort' => 0]);
        $owner->phones()->create(['number' => '8 (901) 111-22-33', 'person' => 'Алёна, жена', 'sort' => 1]);
        $place = Place::factory()->create(['owner_id' => $owner->id]);

        $this->assertSame('Тимофей Иванов', $owner->name);
        $this->get('/'.$place->path.'/')->assertSee('Показать телефоны');
        $this->postJson("/kontakt/{$place->id}/telefon")->assertOk()->assertExactJson(['phones' => [
            ['number' => '+7 900 000-00-00', 'person' => null, 'tel' => 'tel:+79000000000'],
            ['number' => '8 (901) 111-22-33', 'person' => 'Алёна, жена', 'tel' => 'tel:89011112233'],
        ]]);

        $this->assertSame(1, ContactClick::where('place_id', $place->id)->where('kind', 'phone')->count());
    }

    public function test_messenger_link_redirects_and_counts_click(): void
    {
        $owner = Owner::create(['name' => 'Хозяин']);
        $contact = $owner->contacts()->create(['type' => 'telegram', 'value' => '@hozyain']);
        $place = Place::factory()->create(['owner_id' => $owner->id]);

        $this->get("/kontakt/{$place->id}/{$contact->id}")->assertRedirect('https://t.me/hozyain');

        $this->assertSame(1, ContactClick::where('kind', 'telegram')->count());
    }

    public function test_old_duplicate_address_redirects_permanently(): void
    {
        $this->get('/uslugi/gostinitsy/pervyyotel_xdwf_zcxe_sirf_ctor/')
            ->assertStatus(301)
            ->assertRedirect('/uslugi/gostinitsy/domikivSham/');
    }

    public function test_admin_place_form_lets_open_and_edit_selected_owner(): void
    {
        $this->actingAs(User::factory()->create());
        $owner = Owner::create(['name' => 'Хозяин']);
        $place = Place::factory()->create(['owner_id' => $owner->id]);

        $this->get("/admin/hotels/{$place->id}/edit?tab=-khozyain-tab")->assertOk()
            ->assertSee('/admin/owners/'.$owner->id.'/edit', false);
        $this->get("/admin/owners/{$owner->id}/edit")->assertOk()->assertSee('Добавить номер');
    }
}
