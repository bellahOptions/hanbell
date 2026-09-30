<?php

namespace App\Livewire\Account;

use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Orders extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $filter = 'all';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = auth()->user()
            ->orders()
            ->with(['items' => fn ($q) => $q->limit(4)])
            ->latest();

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return view('livewire.account.orders', [
            'orders' => $query->paginate(10),
            'seo' => app(Seo::class)->title(__('hanbell.order.orders'))->noindex(),
        ]);
    }
}