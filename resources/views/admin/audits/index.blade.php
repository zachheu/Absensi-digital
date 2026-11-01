@extends('layouts.app')

@section('title', 'Audit Log')
@section('subtitle', 'Riwayat koreksi dan perubahan absensi')

@section('content')
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-cream-200 px-4 py-3">
            <div class="min-w-44 flex-1">
                <label for="activity" class="label">Kegiatan</label>
                <select id="activity" name="activity" class="input">
                    <option value="">Semua kegiatan</option>
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->id }}" @selected((string) request('activity') === (string) $activity->id)>
                            {{ $activity->name }} · {{ $activity->start_at->format('d M Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label for="user" class="label">Pengurus</label>
                <select id="user" name="user" class="input">
                    <option value="">Semua pengurus</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user') === (string) $user->id)>
                            {{ $user->name }} · {{ $user->npp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="label">Dari</label>
                <input id="from" type="date" name="from" value="{{ request('from') }}" class="input">
            </div>

            <div>
                <label for="to" class="label">Sampai</label>
                <input id="to" type="date" name="to" value="{{ request('to') }}" class="input">
            </div>

            <button class="btn btn-primary">Terapkan</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aksi</th>
                        <th>Pengurus</th>
                        <th>Kegiatan</th>
                        <th>Perubahan</th>
                        <th>Alasan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $audit->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="text-xs font-medium text-army-800">{{ $audit->action }}</td>
                            <td>
                                <p class="text-sm text-army-900">{{ $audit->user?->name ?? '-' }}</p>
                                <p class="text-xs text-army-600">{{ $audit->user?->npp ?? '-' }}</p>
                            </td>
                            <td class="text-xs">
                                <a href="{{ route('activities.show', $audit->activity_id) }}" class="font-medium text-army-700 hover:text-army-900">
                                    {{ $audit->activity?->name ?? '-' }}
                                </a>
                            </td>
                            <td class="max-w-xs text-xs">
                                @if ($audit->old_values)
                                    <p class="text-army-600">Sebelum: {{ json_encode($audit->old_values, JSON_UNESCAPED_SLASHES) }}</p>
                                @endif
                                <p class="text-army-800">Sesudah: {{ json_encode($audit->new_values, JSON_UNESCAPED_SLASHES) }}</p>
                            </td>
                            <td class="max-w-xs text-xs text-army-700">{{ $audit->reason }}</td>
                            <td class="text-xs">
                                {{ $audit->actor?->name ?? 'Sistem' }}
                                @if ($audit->attendance)
                                    <p class="mt-1">
                                        <a href="{{ route('admin.audits.attendance', $audit->attendance_id) }}" class="font-medium text-army-600 hover:text-army-800">Detail</a>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-army-600">Belum ada catatan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">{{ $audits->links() }}</div>
    </div>
@endsection
