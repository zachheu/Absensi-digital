@extends('layouts.app')

@section('title', 'Izin & Sakit')
@section('subtitle', 'Pengajuan dan status persetujuan')

@section('content')
    <div class="card">
        <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-army-900">Pengajuan saya</h2>
            <a href="{{ route('permission-requests.create') }}" class="btn btn-primary">+ Ajukan</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Jenis</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Catatan Admin</th>
                        <th>Diajukan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $permissionRequest)
                        <tr>
                            <td class="font-medium text-army-900">{{ $permissionRequest->activity->name }}</td>
                            <td>{{ $permissionRequest->type->label() }}</td>
                            <td class="max-w-xs text-xs text-army-600">{{ \Illuminate\Support\Str::limit($permissionRequest->reason, 80) }}</td>
                            <td><x-status-badge :status="$permissionRequest->status" /></td>
                            <td class="max-w-xs text-xs text-army-600">{{ $permissionRequest->review_notes ?: '-' }}</td>
                            <td class="text-xs">{{ $permissionRequest->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-army-600">Belum ada pengajuan izin atau sakit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">{{ $requests->links() }}</div>
    </div>
@endsection
