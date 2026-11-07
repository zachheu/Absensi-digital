@props(['label', 'value', 'hint' => null])

<div class="card p-4">
    <p class="text-xs font-medium uppercase tracking-wide text-army-600">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-army-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-army-600">{{ $hint }}</p>
    @endif
</div>
