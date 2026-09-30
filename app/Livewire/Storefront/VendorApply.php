<?php

namespace App\Livewire\Storefront;

use App\Enums\VendorStatus;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Vendor;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Sell with us" — the vendor onboarding application.
 */
#[Layout('layouts.app')]
class VendorApply extends Component
{
    use InteractsWithToasts;
    use WithFileUploads;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('nullable|string|max:160')]
    public string $legal_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:32')]
    public string $phone = '';

    #[Validate('nullable|url|max:200')]
    public string $website = '';

    #[Validate('required|string|min:30|max:1000')]
    public string $description = '';

    #[Validate('nullable|string|max:4000')]
    public string $story = '';

    #[Validate('required|string|max:80')]
    public string $city = '';

    #[Validate('required|string|max:80')]
    public string $state = '';

    #[Validate('nullable|image|max:4096')]
    public $logo = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user) {
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
        }
    }

    public function submit(): void
    {
        $this->validate();

        $user = auth()->user();

        if ($user && $user->vendor()->exists()) {
            $this->toastInfo(__('hanbell.vendor.already_applied'));

            return;
        }

        // Store the logo directly rather than spreading the validated array into
        // create(): a #[Validate] upload property is keyed by its property name
        // (`logo`), which is not a column (the column is `logo_path`).
        $logoPath = $this->logo ? $this->logo->store('vendors/logos', 'public') : null;

        $vendor = Vendor::create([
            'owner_id' => $user?->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'description' => $this->description,
            'story' => $this->story,
            'city' => $this->city,
            'state' => $this->state,
            'country' => 'NG',
            'logo_path' => $logoPath,
            'status' => VendorStatus::Pending,
            'applied_at' => now(),
        ]);

        app(AuditLogger::class)->log('vendor.applied', $vendor, 'Vendor application submitted', ['vendor' => $vendor->name], $user);

        $this->toastSuccess(__('hanbell.vendor.application_received'));
        $this->reset(['name', 'legal_name', 'website', 'description', 'story', 'city', 'state', 'logo']);
    }

    public function render(): View
    {
        return view('livewire.storefront.vendor-apply', [
            'existing' => auth()->user()?->vendor,
            'seo' => app(Seo::class)
                ->title(__('hanbell.vendor.apply_title'))
                ->description(__('hanbell.vendor.apply_subtitle')),
        ]);
    }
}