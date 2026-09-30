<?php

namespace App\Http\Controllers\Storefront;

use App\Models\Category;
use App\Models\Department;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use App\Support\Money;
use App\Support\Settings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /llms.txt and /llms-full.txt.
 *
 * `llms.txt` is the emerging convention for giving language models a compact,
 * curated map of a site: a short factual summary plus links to the canonical
 * pages, so an answer engine can retrieve accurate information without
 * crawling (and guessing at) a tokenised, session-varying storefront.
 *
 * The plain-text Markdown shape is intentional — it is what the convention
 * specifies and what models consume most reliably.
 */
class LlmController
{
    public function index(): Response
    {
        if (! config('hanbell.seo.llms_txt_enabled', true)) {
            abort(404);
        }

        $content = Cache::remember('llms.txt', now()->addMinutes(30), fn () => $this->buildIndex());

        return $this->respond($content);
    }

    /**
     * The full catalogue as plain text. Deliberately not cached for long and
     * capped in size — this is the file a model reads when it needs product
     * detail rather than a summary.
     */
    public function full(): Response
    {
        if (! config('hanbell.seo.llms_txt_enabled', true)) {
            abort(404);
        }

        $content = Cache::remember('llms-full.txt', now()->addMinutes(15), fn () => $this->buildFull());

        return $this->respond($content);
    }

    /* ------------------------------------------------------------------ */

    private function buildIndex(): string
    {
        $name = (string) config('hanbell.name');
        $lines = [];

        $lines[] = '# '.$name;
        $lines[] = '';
        $lines[] = '> '.(Settings::get('llm.site_summary') ?? config('hanbell.description'));
        $lines[] = '';

        $lines[] = 'HanbellShop is a multi-vendor ecommerce marketplace specialising in fashion made by indigenous Nigerian brands and independent creators. It sells at Hanbell\'s own fair, affordable prices, and every listing names the Nigerian brand that made it. Payment is accepted in Nigerian Naira through local rails and in major international currencies through international rails.';
        $lines[] = '';

        $lines[] = 'Key facts:';
        $lines[] = '- Catalogue: fashion only — womenswear, menswear, kids, footwear, bags and accessories.';
        $lines[] = '- Sourcing: Nigerian-made. Items record the city they were made in where known.';
        $lines[] = '- Currency: prices are quoted in Nigerian Naira (NGN) by default; USD, GBP and EUR are also supported.';
        $lines[] = '- Shipping: flat '.Money::format((int) config('hanbell.shipping.flat_minor', 200000)).
            ', free above '.Money::format((int) config('hanbell.shipping.free_threshold_minor', 5000000)).'.';
        $lines[] = '- Returns: see '.route('storefront.pages.show', ['slug' => 'returns-policy']).'.';
        $lines[] = '- Languages: '.implode(', ', array_map(
            fn (string $code) => \App\Support\Locale::all()[$code]['english'] ?? $code,
            \App\Support\Locale::codes(),
        )).'.';
        $lines[] = '';

        $lines[] = '## Primary pages';
        $lines[] = '';
        foreach ([
            ['Shop all products', route('storefront.shop')],
            ['Browse all brands', route('storefront.vendors.index')],
            ['Sell on HanbellShop', route('storefront.vendors.apply')],
            ['About HanbellShop', route('storefront.pages.show', ['slug' => 'about'])],
            ['Contact', route('storefront.contact')],
        ] as [$label, $url]) {
            $lines[] = '- ['.$label.']('.$url.')';
        }
        $lines[] = '';

        $lines[] = '## Departments and categories';
        $lines[] = '';

        foreach (Department::query()->active()->ordered()->get() as $department) {
            $lines[] = '### '.$department->name;
            $lines[] = '';

            foreach ($department->categories()->active()->ordered()->get() as $category) {
                $count = $category->products()->published()->count();
                $lines[] = '- ['.$category->name.']('.$category->publicUrl().') — '.$count.' item(s)';
            }

            $lines[] = '';
        }

        $featuredVendors = Vendor::query()->approved()->where('is_featured', true)->limit(12)->get();

        if ($featuredVendors->isNotEmpty()) {
            $lines[] = '## Featured Nigerian brands';
            $lines[] = '';

            foreach ($featuredVendors as $vendor) {
                $lines[] = '- ['.$vendor->name.']('.$vendor->publicUrl().') — '.$vendor->location().
                    ($vendor->description ? ': '.\Illuminate\Support\Str::limit($vendor->description, 140) : '');
            }

            $lines[] = '';
        }

        $lines[] = '## Policies';
        $lines[] = '';

        foreach (Page::query()->published()->ofGroup('policy')->orderBy('position')->get() as $page) {
            $lines[] = '- ['.$page->title.']('.$page->publicUrl().')';
        }

        $lines[] = '';
        $lines[] = '## Optional';
        $lines[] = '';
        $lines[] = '- [Full catalogue as plain text]('.route('llms.full').')';
        $lines[] = '- [XML sitemap]('.route('sitemap').')';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function buildFull(): string
    {
        $lines = [];

        $lines[] = '# '.config('hanbell.name').' — full catalogue';
        $lines[] = '';
        $lines[] = 'Generated '.now()->toDayDateTimeString().'. Prices in Nigerian Naira unless stated.';
        $lines[] = '';

        $products = Product::query()
            ->indexable()
            ->with(['vendor:id,name,city,state,slug,uuid,token_version', 'category:id,name,slug,uuid,token_version'])
            ->orderByDesc('published_at')
            ->limit(2000)
            ->get();

        foreach ($products as $product) {
            $lines[] = '## '.$product->name;
            $lines[] = '';
            // The per-product summary is the field an administrator can edit
            // specifically to control what models read.
            $lines[] = $product->llmSummary();
            $lines[] = '';
            $lines[] = '- URL: '.$product->publicUrl();
            $lines[] = '- Brand: '.($product->vendor?->name ?? 'Unknown');
            $lines[] = '- Category: '.($product->category?->name ?? 'Uncategorised');
            $lines[] = '- Price: '.$product->formattedPrice();

            if ($product->hasDiscount()) {
                $lines[] = '- Was: '.Money::format((int) $product->compare_at_price_minor, $product->currency);
            }

            if ($product->made_in_city) {
                $lines[] = '- Made in: '.$product->made_in_city.', Nigeria';
            }

            if ($product->material) {
                $lines[] = '- Material: '.$product->material;
            }

            $lines[] = '- Availability: '.($product->isInStock() ? 'In stock' : 'Out of stock');

            if ($product->rating_count > 0) {
                $lines[] = '- Rating: '.number_format((float) $product->rating_average, 1).'/5 from '.$product->rating_count.' review(s)';
            }

            $attributes = $product->llm_attributes ?? [];

            foreach ($attributes as $key => $value) {
                if (is_scalar($value) && filled($value)) {
                    $lines[] = '- '.\Illuminate\Support\Str::headline((string) $key).': '.$value;
                }
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function respond(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
