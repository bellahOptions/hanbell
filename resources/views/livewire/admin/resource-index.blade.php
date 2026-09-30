@php
    /**
     * Shared view for every screen extending Admin\ResourceIndex.
     *
     * The subclass supplies columns() and fields(), so this renders a search +
     * sort table, an inline create/edit panel, and a delete action without any
     * of them being re-implemented per screen.
     */
    $recordColumns = $this->columns();
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $heading }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $records->total() }} record(s)</p>
        </div>

        <x-ui.button wire:click="create" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            {{ __('hanbell.common.create') }}
        </x-ui.button>
    </div>

    {{-- Create / edit panel --}}
    @if ($showForm)
        <x-admin.panel :title="$editingId ? __('hanbell.common.edit') : __('hanbell.common.create')">
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($this->fields() as $key => $field)
                        @php $options = $field['options'] ?? []; @endphp

                        @if (is_string($options) && method_exists($this, 'optionsFor'))
                            @php $options = $this->optionsFor($options); @endphp
                        @endif

                        @if (($field['type'] ?? 'text') === 'textarea')
                            <x-ui.textarea
                                wire:model="form.{{ $key }}"
                                :label="$field['label']"
                                class="sm:col-span-2"
                            />
                        @elseif (($field['type'] ?? 'text') === 'select')
                            <x-ui.select
                                wire:model="form.{{ $key }}"
                                :label="$field['label']"
                                :options="$options"
                                :placeholder="$field['label']"
                            />
                        @elseif (($field['type'] ?? 'text') === 'number')
                            <x-ui.input wire:model="form.{{ $key }}" type="number" :label="$field['label']" />
                        @else
                            <x-ui.input wire:model="form.{{ $key }}" :type="$field['type'] ?? 'text'" :label="$field['label']" />
                        @endif
                    @endforeach
                </div>

                @error('form.*')
                    <x-ui.alert variant="danger">{{ $message }}</x-ui.alert>
                @enderror

                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary" loading="save">
                        <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="cancel" variant="ghost">
                        {{ __('hanbell.common.cancel') }}
                    </x-ui.button>
                </div>
            </form>
        </x-admin.panel>
    @endif

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" />
        </div>

        @if ($records->isEmpty())
            <x-ui.empty-state
                icon="folder-open"
                :title="__('hanbell.common.no_items')"
                :message="__('hanbell.admin.search_placeholder')"
            />
        @else
            <x-admin.table :headers="array_merge(array_values($recordColumns), [__('hanbell.common.actions')])">
                @foreach ($records as $record)
                    <tr class="transition hover:bg-ink-50/60">
                        @foreach (array_keys($recordColumns) as $column)
                            <td class="px-4 py-3">
                                @php $value = data_get($record, $column); @endphp

                                @if (is_bool($value))
                                    <x-ui.badge :variant="$value ? 'brand' : 'neutral'" size="sm">
                                        {{ $value ? __('hanbell.common.yes') : __('hanbell.common.no') }}
                                    </x-ui.badge>
                                @elseif ($value instanceof \Carbon\CarbonInterface)
                                    <span class="text-xs text-ink-500">{{ $value->format('j M Y') }}</span>
                                @elseif ($column === 'department' && $record instanceof \App\Models\Category)
                                    <span class="text-sm text-ink-600">{{ $record->department?->name ?? '—' }}</span>
                                @elseif (is_null($value) || $value === '')
                                    <span class="text-ink-300">—</span>
                                @else
                                    <span class="clamp-1 text-sm text-ink-800">{{ $value }}</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    wire:click="edit({{ $record->getKey() }})"
                                    class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                                    aria-label="{{ __('hanbell.common.edit') }}"
                                >
                                    <x-heroicon-o-pencil-square class="size-4" />
                                </button>

                                @if (array_key_exists('is_active', $recordColumns) || in_array('is_active', $record->getFillable(), true))
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $record->getKey() }})"
                                        class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                                        aria-label="{{ __('hanbell.common.update') }}"
                                    >
                                        <x-heroicon-o-arrow-path class="size-4" />
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    wire:click="delete({{ $record->getKey() }})"
                                    wire:confirm="{{ __('hanbell.admin.confirm_delete') }}"
                                    class="rounded-md p-1.5 text-ink-400 transition hover:bg-danger-50 hover:text-danger-600"
                                    aria-label="{{ __('hanbell.common.delete') }}"
                                >
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            <div class="border-t border-ink-100 p-4">
                {{ $records->links() }}
            </div>
        @endif
    </x-admin.panel>
</div>
