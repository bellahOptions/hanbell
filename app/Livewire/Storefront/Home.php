<?php

namespace App\Livewire\Storefront;

use App\Enums\AdPlacementKey;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Vendor;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The homepage.
 *
 * Structure follows the Nigerian marketplace convention shoppers already know —
 * a left category rail, a dominant hero, a promo strip, then dense product
 * rails — but composed with HanbellShop's own editorial type and green/yellow
 * palette rather than a copy of anyone else's.
 *
 * Every rail is a real query, not a fixture, and every rail degrades to nothing
 * rather than breaking the page when it has no rows yet.
 */
#[Layout('layouts.app')]
class Home extends Component
{
    public function render(): View
    {
        $departments = $this->departments();

        return view('livewire.storefront.home', [
            'departments' => $departments,
            'dealProducts' => $this->deals(),
            'newArrivals' => $this->newArrivals(),
            'trending' => $this->trending(),
            'featuredVendors' => $this->featuredVendors(),
            'categoryRails' => $this->categoryRails($departments),
            'seo' => $this->seo($departments),
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Data
     * ------------------------------------------------------------------ */

    /** @return Collection<int,Department> */
    private function departments(): Collection
    {
        return Department::query()
            ->active()
            ->ordered()
            ->with([
                'categories' => fn ($query) => $query->active()->ordered()->limit(12),
            ])
            ->limit(8)
            ->get();
    }

    /** Products with a genuine markdown — the "deals" rail. */
    private function deals(): Collection
    {
        return $this->baseProductQuery()
            ->whereNotNull('compare_at_price_minor')
            ->whereColumn('compare_at_price_minor', '>', 'price_minor')
            ->orderByRaw('(compare_at_price_minor - price_minor) * 1.0 / compare_at_price_minor DESC')
            ->limit(10)
            ->get();
    }

    private function newArrivals(): Collection
    {
        return $this->baseProductQuery()
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();
    }

    private function trending(): Collection
    {
        return $this->baseProductQuery()
            ->where(function ($query): void {
                $query->where('is_trending', true)->orWhere('sales_count', '>', 0);
            })
            ->orderByDesc('sales_count')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int,Vendor> */
    private function featuredVendors(): Collection
    {
        return Vendor::query()
            ->approved()
            ->where('is_featured', true)
            ->withCount(['products' => fn ($query) => $query->published()])
            ->orderByDesc('products_count')
            ->limit(6)
            ->get();
    }

    /**
     * One rail per department that actually has stock.
     *
     * Capped at three departments: a homepage with a rail for every department
     * is not a homepage, it is a sitemap.
     *
     * @param  Collection<int,Department>  $departments
     * @return Collection<int,array{department:Department,products:Collection<int,Product>}>
     */
    private function categoryRails(Collection $departments): Collection
    {
        return $departments
            ->take(3)
            ->map(function (Department $department) {
                $products = $this->baseProductQuery()
                    ->where('department_id', $department->id)
                    ->orderByDesc('is_featured')
                    ->orderByDesc('published_at')
                    ->limit(10)
                    ->get();

                return ['department' => $department, 'products' => $products];
            })
            ->filter(fn (array $rail) => $rail['products']->isNotEmpty())
            ->values();
    }

    /**
     * The shared product query.
     *
     * Eager loads exactly what a product card reads, so a rail of ten products
     * costs a predictable handful of queries instead of one per card.
     */
    private function baseProductQuery()
    {
        return Product::query()
            ->published()
            ->with([
                'vendor:id,name,slug,uuid,token_version,status',
                'media' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('position')->limit(1),
                'inventories',
            ]);
    }

    /* ------------------------------------------------------------------ *
     * SEO
     * ------------------------------------------------------------------ */

    /**
     * @param  Collection<int,Department>  $departments
     */
    private function seo(Collection $departments): Seo
    {
        $categories = Category::query()->active()->pluck('name')->take(24)->all();

        $seo = app(Seo::class);

        $seo->rawTitle(config('hanbell.name').' — '.config('hanbell.tagline'))
            ->description(config('hanbell.description'))
            ->type('website')
            ->keywords(array_merge(
                ['Nigerian fashion', 'Nigerian brands', 'made in Nigeria', 'online fashion store Nigeria'],
                $departments->pluck('name')->all(),
                $categories,
            ))
            ->image(asset('images/logo.svg'));

        // A short FAQ gives answer engines something concrete to quote, which is
        // the difference between being cited and being ignored.
        $seo->faqs([
            [
                'question' => 'What is HanbellShop?',
                'answer' => 'HanbellShop is a multi-vendor online marketplace that sells fashion made by indigenous Nigerian brands and independent creators, at fair and affordable prices.',
            ],
            [
                'question' => 'Do you ship outside Nigeria?',
                'answer' => 'Yes. HanbellShop delivers nationwide within Nigeria and offers international shipping to selected destinations. Delivery cost and timing are shown at checkout.',
            ],
            [
                'question' => 'Which payment methods are accepted?',
                'answer' => 'HanbellShop accepts local Nigerian payment methods and international cards through trusted payment providers. Available options are shown at checkout based on your currency.',
            ],
            [
                'question' => 'How do I sell my brand on HanbellShop?',
                'answer' => 'Apply through the "Sell with us" page. Every application is reviewed by hand before a brand is approved to list.',
            ],
        ]);

        return $seo;
    }
}
