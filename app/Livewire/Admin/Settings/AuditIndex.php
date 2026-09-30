<?php

namespace App\Livewire\Admin\Settings;

use App\Models\AuditLog;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The audit trail.
 *
 * Read-only by design: an audit log that can be edited is not an audit log. The
 * `properties` payload is already redacted at write time by AuditLogger, so no
 * credential can appear here.
 */
#[Layout('layouts.admin')]
class AuditIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $event = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'event'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        return view('livewire.admin.settings.audit-index', [
            'logs' => AuditLog::query()
                ->with('user:id,name,email')
                ->when(filled($this->search), function (Builder $q): void {
                    $term = '%'.$this->search.'%';
                    $q->where(fn (Builder $inner) => $inner
                        ->where('description', 'like', $term)
                        ->orWhere('event', 'like', $term)
                        ->orWhere('ip_address', 'like', $term));
                })
                ->when(filled($this->event), fn (Builder $q) => $q->where('event', $this->event))
                ->latest('created_at')
                ->paginate(30),
            'events' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event')->all(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.audit_log'))->noindex(),
        ]);
    }
}