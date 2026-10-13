<?php

namespace Tests\Feature;

use App\Filament\Resources\HotelResource\Pages\ListHotels;
use App\Http\Controllers\QrCardController;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_are_only_for_admin(): void
    {
        $place = Place::factory()->create();

        $this->get("/admin-print/qr?places={$place->id}")->assertRedirect('/admin/login');
    }

    public function test_single_place_sheet_repeats_card_ten_times_with_tracked_link(): void
    {
        $this->actingAs(User::factory()->create());
        $place = Place::factory()->create(['title' => 'Гостевой дом «Добрый»', 'path' => 'uslugi/gostinitsy/domikivSham']);

        $html = $this->get("/admin-print/qr?places={$place->id}")->assertOk()
            ->assertSee('Гостевой дом «Добрый»')
            ->assertSee('Жильё в Шаманке')
            ->assertSee('size: A4', false)
            ->getContent();

        $this->assertSame(10, substr_count($html, 'class="card"'));
        $this->assertSame(10, substr_count($html, '<svg'));
        $this->assertSame("https://shamanka.online/q/{$place->id}", QrCardController::url($place));
        $this->assertStringContainsString("shamanka.online/q/{$place->id}</div>", $html);

        auth()->logout();
        $this->get("/q/{$place->id}")
            ->assertRedirect('/uslugi/gostinitsy/domikivSham/?utm_source=qr&utm_medium=card&utm_campaign=domikivSham');
    }

    public function test_several_places_and_single_card_mode_and_svg_download(): void
    {
        $this->actingAs(User::factory()->create());
        [$a, $b] = Place::factory()->count(2)->create();

        $html = $this->get("/admin-print/qr?places={$a->id},{$b->id}&single=1")->assertOk()
            ->assertSee('size: 90mm 50mm', false)->getContent();
        $this->assertSame(2, substr_count($html, 'class="card"'));

        $this->get("/admin-print/qr/{$a->id}.svg")->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_bulk_action_opens_cards_for_selected_places(): void
    {
        $this->actingAs(User::factory()->create());
        [$a, $b] = Place::factory()->count(2)->create();

        Livewire::test(ListHotels::class)
            ->callTableBulkAction('qr', [$a, $b])
            ->assertRedirect(route('qr.cards', ['places' => "{$a->id},{$b->id}"]));
    }
}
