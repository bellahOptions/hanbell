<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.settings') }}</h1>
        <p class="mt-1 text-sm text-ink-500">
            Stored in the database and overlaid on top of <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[11px]">config/hanbell.php</code>.
        </p>
    </div>

    @if (empty($groups))
        <x-ui.alert variant="neutral" :title="__('hanbell.common.no_items')">
            Run <code class="rounded bg-white px-1 py-0.5 font-mono text-[11px]">php artisan db:seed --class=DemoCatalogueSeeder</code>
            to populate the default settings.
        </x-ui.alert>
    @else
        <div class="flex flex-wrap gap-1.5">
            @foreach ($groups as $group)
                <button
                    type="button"
                    wire:click="$set('activeGroup', '{{ $group }}')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-semibold capitalize transition',
                        'bg-ink-950 text-white' => $activeGroup === $group,
                        'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $activeGroup !== $group,
                    ])
                >
                    {{ str_replace('_', ' ', $group) }}
                </button>
            @endforeach
        </div>

        <form wire:submit="saveGroup">
            <x-admin.panel :title="ucfirst(str_replace('_', ' ', $activeGroup))">
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($settings as $setting)
                        @php $value = $values[$setting->key] ?? null; @endphp

                        <div class="{{ $setting->type === 'text' ? 'sm:col-span-2' : '' }}">
                            @if ($setting->type === 'boolean')
                                <label class="flex cursor-pointer items-start gap-2.5 pt-2">
                                    <input
                                        type="checkbox"
                                        wire:model="values.{{ $setting->key }}"
                                        class="mt-0.5 size-4 rounded border-ink-300 text-brand-600"
                                    >
                                    <span>
                                        <span class="block text-sm font-medium text-ink-800">{{ $setting->label }}</span>
                                        @if ($setting->description)
                                            <span class="mt-0.5 block text-xs text-ink-500">{{ $setting->description }}</span>
                                        @endif
                                    </span>
                                </label>
                            @elseif ($setting->type === 'text')
                                <x-ui.textarea
                                    wire:model="values.{{ $setting->key }}"
                                    name="setting_{{ $setting->key }}"
                                    :label="$setting->label"
                                    :hint="$setting->description"
                                    :rows="3"
                                />
                            @else
                                <x-ui.input
                                    wire:model="values.{{ $setting->key }}"
                                    name="setting_{{ $setting->key }}"
                                    :type="$setting->type === 'integer' ? 'number' : 'text'"
                                    :label="$setting->label"
                                    :hint="$setting->description"
                                />
                            @endif

                            <p class="mt-1 font-mono text-[10px] text-ink-300">{{ $setting->key }}</p>
                        </div>
                    @endforeach
                </div>

                <x-ui.button type="submit" variant="primary" class="mt-5" loading="saveGroup">
                    <span wire:loading.remove wire:target="saveGroup">{{ __('hanbell.common.save_changes') }}</span>
                    <span wire:loading wire:target="saveGroup">{{ __('hanbell.common.loading') }}</span>
                </x-ui.button>
            </x-admin.panel>
        </form>
    @endif
</div>
