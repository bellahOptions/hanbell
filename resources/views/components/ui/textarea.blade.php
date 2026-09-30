@props([
    'label' => null,
    'name' => null,
    'rows' => 4,
    'hint' => null,
    'error' => null,
])

@php
    $fieldError = $error ?? ($name ? $errors->first($name) : null);
    $areaId = $id ?? ($name ? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : null);

    $control = 'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 '
        .'transition-colors duration-150 focus:outline-none focus:ring-2 resize-y '
        .($fieldError
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/25'
            : 'border-ink-300 focus:border-brand-600 focus:ring-brand-600/25');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $areaId }}" class="mb-1.5 block text-sm font-medium text-ink-800">{{ $label }}</label>
    @endif

    <textarea
        @if ($name) name="{{ $name }}" @endif
        @if ($areaId) id="{{ $areaId }}" @endif
        rows="{{ $rows }}"
        @if ($fieldError) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => $control]) }}
    >{{ $slot }}</textarea>

    @if ($fieldError)
        <p class="mt-1.5 text-sm text-danger-600">{{ $fieldError }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-sm text-ink-500">{{ $hint }}</p>
    @endif
</div>
