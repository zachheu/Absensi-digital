@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('subtitle', 'Ringkasan kehadiran dan kegiatan HIMATIF')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Pengurus Aktif" :value="$activeUserCount" />
        <x-stat label="Kegiatan" :value="$activityCount" />
        <x-stat label="Total Hadir" :value="$attendanceCount" />
        <x-stat label="Terlambat" :value="$lateCount" />
        <x-stat label="Izin Menunggu" :value="$pendingRequestCount" />
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Kegiatan berlangsung</h2>
                <a href="{{ route('activities.index') }}" class="text-xs font-medium text-army-600 hover:text-army-800">Lihat semua</a>
            </div>
            <div class="divide-y divide-cream-200">
                @forelse ($ongoingActivities as $activity)
                    <a href="{{ route('activities.show', $activity) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-cream-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-army-900">{{ $activity->name }}</p>
                            <p class="text-xs text-army-500">{{ $activity->start_at->format('d M Y H:i') }} · {{ $activity->location ?: 'Lokasi belum diisi' }}</p>
                        </div>
                        <div class="text-right text-xs text-army-600">
                            <p>{{ $activity->attendances_count }} absensi</p>
                            <x-status-badge :status="$activity->status" class="mt-1" />
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Tidak ada kegiatan yang sedang berlangsung.</p>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Absensi terbaru</h2>
                <a href="{{ route('reports.index') }}" class="text-xs font-medium text-army-600 hover:text-army-800">Rekap</a>
            </div>
            <div class="divide-y divide-cream-200">
                @forelse ($recentAttendances as $attendance)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-army-900">{{ $attendance->user->name }}</p>
                            <p class="truncate text-xs text-army-500">{{ $attendance->activity->name }}</p>
                        </div>
                        <div class="text-right text-xs text-army-600">
                            <p>{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</p>
                            <x-status-badge :status="$attendance->status" class="mt-1" />
                            @if ($attendance->is_late)
                                <span class="ml-1 text-[11px] font-medium text-red-600">Terlambat</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada absensi tercatat.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('activities.create') }}" class="card flex items-center justify-between p-4 hover:border-army-300">
            <div>
                <p class="text-sm font-medium text-army-900">Buat kegiatan</p>
                <p class="text-xs text-army-500">Atur jadwal dan toleransi</p>
            </div>
            <span class="text-army-400">+</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="card flex items-center justify-between p-4 hover:border-army-300">
            <div>
                <p class="text-sm font-medium text-army-900">Kelola pengurus</p>
                <p class="text-xs text-army-500">NPP, NPM, dan bidang</p>
            </div>
            <span class="text-army-400">→</span>
        </a>
        <a href="{{ route('admin.audits.index') }}" class="card flex items-center justify-between p-4 hover:border-army-300">
            <div>
                <p class="text-sm font-medium text-army-900">Audit log</p>
                <p class="text-xs text-army-500">Riwayat koreksi absensi</p>
            </div>
            <span class="text-army-400">→</span>
        </a>
    </div>
@endsection
