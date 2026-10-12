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

    public function test_showing_phone_returns_number_and_counts_click(): void
    {
        $owner = Owner::create(['name' => 'Хозяин', 'phone' => '+7 900 000-00-00']);
        $place = Place::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/kontakt/{$place->id}/telefon")->assertOk()->assertJson(['phone' => '+7 900 000-00-00']);

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
}
