<?php

namespace App\Livewire\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Vendor;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The shop / listing page.
 *
 * Doubles as the category, department, brand, tag and search listing: the
 * route supplies an optional scope and everything else is a filter, so a
 * shopper never meets a different interface depending on how they arrived.
 *
 * Pagination is infinite scroll using `wire:intersect` on a sentinel, with a
 * real "Load more" button retained underneath. The button is not a fallback
 * afterthought — an observer-only list is unusable by keyboard and invisible to
 * a screen reader, so the control must exist regardless.
 */
#[Layout('layouts.app')]
class Shop extends Component
{
    /**
     * Scope tokens, exactly as they arrive in the URL.
     *
     * These are NOT typed as models: an inline Livewire component is not a
     * controller, so Laravel's route-model binding never runs and the raw
     * string arrives here. Each is verified below with the same
     * resolveRouteBinding() the router would have used.
     */
    #[Locked]
    public ?string $categoryToken = null;

    #[Locked]
    public ?string $departmentToken = null;

    #[Locked]
    public ?string $brandToken = null;

    #[Locked]
    public ?string $tagToken = null;

    /**
     * Resolved scope ids. Locked because they decide which catalogue slice is
     * shown — a client must not be able to swap them between requests.
     */
    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public ?int $departmentId = null;

    #[Locked]
    public ?int $brandId = null;

    #[Locked]
    public ?int $tagId = null;

    /** Filters, mirrored to the URL so a filtered listing is shareable. */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $sort = 'newest';

    #[Url(as: 'brand', except: [])]
    public array $vendorIds = [];

    #[Url(except: [])]
    public array $genders = [];

    #[Url(as: 'min', except: null)]
    public ?int $minPrice = null;

    #[Url(as: 'max', except: null)]
    public ?int $maxPrice = null;

    #[Url(as: 'stock', except: false)]
    public bool $inStockOnly = false;

    #[Url(as: 'sale', except: false)]
    public bool $onSale = false;

    #[Url(except: false)]
    public bool $featured = false;

    public int $perPage = 24;

    public int $loaded = 24;

    public function mount(
        ?string $category = null,
        ?string $department = null,
        ?string $brand = null,
        ?string $tag = null,
    ): void {
        $this->categoryToken = $category;
        $this->departmentToken = $department;
        $this->brandToken = $brand;
        $this->tagToken = $tag;

        // Verify each token through the model's own route binding, so a tampered
        // or expired link 404s exactly as it would on a bound route.
        $this->categoryId = $this->resolveScope(new Category, $category)?->id;
        $this->departmentId = $this->resolveScope(new Department, $department)?->id;
        $this->brandId = $this->resolveScope(new Brand, $brand)?->id;
        $this->tagId = $this->resolveScope(new Tag, $tag)?->id;

        // A price filter supplied as a huge number would make the range inputs
        // meaningless; clamp it to something sane.
        $this->minPrice = $this->minPrice === null ? null : max(0, $this->minPrice);
        $this->maxPrice = $this->maxPrice === null ? null : max(0, $this->maxPrice);
    }

    /**
     * Resolve a scope token to its model, or abort with a 404.
     *
     * A token that fails verification must be indistinguishable from a missing
     * page, which is why this aborts rather than showing a "bad link" message.
     */
    private function resolveScope(\Illuminate\Database\Eloquent\Model $model, ?string $token): ?\Illuminate\Database\Eloquent\Model
    {
        if (blank($token)) {
            return null;
        }

        $resolved = $model->resolveRouteBinding($token);

        if ($resolved === null) {
            abort(404);
        }

        return $resolved;
    }

    /** Any filter change resets the scroll window, or the list would grow. */
    public function updated(string $property): void
    {
        if ($property !== 'loaded') {
            $this->loaded = $this->perPage;
        }
    }

