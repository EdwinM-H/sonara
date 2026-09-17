@props(['label', 'value', 'icon' => null, 'accent' => 'text-ink'])

<div class="card card-body !p-5 flex items-start justify-between gap-3 u-elevate">
    <div class="min-w-0">
        <p class="mono-label">{{ $label }}</p>
        <p class="mt-1.5 text-3xl font-display font-extrabold {{ $accent }}">{{ $value }}</p>
    </div>
    @if ($icon)
        <span class="grid place-items-center w-12 h-12 rounded-2xl shrink-0" style="background: var(--color-lavender); color: var(--color-primary)" aria-hidden="true">
            <x-icon name="{{ $icon }}" />
        </span>
    @endif
</div>
