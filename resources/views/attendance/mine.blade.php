@extends('layouts.app')

@section('title', 'Absensi Saya')
@section('subtitle', 'Riwayat kehadiran pribadi')

@section('content')
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-cream-200 px-4 py-3">
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Terapkan</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Jadwal</th>
                        <th>Check-in</th>
                        <th>Status</th>
                        <th>Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td class="font-medium text-army-900">{{ $attendance->activity->name }}</td>
                            <td class="text-xs text-army-600">{{ $attendance->activity->start_at->format('d M Y H:i') }}</td>
                            <td class="text-xs">{{ $attendance->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                <x-status-badge :status="$attendance->status" />
                                @if ($attendance->is_late) <span class="ml-1 text-[11px] font-medium text-red-600">Terlambat</span> @endif
                            </td>
                            <td class="text-xs uppercase text-army-500">{{ $attendance->source }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-army-500">Belum ada riwayat absensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">{{ $attendances->links() }}</div>
    </div>
@endsection
