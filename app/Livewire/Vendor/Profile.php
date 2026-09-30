<?php

namespace App\Livewire\Vendor;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The brand's public profile. Edits go live immediately — this is the vendor's
 * own storefront copy, not the catalogue, so it does not need moderation.
 */
#[Layout('layouts.vendor')]
class Profile extends Component
{
    use InteractsWithToasts;
    use WithFileUploads;

    public string $name = '';

    public string $description = '';

    public string $story = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $website = '';

    public string $city = '';

    public string $state = '';

    /** @var mixed */
    public $logo = null;

    public function mount(): void
    {
        $vendor = auth()->user()->vendor;

        if (! $vendor) {
            return;
        }

        $this->name = $vendor->name;
        $this->description = (string) $vendor->description;
        $this->story = (string) $vendor->story;
        $this->phone = (string) $vendor->phone;
        $this->whatsapp = (string) $vendor->whatsapp;
        $this->website = (string) $vendor->website;
        $this->city = (string) $vendor->city;
        $this->state = (string) $vendor->state;
    }

    public function save(AuditLogger $audit): void
    {
        $vendor = auth()->user()->vendor;

        if (! $vendor) {
            abort(403);
        }

        $this->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'story' => 'nullable|string|max:4000',
            'website' => 'nullable|url|max:200',
            'logo' => 'nullable|image|max:4096',
        ]);

        $attributes = [
            'name' => $this->name,
            'description' => $this->description,
            'story' => $this->story,
            'phone' => $this->phone ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'website' => $this->website ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
        ];

        if ($this->logo) {
            $attributes['logo_path'] = $this->logo->store('vendors/logos', 'public');
            $this->logo = null;
        }

        $vendor->update($attributes);

        $audit->log('vendor.profile_updated', $vendor, 'Vendor updated their profile', ['vendor' => $vendor->name], auth()->user());

        $this->toastSuccess(__('hanbell.account.details_updated'));
    }

    public function render(): View
    {
        return view('livewire.vendor.profile', [
            'vendor' => auth()->user()->vendor,
            'seo' => app(Seo::class)->title(__('hanbell.vendor.brand')  )->noindex(),
        ]);
    }
}