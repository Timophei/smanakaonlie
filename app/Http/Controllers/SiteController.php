<?php

namespace App\Http\Controllers;

use App\Models\ContactClick;
use App\Models\OwnerContact;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home');
    }

    /** Список жилья: /uslugi/homes */
    public function hotels(Request $request): View
    {
        $tag = $request->filled('tag') ? Tag::find($request->integer('tag')) : null;

        $places = $this->visible(Place::query())
            ->where('category', 'hotel')
            ->when($tag, fn (Builder $q) => $q->whereHas('tags', fn (Builder $t) => $t->whereKey($tag->id)))
            ->with(['amenities', 'variants'])
            ->orderBy('sort')->orderBy('title')
            ->get();

        $tags = Tag::whereHas('places', fn (Builder $q) => $this->visible($q)->where('category', 'hotel'))
            ->orderBy('name')->get();

        return view('site.hotels', compact('places', 'tags', 'tag'));
    }

    /** Страница объекта по его адресу, например /uslugi/gostinitsy/domikivSham */
    public function place(string $path): View
    {
        $place = $this->visible(Place::query())
            ->where('path', trim($path, '/'))
            ->with(['amenities', 'variants', 'owner.contacts', 'tags'])
            ->firstOrFail();

        return view('site.place', compact('place'));
    }

    /** «Показать телефон»: считаем нажатие и отдаём номер */
    public function phone(Request $request, Place $place): JsonResponse
    {
        abort_unless($this->visible(Place::whereKey($place->id))->exists() && $place->owner?->phone, 404);
        $this->logClick($request, $place, 'phone');

        return response()->json(['phone' => $place->owner->phone]);
    }

    /** «Написать»: считаем нажатие и уводим в мессенджер хозяина */
    public function contact(Request $request, Place $place, OwnerContact $contact): RedirectResponse
    {
        abort_unless($this->visible(Place::whereKey($place->id))->exists() && $contact->owner_id === $place->owner_id, 404);
        $this->logClick($request, $place, $contact->type);

        return redirect()->away($this->contactUrl($contact));
    }

    /** Гости видят только опубликованное, вошедший админ — ещё и черновики. */
    private function visible(Builder $query): Builder
    {
        return auth()->check() ? $query->where('status', '!=', 'hidden') : $query->where('status', 'published');
    }

    private function logClick(Request $request, Place $place, string $kind): void
    {
        if (auth()->check()) {
            return; // свои нажатия из админки не считаем
        }

        ContactClick::create([
            'place_id' => $place->id,
            'kind' => $kind,
            'from_page' => str($request->headers->get('referer', ''))->limit(250, '')->toString() ?: null,
        ]);
    }

    private function contactUrl(OwnerContact $contact): string
    {
        $value = trim($contact->value);
        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return match ($contact->type) {
            'whatsapp' => 'https://wa.me/'.$digits.'?text='.rawurlencode('Здравствуйте, нашёл вас на Шаманка.online'),
            'telegram' => 'https://t.me/'.ltrim($value, '@'),
            default => 'https://'.$value,
        };
    }
}
