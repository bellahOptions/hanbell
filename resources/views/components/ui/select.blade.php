@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-9 text-sm',
        'md' => 'h-11 text-sm',
        'lg' => 'h-12 text-base',
    ];

    $fieldError = $error ?? ($name ? $errors->first($name) : null);
    $selectId = $id ?? ($name ? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : null);

    $control = 'w-full appearance-none rounded-lg border bg-white pl-3.5 pr-10 text-ink-900 '
        .'transition-colors duration-150 focus:outline-none focus:ring-2 '
        .'disabled:cursor-not-allowed disabled:bg-ink-50 '
        .($sizes[$size] ?? $sizes['md']).' '
        .($fieldError
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/25'
            : 'border-ink-300 focus:border-brand-600 focus:ring-brand-600/25');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $selectId }}" class="mb-1.5 block text-sm font-medium text-ink-800">{{ $label }}</label>
    @endif

    <div class="relative">
        <select
            @if ($name) name="{{ $name }}" @endif
            @if ($selectId) id="{{ $selectId }}" @endif
            @if ($fieldError) aria-invalid="true" @endif
            {{ $attributes->except('class')->merge(['class' => $control]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach ($options as $value => $optionLabel)
                {{-- Options may be passed as a flat map or as grouped arrays. --}}
                @if (is_array($optionLabel))
                    <optgroup label="{{ $value }}">
                        @foreach ($optionLabel as $groupValue => $groupLabel)
                            <option value="{{ $groupValue }}">{{ $groupLabel }}</option>
                        @endforeach
                    </optgroup>
                @else
                    <option value="{{ $value }}">{{ $optionLabel }}</option>
                @endif
            @endforeach

            {{ $slot }}
        </select>

        <x-heroicon-m-chevron-down class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
    </div>

    @if ($fieldError)
        <p class="mt-1.5 text-sm text-danger-600">{{ $fieldError }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-sm text-ink-500">{{ $hint }}</p>
    @endif
</div>
