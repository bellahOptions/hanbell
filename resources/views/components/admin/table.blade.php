@props([
    'headers' => [],
    'striped' => false,
])

{{--
    Admin data table.

    A wrapping div with `overflow-x-auto` rather than a responsive card
    transformation: an operator on a tablet needs the columns, so horizontal
    scroll is the right behaviour on a data table.
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full min-w-full text-sm">
        @if (! empty($headers))
            <thead class="border-b border-ink-200 bg-ink-50/60">
                <tr>
                    @foreach ($headers as $header)
                        <th
                            scope="col"
                            @class([
                                'whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-ink-500',
                                'text-left' => ! str_ends_with((string) $header, ':right'),
                                'text-right' => str_ends_with((string) $header, ':right'),
                            ])
                        >
                            {{ str_replace(':right', '', (string) $header) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody @class(['divide-y divide-ink-100', 'even:[&>tr]:bg-ink-50/40' => $striped])>
            {{ $slot }}
        </tbody>
    </table>
</div>
