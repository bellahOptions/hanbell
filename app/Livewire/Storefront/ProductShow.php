<?php

namespace App\Livewire\Storefront;

use App\Enums\AdPlacementKey;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Product detail.
 *
 * The product id is resolved from a signed token in mount(), because an inline
 * Livewire component never receives a route-bound model. The token is verified
 * with the model's own resolveRouteBinding(), so the security properties are the
 * same as a bound route and a tampered link 404s.
 */
#[Layout('layouts.app')]
class ProductShow extends Component
{
    #[Locked]
    public ?int $productId = null;

    /** Currently selected variant, or null when the product has none. */
    public ?int $selectedVariantId = null;

    /** Gallery index. */
    public int $activeImage = 0;

    public function mount(string $product, ?string $slug = null): void
    {
        $resolved = (new Product)->resolveRouteBinding($product);

        if (! $resolved instanceof Product) {
            abort(404);
        }

        $this->productId = $resolved->id;

        // Pre-select the first in-stock variant so the page opens in a
        // purchasable state rather than demanding a choice first.
        $firstAvailable = $resolved->variants()
            ->where('is_active', true)
            ->whereHas('inventory', fn ($q) => $q->whereRaw('quantity_on_hand - quantity_reserved > 0'))
            ->orderBy('position')
            ->first();

        $this->selectedVariantId = $firstAvailable?->id
            ?? $resolved->variants()->where('is_active', true)->orderBy('position')->value('id');

        // The view count is a denormalised counter, not a live COUNT.
        $resolved->recordView();
    }

    public function selectVariant(int $variantId): void
    {
        // Only accept a variant that genuinely belongs to this product.
        $belongs = ProductVariant::where('id', $variantId)
            ->where('product_id', $this->productId)
            ->exists();

        if (! $belongs) {
            return;
        }

        $this->selectedVariantId = $variantId;

        // Jump the gallery to the variant's own image when it has one.
        $variant = ProductVariant::find($variantId);
        $product = $this->product();

        if ($variant && $product) {
            $index = $product->media->search(fn ($media) => $media->variant_id === $variant->id);

            if ($index !== false) {
                $this->activeImage = $index;
            }
        }
    }

    public function setImage(int $index): void
    {
        $this->activeImage = max(0, $index);
    }

    private function product(): ?Product
    {
        return Product::with([
            'vendor',
            'brand',
            'category.department',
            'media',
            'variants.inventory',
            'inventories',
            'tags',
            'attributes.values',
        ])->find($this->productId);
    }

    public function render(): View
    {
        $product = $this->product();

        if (! $product || ! $product->isPublished()) {
            abort(404);
        }

        $variant = $this->selectedVariantId ? ProductVariant::find($this->selectedVariantId) : null;

        return view('livewire.storefront.product-show', [
            'product' => $product,
            'selectedVariant' => $variant,
            'related' => $this->related($product),
            'moreFromVendor' => $this->moreFromVendor($product),
            'seo' => $this->seo($product),
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Related content
     * ------------------------------------------------------------------ */

    /** Same category, excluding this product, in stock first. */
    private function related(Product $product)
    {
        if (! $product->category_id) {
            return collect();
        }

        return Product::query()
            ->published()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with([
                'vendor:id,name,slug,uuid,token_version,status',
                'media' => fn ($q) => $q->orderByDesc('is_primary')->limit(1),
                'inventories',
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get();
    }

    private function moreFromVendor(Product $product)
    {
        return Product::query()
            ->published()
            ->where('vendor_id', $product->vendor_id)
            ->where('id', '!=', $product->id)
            ->with([
                'vendor:id,name,slug,uuid,token_version,status',
                'media' => fn ($q) => $q->orderByDesc('is_primary')->limit(1),
                'inventories',
            ])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
    }

    /* ------------------------------------------------------------------ *
     * SEO
     * ------------------------------------------------------------------ */

    private function seo(Product $product): Seo
    {
        $seo = app(Seo::class);

        $seo->title($product->metaTitle())
            ->description($product->metaDescription())
            ->type('product')
            ->canonical($product->publicUrl())
            ->keywords(array_filter([
                $product->name,
                $product->vendor?->name,
                $product->category?->name,
                $product->made_in_city ? 'made in '.$product->made_in_city : 'made in Nigeria',
                'Nigerian fashion',
            ]));

        if ($image = $product->primaryImage()?->url()) {
            $seo->image($image);
        }

        if (! $product->is_indexable) {
            $seo->noindex();
        }

        // A Rich Results-eligible Product node. Without `offers.availability` and
        // a real price, Google will not surface the price in a result.
        $availability = $product->isInStock()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $seo->schema(array_filter([
            '@type' => 'Product',
            'name' => $product->name,
            'description' => Str::limit(strip_tags((string) ($product->summary ?: $product->description)), 500),
            'sku' => $product->sku,
            'url' => $product->publicUrl(),
            'image' => $product->media->take(4)->map(fn ($media) => $media->url())->all(),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand?->name ?? $product->vendor?->name ?? config('hanbell.name'),
            ],
            'category' => $product->category?->name,
            'material' => $product->material,
            'countryOfOrigin' => 'NG',
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->publicUrl(),
                'priceCurrency' => $product->currency,
                'price' => Money::toGatewayAmount($product->priceFor(), $product->currency),
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $product->vendor?->name ?? config('hanbell.name'),
                ],
            ],
            // AggregateRating is only valid when reviews genuinely exist —
            // emitting it with zero reviews is a structured-data violation.
            'aggregateRating' => $product->rating_count > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) round((float) $product->rating_average, 1),
                'reviewCount' => (string) $product->rating_count,
                'bestRating' => '5',
                'worstRating' => '1',
            ] : null,
        ]));

        $seo->llmSummary($product->llmSummary());

        return $seo;
    }
}
