@extends('layouts.app')

@section('title', 'Dashboard Pengurus')
@section('subtitle', 'Informasi kehadiran dan kegiatan kamu')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <section class="card p-4 lg:col-span-1">
            <p class="text-xs font-medium uppercase tracking-wide text-army-500">Identitas pengurus</p>
            <p class="mt-2 text-lg font-semibold text-army-900">{{ $user->name }}</p>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-army-500">NPP</dt>
                    <dd class="font-medium text-army-900">{{ $user->npp }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-army-500">NPM</dt>
                    <dd class="font-medium text-army-900">{{ $user->npm }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-army-500">Bidang</dt>
                    <dd class="font-medium text-army-900">{{ $user->organizationalField?->code ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-army-500">Jabatan</dt>
                    <dd class="font-medium text-army-900">{{ $user->structural_position?->label() ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-army-500">Status akun</dt>
                    <dd><span class="rounded-full bg-army-100 px-2 py-0.5 text-xs font-medium text-army-800">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd>
                </div>
            </dl>
            <div class="mt-4 grid gap-2">
                <a href="{{ route('scan.index') }}" class="btn btn-primary w-full">Pindai QR Absensi</a>
                <a href="{{ route('permission-requests.create') }}" class="btn btn-secondary w-full">Ajukan Izin / Sakit</a>
            </div>
        </section>

        <section class="card lg:col-span-2">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Kegiatan mendatang</h2>
                <a href="{{ route('activities.index') }}" class="text-xs font-medium text-army-600 hover:text-army-800">Lihat semua</a>
            </div>
            <div class="divide-y divide-cream-200">
                @forelse ($activities as $activity)
                    @php
                        $attendance = $activity->attendances->first();
                        $request = $activity->permissionRequests->first();
                    @endphp
                    <a href="{{ route('activities.show', $activity) }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-cream-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-army-900">{{ $activity->name }}</p>
                            <p class="text-xs text-army-500">{{ $activity->start_at->format('d M Y H:i') }} · {{ $activity->location ?: 'Lokasi belum diisi' }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            @if ($attendance)
                                <x-status-badge :status="$attendance->status" />
                            @elseif ($request)
                                <x-status-badge :status="$request->status" />
                            @else
                                <span class="rounded-full border border-cream-300 px-2.5 py-1 text-army-500">Belum absen</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada kegiatan yang dipublikasikan.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Riwayat absensi</h2>
                <a href="{{ route('attendances.mine') }}" class="text-xs font-medium text-army-600 hover:text-army-800">Semua</a>
            </div>
            <div class="divide-y divide-cream-200">
                @forelse ($recentAttendances as $attendance)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-army-900">{{ $attendance->activity->name }}</p>
                            <p class="text-xs text-army-500">{{ $attendance->activity->start_at->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <x-status-badge :status="$attendance->status" />
                            @if ($attendance->is_late)
                                <p class="mt-1 text-[11px] font-medium text-red-600">Terlambat</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada riwayat absensi.</p>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Pengajuan izin & sakit</h2>
                <a href="{{ route('permission-requests.index') }}" class="text-xs font-medium text-army-600 hover:text-army-800">Semua</a>
            </div>
            <div class="divide-y divide-cream-200">
                @forelse ($permissionRequests as $permissionRequest)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-army-900">{{ $permissionRequest->activity->name }}</p>
                            <p class="text-xs capitalize text-army-500">{{ $permissionRequest->type->label() }} · {{ $permissionRequest->created_at->format('d M Y') }}</p>
                        </div>
                        <x-status-badge :status="$permissionRequest->status" />
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada pengajuan.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
