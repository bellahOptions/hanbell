<?php

namespace App\Support;

use App\Models\SeoMeta;
use Illuminate\Support\Str;

/**
 * Builds the <head> payload for a page.
 *
 * Resolution order for every field is: explicit model value → administrator
 * override for the route (seo_meta) → store setting → config default. Model
 * values win because a product's own title is always more specific than a
 * route-level default.
 *
 * A single object (rather than a bag of view variables) means a partial can
 * render Open Graph, Twitter, canonical, hreflang and JSON-LD from one source
 * without the caller needing to remember which pieces it set.
 */
class Seo
{
    /** @var array<int,array<string,mixed>> */
    private array $schema = [];

    private ?string $title = null;

    private ?string $description = null;

    private ?string $image = null;

    private ?string $canonical = null;

    private ?string $robots = null;

    private array $keywords = [];

    private ?string $type = 'website';

    /** @var array<int,array<string,string>> */
    private array $alternates = [];

    /** @var array<int,array{question:string,answer:string}> */
    private array $faqs = [];

    private ?string $llmSummary = null;

    private ?SeoMeta $override = null;

    public function __construct(private readonly ?string $routeName = null)
    {
        $this->override = SeoMeta::forRoute($routeName);
    }

    /* ------------------------------------------------------------------ *
     * Fluent construction
     * ------------------------------------------------------------------ */

    public function title(?string $title, bool $withSuffix = true): static
    {
        if (filled($title)) {
            $suffix = (string) config('hanbell.seo.title_suffix', config('hanbell.name'));

            $this->title = $withSuffix && ! Str::contains($title, $suffix)
                ? $title.' | '.$suffix
                : $title;
        }

        return $this;
    }

    /** A raw title that must not be suffixed (homepage, landing pages). */
    public function rawTitle(?string $title): static
    {
        return $this->title($title, withSuffix: false);
    }

    public function description(?string $description): static
    {
        if (filled($description)) {
            $this->description = Str::limit(trim(strip_tags($description)), 158);
        }

        return $this;
    }

    public function image(?string $url): static
    {
        if (filled($url)) {
            $this->image = $url;
        }

        return $this;
    }

    public function canonical(?string $url): static
    {
        if (filled($url)) {
            $this->canonical = $url;
        }

        return $this;
    }

    public function robots(string $robots): static
    {
        $this->robots = $robots;

        return $this;
    }

    public function noindex(): static
    {
        return $this->robots('noindex,nofollow');
    }

