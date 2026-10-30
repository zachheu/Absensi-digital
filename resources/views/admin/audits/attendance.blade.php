@extends('layouts.app')

@section('title', 'Audit Absensi')
@section('subtitle', $attendance->user->name.' · '.$attendance->activity->name)

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card h-fit p-4">
            <h2 class="text-sm font-semibold text-army-900">Data absensi saat ini</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Pengurus</dt>
                    <dd class="font-medium text-army-900">{{ $attendance->user->name }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">NPP / NPM</dt>
                    <dd class="text-right text-xs">{{ $attendance->user->npp }} / {{ $attendance->user->npm }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Kegiatan</dt>
                    <dd class="text-right text-xs">{{ $attendance->activity->name }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Status</dt>
                    <dd><x-status-badge :status="$attendance->status" /></dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Check-in</dt>
                    <dd class="text-xs">{{ $attendance->check_in_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Terlambat</dt>
                    <dd class="text-xs">{{ $attendance->is_late ? 'Ya' : 'Tidak' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-army-500">Sumber</dt>
                    <dd class="text-xs uppercase">{{ $attendance->source }}</dd>
                </div>
            </dl>

            <a href="{{ route('activities.show', $attendance->activity) }}" class="btn btn-secondary mt-4 w-full">Kembali ke kegiatan</a>
        </section>

        <section class="card xl:col-span-2">
            <div class="border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Riwayat perubahan</h2>
                <p class="mt-1 text-xs text-army-500">Audit bersifat append-only, tidak dapat diubah atau dihapus dari aplikasi.</p>
            </div>

            <div class="divide-y divide-cream-200">
                @forelse ($audits as $audit)
                    <div class="px-4 py-4 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-army-900">{{ $audit->action }}</p>
                            <p class="text-army-500">{{ $audit->created_at?->format('d M Y H:i:s') }}</p>
                        </div>
                        <p class="mt-1 text-army-500">Oleh {{ $audit->actor?->name ?? 'Sistem' }}</p>
                        <p class="mt-2 text-army-700">{{ $audit->reason }}</p>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-lg bg-cream-100 p-2">
                                <p class="font-medium text-army-600">Sebelum</p>
                                <pre class="mt-1 overflow-x-auto whitespace-pre-wrap break-all text-[11px] text-army-700">{{ $audit->old_values ? json_encode($audit->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
                            </div>
                            <div class="rounded-lg bg-army-50 p-2">
                                <p class="font-medium text-army-700">Sesudah</p>
                                <pre class="mt-1 overflow-x-auto whitespace-pre-wrap break-all text-[11px] text-army-800">{{ json_encode($audit->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada audit untuk absensi ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
