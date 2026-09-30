<?php

namespace App\Livewire\Admin\Content;

use App\Models\NewsletterSubscriber;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class NewsletterIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.content.newsletter-index', [
            'subscribers' => NewsletterSubscriber::query()
                ->when(filled($this->search), fn ($q) => $q->where('email', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(25),
            'activeCount' => NewsletterSubscriber::active()->count(),
            'totalCount' => NewsletterSubscriber::count(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.newsletter'))->noindex(),
        ]);
    }
}