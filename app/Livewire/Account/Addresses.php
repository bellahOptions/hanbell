<?php

namespace App\Livewire\Account;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Address;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Addresses extends Component
{
    use InteractsWithToasts;

    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:120')]
    public string $recipient_name = '';

    #[Validate('required|string|max:32')]
    public string $phone = '';

    #[Validate('required|string|max:180')]
    public string $line1 = '';

    #[Validate('nullable|string|max:180')]
    public string $line2 = '';

    #[Validate('required|string|max:80')]
    public string $city = '';

    #[Validate('required|string|max:80')]
    public string $state = '';

    #[Validate('nullable|string|max:16')]
    public string $postal_code = '';

    #[Validate('nullable|string|max:40')]
    public string $label = '';

    public string $country = 'NG';

    public function create(): void
    {
        $this->reset(['editingId', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'label']);
        $this->country = 'NG';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $address = $this->owned($id);

        if (! $address) {
            return;
        }

        $this->editingId = $address->id;
        $this->fill($address->only(['recipient_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'label', 'country']));
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->only(['recipient_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'label', 'country']);

        if ($this->editingId) {
            $address = $this->owned($this->editingId);

            if (! $address) {
                return;
            }

            $address->update($data);
            $this->toastSuccess(__('hanbell.account.address_updated'));
        } else {
            $data['is_default'] = auth()->user()->addresses()->count() === 0;
            auth()->user()->addresses()->create($data);

            $this->toastSuccess(__('hanbell.account.address_added'));
        }

        $this->showForm = false;
        $this->reset(['editingId', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'label']);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code', 'label']);
    }

    public function makeDefault(int $id): void
    {
        $this->owned($id)?->makeDefault();
        $this->toastSuccess(__('hanbell.account.set_default'));
    }

    public function delete(int $id): void
    {
        $this->owned($id)?->delete();
        $this->toastSuccess(__('hanbell.account.address_removed'));
    }

    /** Every mutation is scoped to the signed-in user. */
    private function owned(int $id): ?Address
    {
        return Address::where('user_id', auth()->id())->find($id);
    }

    public function render(): View
    {
        return view('livewire.account.addresses', [
            'addresses' => auth()->user()->addresses()->orderByDesc('is_default')->latest()->get(),
            'seo' => app(Seo::class)->title(__('hanbell.account.addresses'))->noindex(),
        ]);
    }
}