<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.pages') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $pages->total() }} page(s). The footer is generated from this list, so every link resolves.
            </p>
        </div>

        <x-ui.button :href="route('admin.pages.create')" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            {{ __('hanbell.common.create') }}
        </x-ui.button>
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Page title…" />
        </div>

        @if ($pages->isEmpty())
            <x-ui.empty-state icon="document-text" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">Page</th>
                            <th scope="col" class="px-4 py-2.5">Group</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Updated</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($pages as $page)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-semibold text-ink-900 transition hover:text-brand-700">
                                        {{ $page->title }}
                                    </a>
                                    <p class="font-mono text-[11px] text-ink-400">/pages/{{ $page->slug }}</p>
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge variant="neutral" size="sm" class="capitalize">{{ $page->group }}</x-ui.badge>
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$page->status->badgeClasses()" size="sm">{{ $page->status->label() }}</x-ui.badge>
                                </td>

                                <td class="px-4 py-3 text-right text-xs text-ink-500">{{ $page->updated_at->format('j M Y') }}</td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener"
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700" title="View">
                                            <x-heroicon-o-eye class="size-4" />
                                        </a>

                                        <button type="button" wire:click="togglePublish({{ $page->id }})"
                                            class="rounded-md p-1.5 transition {{ $page->isPublished() ? 'text-warning-500 hover:bg-warning-50' : 'text-brand-600 hover:bg-brand-50' }}"
                                            title="{{ $page->isPublished() ? 'Unpublish' : 'Publish' }}">
                                            <x-heroicon-o-power class="size-4" />
                                        </button>

                                        <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700">
                                            <x-heroicon-o-pencil-square class="size-4" />
                                        </a>

                                        <button type="button" wire:click="delete({{ $page->id }})"
                                            wire:confirm="{{ __('hanbell.admin.confirm_delete') }}"
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-danger-50 hover:text-danger-600">
                                            <x-heroicon-o-trash class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $pages->links() }}</div>
        @endif
    </x-admin.panel>
</div>
