@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'icon' => null,
    'suffix' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-9 text-sm',
        'md' => 'h-11 text-sm',
        'lg' => 'h-12 text-base',
    ];

    // A Livewire validation error is looked up by field name when the caller
    // does not pass one explicitly, so forms do not have to wire it by hand.
    $fieldError = $error ?? ($name ? $errors->first($name) : null);

    $inputId = $id ?? ($name ? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : null);

    $control = 'w-full rounded-lg border bg-white text-ink-900 placeholder:text-ink-400 '
        .'transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-0 '
        .'disabled:cursor-not-allowed disabled:bg-ink-50 disabled:text-ink-500 '
        .($sizes[$size] ?? $sizes['md']).' '
        .($fieldError
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/25'
            : 'border-ink-300 focus:border-brand-600 focus:ring-brand-600/25')
        .($icon ? ' pl-10' : ' px-3.5')
        .($suffix ? ' pr-16' : '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-ink-800">
            {{ $label }}
            @if (isset($required) && $required)
                <span class="text-danger-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <x-dynamic-component
                :component="'heroicon-o-' . $icon"
                class="pointer-events-none absolute left-3 top-1/2 size-4.5 -translate-y-1/2 text-ink-400"
            />
        @endif

        <input
            type="{{ $type }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($inputId) id="{{ $inputId }}" @endif
            @if ($fieldError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
            @if ($hint && ! $fieldError) aria-describedby="{{ $inputId }}-hint" @endif
            {{ $attributes->except('class')->merge(['class' => $control]) }}
        >

        @if ($suffix)
            <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-ink-400">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if ($fieldError)
        <p id="{{ $inputId }}-error" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger-600">
            <x-heroicon-o-exclamation-circle class="mt-0.5 size-4 shrink-0" />
            <span>{{ $fieldError }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $inputId }}-hint" class="mt-1.5 text-sm text-ink-500">{{ $hint }}</p>
    @endif
</div>
