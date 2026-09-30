<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Setting;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Store settings.
 *
 * Values are grouped and typed; on save they are written through
 * App\Support\Settings, which also busts the read cache so the change takes
 * effect on the next request rather than in an hour.
 */
#[Layout('layouts.admin')]
class SettingsIndex extends Component
{
    use InteractsWithToasts;

    /** key => value */
    public array $values = [];

    public string $activeGroup = 'store';

    public function mount(): void
    {
        foreach (Setting::all() as $setting) {
            $this->values[$setting->key] = $setting->typedValue();
        }
    }

    public function saveGroup(AuditLogger $audit): void
    {
        $writable = Setting::ofGroup($this->activeGroup)->pluck('key')->all();

        $payload = collect($this->values)->only($writable)->all();

        Settings::putMany($payload);

        $audit->log('settings.updated', null, 'Settings updated ('.$this->activeGroup.')', ['group' => $this->activeGroup]);

        $this->toastSuccess(__('hanbell.admin.saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.settings-index', [
            'groups' => Setting::query()->select('group')->distinct()->orderBy('group')->pluck('group')->all(),
            'settings' => Setting::ofGroup($this->activeGroup)->get(),
            'seo' => app(Seo::class)->title(__('hanbell.admin.settings'))->noindex(),
        ]);
    }
}