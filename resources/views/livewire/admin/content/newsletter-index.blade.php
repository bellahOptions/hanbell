<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.newsletter') }}</h1>
        <p class="mt-1 text-sm text-ink-500">
            {{ number_format($activeCount) }} active of {{ number_format($totalCount) }} total subscriber(s).
        </p>
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Email address…" />
        </div>

        @if ($subscribers->isEmpty())
            <x-ui.empty-state icon="envelope" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">Email</th>
                            <th scope="col" class="px-4 py-2.5">Language</th>
                            <th scope="col" class="px-4 py-2.5">Source</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Subscribed</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($subscribers as $subscriber)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3 font-medium text-ink-800">{{ $subscriber->email }}</td>
                                <td class="px-4 py-3 text-xs uppercase text-ink-500">{{ $subscriber->locale }}</td>
                                <td class="px-4 py-3 text-xs text-ink-500">{{ $subscriber->source ?: '—' }}</td>

                                <td class="px-4 py-3">
                                    @if ($subscriber->is_active && ! $subscriber->unsubscribed_at)
                                        <x-ui.badge variant="brand" size="sm">Active</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" size="sm">Unsubscribed</x-ui.badge>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-xs text-ink-500">
                                    {{ $subscriber->created_at->format('j M Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $subscribers->links() }}</div>
        @endif
    </x-admin.panel>
</div>
