@props(['label', 'value', 'hint' => null])

<div class="card relative overflow-hidden p-4">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-army-500 via-accent-500 to-cream-400"></div>
    <p class="text-xs font-medium uppercase tracking-wide text-army-600">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold bg-gradient-to-br from-army-800 to-accent-600 bg-clip-text text-transparent">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-army-600">{{ $hint }}</p>
    @endif
</div>