    /** @param array<int,string> $keywords */
    public function keywords(array $keywords): static
    {
        $this->keywords = array_values(array_filter(array_map('trim', $keywords)));

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function schema(array $node): static
    {
        $this->schema[] = $node;

        return $this;
    }

    /** @param array<int,array{question:string,answer:string}> $faqs */
    public function faqs(array $faqs): static
    {
        $this->faqs = $faqs;

        return $this;
    }

    public function llmSummary(?string $summary): static
    {
        if (filled($summary)) {
            $this->llmSummary = $summary;
        }

        return $this;
    }

    /* ------------------------------------------------------------------ *
     * Resolution — what actually gets rendered
     * ------------------------------------------------------------------ */

    public function resolvedTitle(): string
    {
        $title = $this->title
            ?? $this->override?->title
            ?? Settings::get('seo.default_title')
            ?? config('hanbell.name');

        $suffix = (string) config('hanbell.seo.title_suffix', config('hanbell.name'));

        // Guard against a suffix being appended twice when a value comes from
        // the seo_meta table already formatted.
        return Str::contains($title, $suffix) ? $title : $title.' | '.$suffix;
    }

    public function resolvedDescription(): string
    {
        return $this->description
            ?? $this->override?->description
            ?? Settings::get('seo.default_description')
            ?? (string) config('hanbell.seo.default_description');
    }

    public function resolvedImage(): ?string
    {
        $image = $this->image
            ?? $this->override?->og_image_path
            ?? Settings::get('seo.default_og_image');

        if (blank($image)) {
            return null;
        }

        // Social platforms require an absolute URL.
        return Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
    }

    public function resolvedCanonical(): string
    {
        return $this->canonical
            ?? $this->override?->canonical_url
            ?? url()->current();
    }

    public function resolvedRobots(): string
    {
        return $this->robots
            ?? $this->override?->robots
            ?? 'index,follow';
    }

    public function isIndexable(): bool
    {
        return ! Str::contains($this->resolvedRobots(), 'noindex');
    }

    /** @return array<int,string> */
    public function resolvedKeywords(): array
    {
        if ($this->keywords !== []) {
            return $this->keywords;
        }

        $stored = $this->override?->keywords;

        return $stored ? array_map('trim', explode(',', $stored)) : [];
    }

    public function resolvedType(): string
    {
        return $this->type ?? 'website';
    }

    public function openGraphTitle(): string
    {
        return $this->override?->og_title
            ?? $this->title
            ?? $this->resolvedTitle();
    }

    public function openGraphDescription(): string
    {
        return $this->override?->og_description ?? $this->resolvedDescription();
    }

    public function twitterCard(): string
    {
        return $this->override?->twitter_card ?? 'summary_large_image';
    }

    /**
     * hreflang alternates for the current page in every supported locale.
     *
     * Every language is served from the same URL (the locale is resolved from
     * session/cookie/header), so the alternates all point at the current URL
     * with a locale query parameter — which is what the switcher itself uses.
     *
     * @return array<int,array<string,string>>
     */
    public function alternates(): array
    {
        if ($this->alternates !== []) {
            return $this->alternates;
        }

        $current = $this->resolvedCanonical();
        $urls = [];

        foreach (Locale::codes() as $code) {
            $urls[] = [
                'hreflang' => $code,
                'href' => $current.'?'.http_build_query(['hl' => $code]),
            ];
        }

        // x-default tells a crawler which version to serve an unmatched user.
        $urls[] = [
            'hreflang' => 'x-default',
            'href' => $current,
        ];

        return $this->alternates = $urls;
    }

    /* ------------------------------------------------------------------ *
     * JSON-LD
     * ------------------------------------------------------------------ */

    /** @return array<int,array<string,mixed>> */
    public function schemaNodes(): array
    {
        $nodes = $this->schema;

        if ($this->override?->schema_json) {
            $nodes[] = $this->override->schema_json;
        }

        // Always describe the organisation and the site itself.
        return array_merge($this->baselineSchema(), $nodes, $this->faqSchema());
    }

    /** @return array<int,array<string,mixed>> */
    private function baselineSchema(): array
    {
        $organization = array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('hanbell.name'),
            'url' => url('/'),
            'logo' => asset('images/logo.svg'),
            'description' => config('hanbell.description'),
            'email' => config('hanbell.support_email'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'NG',
                'addressLocality' => config('hanbell.address'),
            ],
            'sameAs' => array_values(array_filter([
                Settings::get('social.instagram'),
                Settings::get('social.twitter'),
                Settings::get('social.facebook'),
            ])),
        ]);

        $website = [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('hanbell.name'),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('storefront.search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return [$organization, $website];
    }

    /** @return array<int,array<string,mixed>> */
    private function faqSchema(): array
    {
        $faqs = $this->faqs ?: ($this->override?->faqs() ?? []);

        if ($faqs === []) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ], $faqs),
        ]];
    }

    /**
     * The whole graph as a single JSON-LD document.
     */
    public function toJsonLd(): string
    {
        $nodes = $this->schemaNodes();

        if ($nodes === []) {
            return '';
        }

        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        ];

        // JSON_UNESCAPED_SLASHES keeps URLs readable; UNESCAPED_UNICODE keeps
        // Yoruba and Arabic content legible in the markup.
        return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * The factual blurb surfaced to language models (llms.txt and friends).
     */
    public function resolvedLlmSummary(): ?string
    {
        return $this->llmSummary
            ?? $this->override?->llm_summary
            ?? Settings::get('llm.site_summary')
            ?? config('hanbell.description');
    }
}
