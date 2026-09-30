<?php

namespace App\Livewire\Admin\Vendors;

use App\Enums\VendorStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Vendor;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class VendorIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public ?int $rejectingId = null;

    public string $reason = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function approve(int $id, AuditLogger $audit): void
    {
        $vendor = Vendor::find($id);

        if (! $vendor) {
            return;
        }

        $vendor->approve();

        $audit->moderation('vendor.approved', $vendor, 'Vendor approved', ['vendor' => $vendor->name]);
        $this->toastSuccess($vendor->name.' is now approved to sell.');
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->reason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->reason = '';
    }

    public function reject(AuditLogger $audit): void
    {
        $this->validate(['reason' => 'required|string|max:500']);

        $vendor = Vendor::find($this->rejectingId);

        if (! $vendor) {
            return;
        }

        $vendor->reject($this->reason);

        $audit->moderation('vendor.rejected', $vendor, 'Vendor application rejected', [
            'vendor' => $vendor->name,
            'reason' => $this->reason,
        ]);

        $this->rejectingId = null;
        $this->reason = '';
        $this->toastSuccess($vendor->name.' was not approved.');
    }

    public function suspend(int $id, AuditLogger $audit): void
    {
        $vendor = Vendor::find($id);

        if (! $vendor) {
            return;
        }

        $vendor->suspend('Suspended by an administrator.');

        $audit->moderation('vendor.suspended', $vendor, 'Vendor suspended', ['vendor' => $vendor->name]);
        $this->toastInfo($vendor->name.' was suspended.');
    }

    public function render(): View
    {
        return view('livewire.admin.vendors.vendor-index', [
            'vendors' => Vendor::query()
                ->withCount(['products', 'products as published_count' => fn (Builder $q) => $q->where('status', 'published')])
                ->when(filled($this->search), fn (Builder $q) => $q->search($this->search))
                ->when(filled($this->status), fn (Builder $q) => $q->where('status', $this->status))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest()
                ->paginate(20),
            'counts' => $this->counts(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.vendors'))->noindex(),
        ]);
    }

    /** @return array<string,int> */
    private function counts(): array
    {
        $counts = Vendor::query()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->all();

        return ['all' => Vendor::count()] + array_map('intval', $counts);
    }
}