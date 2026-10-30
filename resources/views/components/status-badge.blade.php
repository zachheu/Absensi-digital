@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $classes = match ($value) {
        'hadir', 'approved', 'published', 'ongoing', 'open' => 'bg-army-100 text-army-800 border-army-200',
        'izin' => 'bg-amber-50 text-amber-800 border-amber-200',
        'sakit' => 'bg-sky-50 text-sky-800 border-sky-200',
        'alpa', 'rejected' => 'bg-red-50 text-red-700 border-red-200',
        'pending' => 'bg-cream-200 text-army-800 border-cream-400',
        default => 'bg-cream-100 text-army-700 border-cream-300',
    };

    $label = $status instanceof \BackedEnum ? $status->label() : $status;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium '.$classes]) }}>
    {{ $label }}
</span>
