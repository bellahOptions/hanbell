<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Payment;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class PaymentIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $provider = '';

    #[Url(except: '')]
    public string $status = '';

    public function updated(string $property): void
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $query = Payment::query()
            ->with('order:id,number,email,currency')
            ->when(filled($this->search), function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(fn (Builder $inner) => $inner
                    ->where('reference', 'like', $term)
                    ->orWhere('provider_reference', 'like', $term));
            })
            ->when(filled($this->provider), fn (Builder $q) => $q->where('provider', $this->provider))
            ->when(filled($this->status), fn (Builder $q) => $q->where('status', $this->status))
            ->latest();

        return view('livewire.admin.orders.payment-index', [
            'payments' => $query->paginate(20),
            'collectedMinor' => (int) Payment::successful()->sum('amount_minor'),
            'refundedMinor' => (int) Payment::sum('refunded_minor'),
            'failedCount' => Payment::where('status', 'failed')->count(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.payments'))->noindex(),
        ]);
    }
}