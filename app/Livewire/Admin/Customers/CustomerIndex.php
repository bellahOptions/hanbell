<?php

namespace App\Livewire\Admin\Customers;

use App\Models\User;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CustomerIndex extends Component
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
        return view('livewire.admin.customers.customer-index', [
            'customers' => User::query()
                ->withCount('orders')
                ->withSum(['orders as spent_minor' => fn (Builder $q) => $q->whereNotNull('paid_at')], 'total_minor')
                ->when(filled($this->search), function (Builder $q): void {
                    $term = '%'.$this->search.'%';
                    $q->where(fn (Builder $inner) => $inner
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term));
                })
                ->latest()
                ->paginate(20),
            'totalCustomers' => User::count(),
            'verifiedCount' => User::whereNotNull('email_verified_at')->count(),
            'twoFactorCount' => User::where('two_factor_method', '!=', 'none')->count(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.customers'))->noindex(),
        ]);
    }
}