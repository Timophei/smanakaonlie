<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogPage;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * robots.txt и sitemap.xml. Индексируется только основной домен, тестовый закрыт целиком.
 */
class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $root = config('app.canonical_root');

        $lines = self::isMainHost($request)
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /livewire', 'Disallow: /kontakt/', '', "Sitemap: {$root}/sitemap.xml"]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $root = config('app.canonical_root');

        /** @var Collection<int, array{loc: string, lastmod: ?string}> $urls */
        $urls = collect([['loc' => $root.'/', 'lastmod' => CatalogPage::firstWhere('category', 'home')?->updated_at?->toAtomString()]])
            ->concat(collect(array_keys(CatalogPage::DEFAULTS))->map(fn (string $category) => CatalogPage::forCategory($category))
                ->map(fn (CatalogPage $page) => ['loc' => $root.$page->url(), 'lastmod' => $page->updated_at?->toAtomString()]))
            ->concat(Article::where('status', 'published')->orderBy('path')->get()
                ->map(fn (Article $article) => ['loc' => $root.'/'.$article->path.'/', 'lastmod' => $article->updated_at?->toAtomString()]))
            ->concat(Place::where('status', 'published')->orderBy('path')->get()
                ->map(fn (Place $place) => ['loc' => $root.'/'.$place->path.'/', 'lastmod' => $place->updated_at?->toAtomString()]));

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Запрос пришёл на основной домен (shamanka.online), а не на тестовый. */
    public static function isMainHost(Request $request): bool
    {
        return $request->getHost() === parse_url(config('app.canonical_root'), PHP_URL_HOST);
    }
}
