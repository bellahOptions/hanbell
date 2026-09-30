<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Page;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * XML sitemaps.
 *
 * Split by section and referenced from a sitemap index, because a single file
 * with every product does not scale and because Search Console reports coverage
 * far more usefully when product, category and page URLs are separable.
 *
 * Only indexable, published records are listed: a sitemap that advertises
 * noindex URLs is a contradiction crawlers penalise.
 */
class SitemapController
{
    public function index(): Response
    {
        $sections = ['products', 'categories', 'vendors', 'pages'];

        $xml = $this->render('sitemap.index', [
            'sections' => collect($sections)->map(fn (string $section) => [
                'loc' => route('sitemap.section', $section),
                'lastmod' => now()->toAtomString(),
            ])->all(),
        ]);

        return $this->respond($xml);
    }

    public function section(string $section): Response
    {
        $urls = Cache::remember(
            'sitemap.'.$section,
            now()->addMinutes((int) config('hanbell.seo.sitemap_cache_minutes', 60)),
            fn () => $this->urlsFor($section),
        );

        return $this->respond($this->render('sitemap.section', ['urls' => $urls]));
    }

    /**
     * @return array<int,array{loc:string,lastmod:?string,changefreq:string,priority:string,image:?string}>
     */
    private function urlsFor(string $section): array
    {
        return match ($section) {
            'products' => $this->products(),
            'categories' => $this->categories(),
            'vendors' => $this->vendors(),
            'pages' => $this->pages(),
            default => [],
        };
    }

    private function products(): array
    {
        return Product::query()
            ->indexable()
            ->with(['media' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->orderByDesc('updated_at')
            ->limit(20000)
            ->get()
            ->map(fn (Product $product) => [
                'loc' => $product->publicUrl(),
                'lastmod' => $product->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'image' => $product->primaryImage()?->url(),
            ])
            ->all();
    }

    private function categories(): array
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Category $category) => [
                'loc' => $category->publicUrl(),
                'lastmod' => $category->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'image' => null,
            ]);

        $departments = Department::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Department $department) => [
                'loc' => $department->publicUrl(),
                'lastmod' => $department->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'image' => null,
            ]);

        return $categories->merge($departments)->all();
    }

    private function vendors(): array
    {
        return Vendor::query()
            ->approved()
            ->orderByDesc('is_featured')
            ->limit(5000)
            ->get()
            ->map(fn (Vendor $vendor) => [
                'loc' => $vendor->publicUrl(),
                'lastmod' => $vendor->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'image' => null,
            ])
            ->all();
    }

    private function pages(): array
    {
        $pages = Page::query()
            ->published()
            ->where('is_indexable', true)
            ->get()
            ->map(fn (Page $page) => [
                'loc' => $page->publicUrl(),
                'lastmod' => $page->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => $page->group === 'policy' ? '0.4' : '0.5',
                'image' => null,
            ]);

        // The handful of static routes worth submitting.
        $static = collect([
            ['loc' => route('storefront.home'), 'priority' => '1.0'],
            ['loc' => route('storefront.shop'), 'priority' => '0.9'],
            ['loc' => route('storefront.vendors.index'), 'priority' => '0.6'],
            ['loc' => route('storefront.vendors.apply'), 'priority' => '0.4'],
            ['loc' => route('storefront.contact'), 'priority' => '0.3'],
        ])->map(fn (array $row) => [
            'loc' => $row['loc'],
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => $row['priority'],
            'image' => null,
        ]);

        return $static->merge($pages)->all();
    }

    private function render(string $view, array $data): string
    {
        return response()
            ->view($view, $data)
            ->getContent();
    }

    private function respond(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            // Sitemaps are expensive to build; let crawlers reuse them briefly.
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
