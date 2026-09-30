<?php

namespace App\Livewire\Admin\Content;

use App\Enums\PageStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Page;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PageForm extends Component
{
    use InteractsWithToasts;

    public ?int $pageId = null;

    public string $title = '';

    public string $slug = '';

    public string $content = '';

    public string $excerpt = '';

    public string $group = 'general';

    public string $status = 'draft';

    public int $position = 0;

    public bool $show_in_footer = true;

    public bool $show_in_header = false;

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $is_indexable = true;

    public string $llm_summary = '';

    public function mount(?Page $page = null): void
    {
        if (! $page || ! $page->exists) {
            return;
        }

        $this->pageId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->content = (string) $page->content;
        $this->excerpt = (string) $page->excerpt;
        $this->group = (string) $page->group;
        $this->status = $page->status->value;
        $this->position = (int) $page->position;
        $this->show_in_footer = $page->show_in_footer;
        $this->show_in_header = $page->show_in_header;
        $this->meta_title = (string) $page->meta_title;
        $this->meta_description = (string) $page->meta_description;
        $this->is_indexable = $page->is_indexable;
        $this->llm_summary = (string) $page->llm_summary;
    }

    public function save(AuditLogger $audit)
    {
        $this->validate([
            'title' => 'required|string|max:180',
            'slug' => 'nullable|string|max:180|alpha_dash',
            'content' => 'nullable|string',
            'group' => 'required|in:general,policy,help,legal',
            'status' => 'required|in:draft,published',
        ]);

        $attributes = [
            'title' => $this->title,
            'slug' => $this->slug ?: null,
            'content' => $this->content,
            'excerpt' => $this->excerpt ?: null,
            'group' => $this->group,
            'status' => $this->status,
            'position' => $this->position,
            'show_in_footer' => $this->show_in_footer,
            'show_in_header' => $this->show_in_header,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'is_indexable' => $this->is_indexable,
            'llm_summary' => $this->llm_summary ?: null,
        ];

        if (! $this->slug) {
            unset($attributes['slug']); // let HasSlug derive it from the title
        }

        if ($this->status === 'published') {
            $attributes['published_at'] = now();
        }

        if ($this->pageId) {
            $page = Page::findOrFail($this->pageId);
            $page->update($attributes);
            $audit->log('page.updated', $page, 'Page updated', ['page' => $page->title]);
            $this->toastSuccess(__('hanbell.admin.saved'));
        } else {
            $page = Page::create($attributes);
            $audit->log('page.created', $page, 'Page created', ['page' => $page->title]);
            $this->toastSuccess(__('hanbell.admin.created'));
        }

        return $this->redirect(route('admin.pages.index'));
    }

    public function render(): View
    {
        return view('livewire.admin.content.page-form', [
            'page' => $this->pageId ? Page::find($this->pageId) : null,
            'statuses' => collect(PageStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'seo' => app(Seo::class)->title($this->pageId ? 'Edit page' : 'New page')->noindex(),
        ]);
    }
}