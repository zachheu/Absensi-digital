@extends('layouts.app')

@section('title', 'Rekap & Laporan')
@section('subtitle', 'Satu baris per absensi, tanpa perhitungan ganda')

@section('content')
    @php
        $exportQuery = request()->query();
    @endphp

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-cream-200 px-4 py-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="from" class="label">Tanggal mulai</label>
                <input id="from" type="date" name="from" value="{{ request('from') }}" class="input">
                @error('from') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="to" class="label">Tanggal akhir</label>
                <input id="to" type="date" name="to" value="{{ request('to') }}" class="input">
                @error('to') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
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

            <div>
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
                <label for="field" class="label">Bidang</label>
                <select id="field" name="field" class="input">
                    <option value="">Semua bidang</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}" @selected((string) request('field') === (string) $field->id)>{{ $field->code }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="position" class="label">Jabatan kepanitiaan</label>
                <select id="position" name="position" class="input">
                    <option value="">Semua jabatan</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" @selected((string) request('position') === (string) $position->id)>{{ $position->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="late" class="label">Keterlambatan</label>
                <select id="late" name="late" class="input">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('late') === '1')>Hanya terlambat</option>
                    <option value="0" @selected(request('late') === '0')>Hanya tepat waktu</option>
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-4">
                <button class="btn btn-primary">Terapkan filter</button>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Reset</a>
                <a href="{{ route('reports.excel', $exportQuery) }}" class="btn btn-secondary ml-auto">Ekspor Excel</a>
                <a href="{{ route('reports.pdf', $exportQuery) }}" class="btn btn-secondary">Ekspor PDF</a>
            </div>
        </form>

        <div class="grid gap-3 border-b border-cream-200 px-4 py-4 sm:grid-cols-3 xl:grid-cols-6">
            <x-stat label="Total" :value="$summary['total']" />
            <x-stat label="Hadir" :value="$summary['hadir']" />
            <x-stat label="Izin" :value="$summary['izin']" />
            <x-stat label="Sakit" :value="$summary['sakit']" />
            <x-stat label="Alpa" :value="$summary['alpa']" />
            <x-stat label="Terlambat" :value="$summary['late']" />
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Pengurus</th>
                        <th>Bidang</th>
                        <th>Kegiatan</th>
                        <th>Jabatan</th>
                        <th>Check-in</th>
                        <th>Status</th>
                        <th>Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>
                                <p class="font-medium text-army-900">{{ $attendance->user->name }}</p>
                                <p class="text-xs text-army-500">{{ $attendance->user->npp }} · {{ $attendance->user->npm }}</p>
                            </td>
                            <td class="text-xs">{{ $attendance->user->organizationalField?->code ?? '-' }}</td>
                            <td>
                                <p class="text-sm text-army-900">{{ $attendance->activity->name }}</p>
                                <p class="text-xs text-army-500">{{ $attendance->activity->start_at->format('d M Y H:i') }}</p>
                            </td>
                            <td class="text-xs">
                                {{ $attendance->user->committeeMembers->where('activity_id', $attendance->activity_id)->pluck('position.name')->filter()->implode(', ') ?: '-' }}
                            </td>
                            <td class="text-xs">{{ $attendance->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                <x-status-badge :status="$attendance->status" />
                                @if ($attendance->is_late)
                                    <span class="ml-1 text-[11px] font-medium text-red-600">Terlambat</span>
                                @endif
                            </td>
                            <td class="text-xs uppercase text-army-500">{{ $attendance->source }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-army-500">Tidak ada data absensi sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">{{ $attendances->links() }}</div>
    </div>
@endsection
