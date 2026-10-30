@extends('layouts.app')

@section('title', 'QR Absensi')
@section('subtitle', $activity->name)

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card bg-army-900 p-6 text-center xl:col-span-2">
            <p class="text-xs font-medium uppercase tracking-widest text-cream-300/70">QR Code dinamis</p>
            <h2 class="mt-1 text-lg font-semibold text-cream-50">{{ $activity->name }}</h2>
            <p class="text-xs text-cream-300/70">{{ $activity->start_at->format('d M Y H:i') }} · {{ $activity->location ?: 'Lokasi belum diisi' }}</p>

            <div id="qr-container" class="mx-auto mt-6 flex h-72 w-72 items-center justify-center rounded-xl bg-white p-3 [&>svg]:h-full [&>svg]:w-full">
                <p class="text-sm text-army-500">Memuat QR...</p>
            </div>

            <p id="qr-status" class="mt-4 text-sm text-cream-200">Menyiapkan QR Code...</p>
            <div class="mx-auto mt-3 h-1.5 w-64 overflow-hidden rounded-full bg-white/15">
                <div id="qr-progress" class="h-full w-full rounded-full bg-cream-300 transition-[width] duration-1000 ease-linear"></div>
            </div>
            <p class="mt-3 text-xs text-cream-300/60">QR diperbarui setiap {{ $ttl }} detik. Token kedaluwarsa tidak dapat dipakai.</p>
        </section>

        <section class="card p-4">
            <h3 class="text-sm font-semibold text-army-900">Informasi sesi</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Status sesi</dt>
                    <dd><x-status-badge :status="$session->status" /></dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Dibuka</dt>
                    <dd class="font-medium">{{ $session->opened_at?->format('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Toleransi</dt>
                    <dd class="font-medium">{{ $activity->late_tolerance_minutes }} menit</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Batas tepat waktu</dt>
                    <dd class="font-medium">{{ $activity->lateThreshold()->format('d M Y H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-4 space-y-2">
                <a href="{{ route('activities.show', $activity) }}" class="btn btn-secondary w-full">Kembali ke detail</a>
                <form method="POST" action="{{ route('activities.session.close', $activity) }}" onsubmit="return confirm('Tutup sesi absensi sekarang?')">
                    @csrf
                    <button class="btn btn-danger w-full">Tutup sesi absensi</button>
                </form>
            </div>

            <p class="mt-4 text-xs leading-relaxed text-army-500">
                Tampilkan halaman ini di perangkat admin. Pengurus memindai QR melalui menu Pindai QR pada akun masing-masing.
            </p>
        </section>
    </div>

    <script>
        (() => {
            const tokenUrl = @json(route('attendance-sessions.token', $session));
            const ttl = {{ (int) $ttl }};
            const container = document.getElementById('qr-container');
            const status = document.getElementById('qr-status');
            const progress = document.getElementById('qr-progress');
            let remaining = ttl;
            let timer = null;

            const tick = () => {
                remaining -= 1;

                if (remaining <= 0) {
                    progress.style.width = '0%';
                    loadToken();

                    return;
                }

                progress.style.width = `${Math.max(0, (remaining / ttl) * 100)}%`;
                status.textContent = `Berlaku ${remaining} detik lagi`;
            };

            const loadToken = async () => {
                clearInterval(timer);

                try {
                    const response = await fetch(tokenUrl, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });

                    if (!response.ok) {
                        throw new Error('Sesi tidak aktif');
                    }

                    const data = await response.json();

                    container.innerHTML = data.svg;
                    remaining = ttl;
                    progress.style.width = '100%';
                    status.textContent = `Berlaku ${remaining} detik lagi`;
                    timer = setInterval(tick, 1000);
                } catch (error) {
                    container.innerHTML = '<p class="text-sm text-red-600">Sesi absensi sudah ditutup.</p>';
                    status.textContent = 'QR tidak tersedia';
                    progress.style.width = '0%';
                }
            };

            loadToken();
        })();
    </script>
@endsection
