@php
    $scanResult = session('scan_result');
@endphp

@if (session('status'))
    <div class="mb-4 rounded-lg border border-army-200 bg-army-50 px-4 py-3 text-sm text-army-900">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold">Periksa kembali data berikut:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($scanResult)
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $scanResult['successful'] ? 'border-army-300 bg-army-100 text-army-900' : 'border-red-200 bg-red-50 text-red-800' }}">
        <p class="font-semibold">{{ $scanResult['successful'] ? 'Absensi tercatat' : 'Absensi gagal' }}</p>
        <p>{{ $scanResult['message'] }}</p>
        @if ($scanResult['successful'] && ! empty($scanResult['activity']))
            <p class="mt-1 text-xs">
                {{ $scanResult['activity'] }} · {{ $scanResult['status'] }} · {{ $scanResult['check_in_at'] }}
                @if ($scanResult['is_late']) · Terlambat @endif
            </p>
        @endif
    </div>
@endif
