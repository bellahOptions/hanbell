<?php

namespace App\Livewire\Admin\Content;

use App\Enums\PageStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Page;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CMS pages: policy pages, about, help. Every footer link resolves because the
 * footer is generated from this same table.
 */
#[Layout('layouts.admin')]
class PageIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function togglePublish(int $id, AuditLogger $audit): void
    {
        $page = Page::find($id);

        if (! $page) {
            return;
        }

        $page->forceFill([
            'status' => $page->isPublished() ? PageStatus::Draft : PageStatus::Published,
            'published_at' => $page->isPublished() ? $page->published_at : now(),
        ])->save();

        $page->refresh();

        $audit->log('page.' . ($page->isPublished() ? 'published' : 'unpublished'), $page, 'Page '.$page->status->label(), [
            'page' => $page->title,
        ]);

        $this->toastSuccess($page->title.' is now '.strtolower($page->status->label()).'.');
    }

    public function delete(int $id, AuditLogger $audit): void
    {
        $page = Page::find($id);

        if (! $page) {
            return;
        }

        $audit->log('page.deleted', $page, 'Page deleted', ['page' => $page->title]);
        $page->delete();

        $this->toastSuccess(__('hanbell.admin.deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.content.page-index', [
            'pages' => Page::query()
                ->when(filled($this->search), fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->orderBy('group')
                ->orderBy('position')
                ->paginate(20),
            'seo' => app(Seo::class)->title(__('hanbell.admin.pages'))->noindex(),
        ]);
    }
}