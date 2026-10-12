<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogPage;
use App\Models\ContactClick;
use App\Models\OwnerContact;
use App\Models\OwnerPhone;
use App\Models\Photo;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'seo' => CatalogPage::firstWhere('category', 'home'),
            // Предложения для карусели «Останьтесь чуть дольше»: сначала рекомендуемые, остальные вперемешку
            'offers' => Place::where('status', 'published')->whereNotNull('cover')
                ->orderByDesc('is_featured')->inRandomOrder()->limit(8)->get(),
            'photos' => Photo::where('is_published', true)->inRandomOrder()->limit(24)->get(),
        ]);
    }

    /** Список жилья: /uslugi/homes */
    public function hotels(Request $request): View
    {
        return $this->catalog($request, CatalogPage::forCategory('hotel'));
    }

    /**
     * Страница по адресу из админки: раздел каталога, статья или объект.
     * Так старые адреса сайта (/stati/…, /uslugi/…) открываются как раньше.
     */
    public function page(Request $request, string $path): View|RedirectResponse
    {
        $path = trim($path, '/');

        if ($target = config('redirects')[$path] ?? null) {
            return redirect($target, Response::HTTP_MOVED_PERMANENTLY);
        }

        $catalog = CatalogPage::where('path', $path)->where('category', '!=', 'home')->first();
        if ($catalog) {
            return $this->catalog($request, $catalog);
        }

        $article = $this->visible(Article::query())->where('path', $path)->with('tags')->first();
        if ($article) {
            return view('site.article', ['article' => $article, 'related' => $this->relatedPlaces($article), 'seo' => $article, 'seoImage' => $article->cover]);
        }

        $place = $this->visible(Place::query())
            ->where('path', $path)
            ->with(['amenities', 'variants', 'owner.contacts', 'owner.phones', 'tags'])
            ->firstOrFail();

        return view('site.place', [
            'place' => $place,
            'catalog' => CatalogPage::forCategory($place->category),
            'seo' => $place,
            'seoImage' => $place->cover,
        ]);
    }

    /** Раздел каталога: объекты одной категории с фильтром по меткам */
    private function catalog(Request $request, CatalogPage $catalog): View
    {
        $category = $catalog->category;
        $tag = $request->filled('tag') ? Tag::find($request->integer('tag')) : null;

        $places = $this->visible(Place::query())
            ->where('category', $category)
            ->when($tag, fn (Builder $q) => $q->whereHas('tags', fn (Builder $t) => $t->whereKey($tag->id)))
            ->with(['amenities', 'variants'])
            ->orderBy('sort')->orderBy('title')
            ->get();

        $tags = Tag::whereHas('places', fn (Builder $q) => $this->visible($q)->where('category', $category))
            ->orderBy('name')->get();

        return view('site.catalog', [
            'catalog' => $catalog,
            'places' => $places,
            'tags' => $tags,
            'tag' => $tag,
            'seo' => $catalog,
            'seoImage' => $places->firstWhere('cover')?->cover,
        ]);
    }

    /**
     * Объекты с теми же метками, что у статьи: для блока «Рядом» в конце.
     *
     * @return Collection<int, Place>
     */
    private function relatedPlaces(Article $article): Collection
    {
        $tagIds = $article->tags->modelKeys();
        if (! $tagIds) {
            return collect();
        }

        return Place::where('status', 'published')
            ->whereHas('tags', fn (Builder $query) => $query->whereKey($tagIds))
            ->orderByDesc('is_featured')->orderBy('sort')->limit(3)->get();
    }

    /** «Показать телефон»: считаем нажатие и отдаём все номера хозяина с подписями */
    public function phone(Request $request, Place $place): JsonResponse
    {
        $phones = $place->owner?->phones ?? collect();
        abort_unless($this->visible(Place::whereKey($place->id))->exists() && $phones->isNotEmpty(), 404);
        $this->logClick($request, $place, 'phone');

        return response()->json([
            'phones' => $phones->map(fn (OwnerPhone $phone): array => ['number' => $phone->number, 'person' => $phone->person, 'tel' => $phone->telUri()])->values(),
        ]);
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
