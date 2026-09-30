<?php

namespace App\Livewire\Admin\Seo;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\SeoMeta;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Search and AI settings.
 *
 * Two audiences are configured here: crawlers (title/description/robots) and
 * language models (a factual summary plus optional FAQs, which are emitted as
 * FAQPage structured data and published in llms.txt). The administrator controls
 * all of it without a deploy.
 */
#[Layout('layouts.admin')]
class SeoIndex extends Component
{
    use InteractsWithToasts;

    /** route_name => [title, description] */
    public array $routes = [];

    public string $siteSummary = '';

    public string $defaultTitle = '';

    public string $defaultDescription = '';

    public string $twitterHandle = '';

    public string $robotsExtra = '';

    public bool $llmsEnabled = true;

    /** @var array<int,array{question:string,answer:string}> */
    public array $faqs = [];

    /** The routes an administrator can override. */
    public const MANAGED_ROUTES = [
        'storefront.home' => 'Homepage',
        'storefront.shop' => 'Shop',
        'storefront.vendors.index' => 'Brand directory',
        'storefront.vendors.apply' => 'Sell with us',
        'storefront.contact' => 'Contact',
    ];

    public function mount(): void
    {
        $this->siteSummary = (string) Settings::get('llm.site_summary', config('hanbell.description'));
        $this->defaultTitle = (string) Settings::get('seo.default_title', config('hanbell.name'));
        $this->defaultDescription = (string) Settings::get('seo.default_description', config('hanbell.seo.default_description'));
        $this->twitterHandle = (string) Settings::get('seo.twitter_handle', config('hanbell.seo.twitter_handle'));
        $this->robotsExtra = (string) Settings::get('seo.robots_extra', '');
        $this->llmsEnabled = (bool) Settings::get('seo.llms_enabled', true);

        foreach (array_keys(self::MANAGED_ROUTES) as $route) {
            $meta = SeoMeta::forRoute($route);

            $this->routes[$route] = [
                'title' => (string) ($meta?->title ?? ''),
                'description' => (string) ($meta?->description ?? ''),
                'llm_summary' => (string) ($meta?->llm_summary ?? ''),
            ];
        }

        $home = SeoMeta::forRoute('storefront.home');
        $this->faqs = $home?->faqs() ?: [];
    }

    public function addFaq(): void
    {
        $this->faqs[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(int $index): void
    {
        unset($this->faqs[$index]);
        $this->faqs = array_values($this->faqs);
    }

    public function save(\App\Services\Security\AuditLogger $audit): void
    {
        $this->validate([
            'defaultTitle' => 'required|string|max:180',
            'defaultDescription' => 'required|string|max:320',
            'siteSummary' => 'required|string|max:1200',
            'routes.*.title' => 'nullable|string|max:180',
            'routes.*.description' => 'nullable|string|max:320',
            'faqs.*.question' => 'nullable|string|max:300',
            'faqs.*.answer' => 'nullable|string|max:1500',
        ]);

        Settings::putMany([
            'seo.default_title' => $this->defaultTitle,
            'seo.default_description' => $this->defaultDescription,
            'seo.twitter_handle' => $this->twitterHandle,
            'seo.robots_extra' => $this->robotsExtra,
            'seo.llms_enabled' => $this->llmsEnabled,
            'llm.site_summary' => $this->siteSummary,
        ]);

        foreach ($this->routes as $route => $values) {
            SeoMeta::updateOrCreate(
                ['route_name' => $route],
                [
                    'path' => $route === 'storefront.home' ? '/' : null,
                    'title' => $values['title'] ?: null,
                    'description' => $values['description'] ?: null,
                    'llm_summary' => $values['llm_summary'] ?: null,
                    // Only the homepage carries the site-wide FAQ block.
                    'llm_faq' => $route === 'storefront.home' ? array_values(array_filter(
                        $this->faqs,
                        fn (array $faq) => filled($faq['question'] ?? null),
                    )) : null,
                ],
            );
        }

        $audit->log('seo.updated', null, 'SEO and LLM settings updated');

        $this->toastSuccess(__('hanbell.admin.saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.seo.seo-index', [
            'managedRoutes' => self::MANAGED_ROUTES,
            'sitemapUrl' => route('sitemap'),
            'robotsUrl' => route('robots'),
            'llmsUrl' => route('llms'),
            'seo' => app(Seo::class)->title(__('hanbell.admin.seo_settings'))->noindex(),
        ]);
    }
}