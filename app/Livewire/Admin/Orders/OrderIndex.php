<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class OrderIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $payment = '';

    public int $perPage = 20;

    public function updated(string $property): void
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        return view('livewire.admin.orders.order-index', [
            'orders' => $this->query()->paginate($this->perPage),
            'counts' => $this->counts(),
            'revenueMinor' => (int) $this->query()->whereNotNull('paid_at')->sum('total_minor'),
            'seo' => app(Seo::class)->title(__('hanbell.admin.orders'))->noindex(),
        ]);
    }

    private function query(): Builder
    {
        return Order::query()
            ->withCount('items')
            ->when(filled($this->search), function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(fn (Builder $inner) => $inner
                    ->where('number', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('customer_name', 'like', $term));
            })
            ->when(filled($this->status), fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->payment === 'paid', fn (Builder $q) => $q->whereNotNull('paid_at'))
            ->when($this->payment === 'unpaid', fn (Builder $q) => $q->whereNull('paid_at'))
            ->latest();
    }

    /** @return array<string,int> */
    private function counts(): array
    {
        $counts = Order::query()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->all();

        return ['all' => Order::count()] + array_map('intval', $counts);
    }
}