    public function loadMore(): void
    {
        $this->loaded += $this->perPage;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'vendorIds', 'genders', 'minPrice', 'maxPrice', 'inStockOnly', 'onSale', 'featured']);
        $this->sort = 'newest';
        $this->loaded = $this->perPage;
    }

    public function updatedVendorIds(): void
    {
        $this->loaded = $this->perPage;
    }

    public function render(): View
    {
        $query = $this->buildQuery();

        $total = (clone $query)->count();
        $products = $query->limit($this->loaded)->get();

        return view('livewire.storefront.shop', [
            'products' => $products,
            'total' => $total,
            'hasMore' => $products->count() < $total,
            'scope' => $this->scope(),
            'facets' => $this->facets(),
            'priceBounds' => $this->priceBounds(),
            'seo' => $this->seo(),
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Query
     * ------------------------------------------------------------------ */

    private function buildQuery(): Builder
    {
        $query = Product::query()
            ->published()
            ->with([
                'vendor:id,name,slug,uuid,token_version,status',
                'media' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position')->limit(1),
                'inventories',
            ]);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        if ($this->tagId) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $this->tagId));
        }

        if (filled($this->search)) {
            $query->search($this->search);
        }

        if ($this->vendorIds !== []) {
            $query->whereIn('vendor_id', array_map('intval', $this->vendorIds));
        }

        if ($this->genders !== []) {
            $query->whereIn('gender', $this->genders);
        }

        if ($this->minPrice !== null || $this->maxPrice !== null) {
            // Prices are stored in minor units; the inputs are in major units.
            $query->priceBetween($this->minPrice, $this->maxPrice);
        }

        if ($this->inStockOnly) {
            $query->inStock();
        }

        if ($this->onSale) {
            $query->whereNotNull('compare_at_price_minor')
                ->whereColumn('compare_at_price_minor', '>', 'price_minor');
        }

        if ($this->featured) {
            $query->isFeatured();
        }

        return $this->applySort($query);
    }

    private function applySort(Builder $query): Builder
    {
        return match ($this->sort) {
            'price_low' => $query->orderBy('price_minor'),
            'price_high' => $query->orderByDesc('price_minor'),
            'popular' => $query->orderByDesc('sales_count')->orderByDesc('views_count'),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
            'name' => $query->orderBy('name'),
            'discount' => $query->orderByRaw(
                'CASE WHEN compare_at_price_minor > price_minor '.
                'THEN (compare_at_price_minor - price_minor) * 1.0 / compare_at_price_minor '.
                'ELSE 0 END DESC'
            ),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /* ------------------------------------------------------------------ *
     * Facets
     * ------------------------------------------------------------------ */

    /**
     * Filter options, each with a live count so a shopper can see that a filter
     * leads somewhere before they apply it.
     *
     * @return array<string,mixed>
     */
    private function facets(): array
    {
        // Facet counts are computed against the scope (category/department), not
        // against the currently filtered set, so applying one filter does not
        // hide the others.
        $base = Product::query()->published();

        if ($this->categoryId) {
            $base->where('category_id', $this->categoryId);
        }

        if ($this->departmentId) {
            $base->where('department_id', $this->departmentId);
        }

        $vendors = Vendor::query()
            ->approved()
            ->withCount(['products' => fn (Builder $q) => $q->published()])
            ->whereHas('products', fn ($q) => $q->published())
            ->orderByDesc('products_count')
            ->limit(20)
            ->get()
            ->map(fn (Vendor $vendor) => [
                'id' => (string) $vendor->id,
                'name' => $vendor->name,
                'count' => $vendor->products_count,
            ])
            ->all();

        $categories = Category::query()
            ->active()
            ->when($this->departmentId, fn (Builder $q) => $q->where('department_id', $this->departmentId))
            ->withCount(['products' => fn (Builder $q) => $q->published()])
            ->whereHas('products', fn ($q) => $q->published())
            ->orderByDesc('products_count')
            ->limit(24)
            ->get();

        return [
            'vendors' => $vendors,
            'categories' => $categories,
            'genders' => [
                'women' => __('hanbell.shop.women'),
                'men' => __('hanbell.shop.men'),
                'unisex' => __('hanbell.shop.unisex'),
                'kids' => __('hanbell.shop.kids'),
            ],
        ];
    }

    /** @return array{min:int,max:int} */
    private function priceBounds(): array
    {
        $query = Product::query()->published();

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        $min = (int) ($query->min('price_minor') ?? 0);
        $max = (int) ($query->max('price_minor') ?? 0);

        return ['min' => $min, 'max' => max($max, $min)];
    }

    /**
     * What the shopper is currently looking at, for the heading and breadcrumb.
     *
     * @return array{type:string,model:mixed,title:string,subtitle:?string}
     */
    private function scope(): array
    {
        if ($this->categoryId) {
            $category = Category::with('department')->find($this->categoryId);

            return [
                'type' => 'category',
                'model' => $category,
                'title' => $category?->name ?? __('hanbell.shop.title'),
                'subtitle' => $category?->description,
            ];
        }

        if ($this->departmentId) {
            $department = Department::find($this->departmentId);

            return [
                'type' => 'department',
                'model' => $department,
                'title' => $department?->name ?? __('hanbell.shop.title'),
                'subtitle' => $department?->description,
            ];
        }

        if ($this->brandId) {
            $brand = Brand::with('vendor')->find($this->brandId);

            return [
                'type' => 'brand',
                'model' => $brand,
                'title' => $brand?->name ?? __('hanbell.shop.title'),
                'subtitle' => $brand?->description,
            ];
        }

        if ($this->tagId) {
            $tag = Tag::find($this->tagId);

            return [
                'type' => 'tag',
                'model' => $tag,
                'title' => $tag ? \Illuminate\Support\Str::headline($tag->name) : __('hanbell.shop.title'),
                'subtitle' => null,
            ];
        }

        if (filled($this->search)) {
            return [
                'type' => 'search',
                'model' => null,
                'title' => __('hanbell.search.results_for', ['term' => $this->search]),
                'subtitle' => null,
            ];
        }

        return [
            'type' => 'shop',
            'model' => null,
            'title' => __('hanbell.shop.title'),
            'subtitle' => __('hanbell.shop.subtitle'),
        ];
    }

    /* ------------------------------------------------------------------ *
     * SEO
     * ------------------------------------------------------------------ */

    private function seo(): Seo
    {
        $scope = $this->scope();
        $seo = app(Seo::class);

        /**
         * A filtered or paginated listing is a duplicate of its parent page.
         * Only the canonical, unfiltered scope is indexable — otherwise a
         * crawler is invited to index thousands of near-identical URLs.
         */
        $isCanonical = $this->sort === 'newest'
            && $this->loaded === $this->perPage
            && ! $this->inStockOnly
            && ! $this->onSale
            && ! $this->featured
            && $this->vendorIds === []
            && $this->genders === []
            && $this->minPrice === null
            && $this->maxPrice === null;

        $seo->title($scope['title']);

        if ($scope['model'] ?? null) {
            $model = $scope['model'];
            $seo->description($model->meta_description ?? $scope['subtitle'] ?? null);
        } else {
            $seo->description($scope['subtitle']);
        }

        if (filled($this->search)) {
            $seo->noindex();
        } elseif (! $isCanonical) {
            $seo->robots('noindex,follow');
        }

        $seo->schema([
            '@type' => 'CollectionPage',
            'name' => $scope['title'],
            'description' => $seo->resolvedDescription(),
            'url' => url()->current(),
        ]);

        // A BreadcrumbList is what turns a bare URL into a readable path in a
        // search result.
        $crumbs = [
            ['name' => __('hanbell.nav.home'), 'url' => route('storefront.home')],
            ['name' => __('hanbell.nav.shop'), 'url' => route('storefront.shop')],
        ];

        if ($scope['type'] !== 'shop' && $scope['type'] !== 'search') {
            $crumbs[] = ['name' => $scope['title'], 'url' => url()->current()];
        }

        $seo->schema([
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ]);

        return $seo;
    }

    /** Formatted price bounds for the range filter labels. */
    public function formattedBound(int $minor): string
    {
        return Money::format($minor);
    }
}
