@extends('layouts.app')

@section('title', 'Pindai QR Absensi')
@section('subtitle', 'Arahkan kamera ke QR Code yang ditampilkan admin')

@section('content')
    <div class="grid gap-4 xl:grid-cols-3" data-scan-page data-scan-endpoint="{{ route('scan.store') }}">
        <section class="card p-4 xl:col-span-2">
            <div id="qr-reader" class="mx-auto w-full max-w-md overflow-hidden rounded-xl border border-cream-300 bg-cream-100"></div>
            <div id="scan-message" class="mt-4 rounded-lg border border-cream-300 bg-cream-100 px-4 py-3 text-sm text-army-700">
                Menyiapkan kamera...
            </div>

            <form method="POST" action="{{ route('scan.store') }}" class="mt-5 border-t border-cream-200 pt-4">
                @csrf
                <label for="token" class="label">Input token manual</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="token" name="token" class="input font-mono text-xs" placeholder="Tempel token 64 karakter bila kamera tidak tersedia" required pattern="[a-f0-9]{64}">
                    <button class="btn btn-secondary sm:w-auto">Kirim</button>
                </div>
                <p class="help">Gunakan hanya bila kamera perangkat tidak berfungsi.</p>
            </form>
        </section>

        <section class="card p-4">
            <h2 class="text-sm font-semibold text-army-900">Kegiatan dengan sesi terbuka</h2>
            <div class="mt-3 space-y-2">
                @forelse ($activeActivities as $activity)
                    <div class="rounded-lg border border-cream-300 bg-cream-50 p-3">
                        <p class="text-sm font-medium text-army-900">{{ $activity->name }}</p>
                        <p class="text-xs text-army-600">{{ $activity->start_at->format('d M Y H:i') }} · {{ $activity->location ?: 'Lokasi belum diisi' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-army-600">Belum ada sesi absensi yang terbuka.</p>
                @endforelse
            </div>
            <div class="mt-4 rounded-lg bg-cream-100 p-3 text-xs leading-relaxed text-army-600">
                Validasi dilakukan di server. Token yang sudah kedaluwarsa, dicabut, atau milik sesi lain akan ditolak.
            </div>
        </section>
    </div>
@endsection
