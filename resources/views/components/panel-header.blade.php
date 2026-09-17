@props(['title', 'subtitle' => null])

<div class="mb-7">
    <h1 class="text-3xl font-display font-extrabold text-ink tracking-tight">{{ $title }}</h1>
    @if ($subtitle)
        <p class="text-gray-600 mt-1.5">{{ $subtitle }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-3 flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
