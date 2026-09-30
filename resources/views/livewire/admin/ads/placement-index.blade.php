<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.placements') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-ink-500">
            Every ad slot on the site. Slots are a closed set defined in
            <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[11px]">App\Enums\AdPlacementKey</code>,
            and each has exactly one real call site — so a slot cannot render nothing forever because of a typo.
        </p>
    </div>

    <x-admin.panel padding="none">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-ink-200 bg-ink-50/60">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                        <th scope="col" class="px-4 py-2.5">Slot</th>
                        <th scope="col" class="px-4 py-2.5">Key</th>
                        <th scope="col" class="px-4 py-2.5">Recommended size</th>
                        <th scope="col" class="px-4 py-2.5 text-right">Max creatives</th>
                        <th scope="col" class="px-4 py-2.5 text-right">Active campaigns</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @foreach ($placements as $placement)
                        <tr class="transition hover:bg-ink-50/60">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink-900">{{ $placement['label'] }}</p>
                                <p class="mt-0.5 max-w-lg text-xs leading-relaxed text-ink-500">{{ $placement['description'] }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[11px] text-ink-600">{{ $placement['key'] }}</code>
                            </td>

                            <td class="px-4 py-3 text-xs tabular-nums text-ink-600">{{ $placement['size'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ $placement['max'] }}</td>

                            <td class="px-4 py-3 text-right">
                                @if ($placement['campaigns'] > 0)
                                    <x-ui.badge variant="brand" size="sm">{{ $placement['campaigns'] }}</x-ui.badge>
                                @else
                                    <span class="text-xs text-ink-400">Empty — available</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.panel>
</div>
