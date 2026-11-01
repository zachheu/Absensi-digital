@extends('layouts.app')

@section('title', 'Kegiatan')
@section('subtitle', 'Daftar kegiatan HIMATIF')

@section('content')
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-cream-200 px-4 py-3">
            <div class="min-w-40 flex-1">
                <label for="search" class="label">Cari</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Nama atau lokasi kegiatan">
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
            <button class="btn btn-primary">Terapkan</button>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('activities.create') }}" class="btn btn-secondary ml-auto">+ Kegiatan</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th>Panitia</th>
                        <th>Absensi</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td>
                                <p class="font-medium text-army-900">{{ $activity->name }}</p>
                                <p class="text-xs text-army-600">{{ $activity->location ?: 'Lokasi belum diisi' }}</p>
                            </td>
                            <td class="text-xs text-army-600">
                                {{ $activity->start_at->format('d M Y H:i') }}
                                <br>s.d. {{ $activity->end_at->format('d M Y H:i') }}
                            </td>
                            <td><x-status-badge :status="$activity->status" /></td>
                            <td>{{ $activity->committee_members_count }}</td>
                            <td>{{ $activity->attendances_count }}</td>
                            <td class="text-right">
                                <a href="{{ route('activities.show', $activity) }}" class="text-xs font-medium text-army-700 hover:text-army-900">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-army-600">Belum ada kegiatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $activities->links() }}
        </div>
    </div>
@endsection
