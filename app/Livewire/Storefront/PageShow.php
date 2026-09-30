<?php

namespace App\Livewire\Storefront;

use App\Models\Page;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CMS page (policies, about, help).
 *
 * A slug with no published page is NOT a 404. The footer links to policies an
 * administrator may not have written yet, and a friendly "still being written"
 * placeholder is a better experience than a dead end — so the page renders,
 * just with a noindex flag.
 */
#[Layout('layouts.app')]
class PageShow extends Component
{
    public ?int $pageId = null;

    public function mount(string $slug): void
    {
        $this->pageId = Page::query()
            ->published()
            ->where('slug', $slug)
            ->value('id');
    }

    public function render(): View
    {
        $page = $this->pageId ? Page::find($this->pageId) : null;

        $seo = $page
            ? app(Seo::class)
                ->title($page->metaTitle())
                ->description($page->metaDescription())
                ->llmSummary($page->llmSummary())
            : app(Seo::class)->title(__('hanbell.page.not_found'))->noindex();

        if ($page && ! $page->is_indexable) {
            $seo->noindex();
        }

        return view('livewire.storefront.page-show', [
            'page' => $page,
            'policyPages' => Page::query()->inFooter()->ofGroup('policy')->orderBy('position')->get(),
            'seo' => $seo,
        ]);
    }
}