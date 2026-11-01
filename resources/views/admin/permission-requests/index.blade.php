@extends('layouts.app')

@section('title', 'Persetujuan Izin & Sakit')
@section('subtitle', 'Tinjau pengajuan pengurus sebelum rekap difinalisasi')

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

            <div class="min-w-48 flex-1">
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

            <button class="btn btn-primary">Terapkan</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Pengurus</th>
                        <th>Kegiatan</th>
                        <th>Jenis</th>
                        <th>Alasan & Lampiran</th>
                        <th>Status</th>
                        <th>Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $permissionRequest)
                        <tr>
                            <td>
                                <p class="font-medium text-army-900">{{ $permissionRequest->user->name }}</p>
                                <p class="text-xs text-army-600">
                                    {{ $permissionRequest->user->npp }} · {{ $permissionRequest->user->organizationalField?->code ?? '-' }}
                                </p>
                            </td>
                            <td>
                                <p class="text-sm text-army-900">{{ $permissionRequest->activity->name }}</p>
                                <p class="text-xs text-army-600">{{ $permissionRequest->activity->start_at->format('d M Y H:i') }}</p>
                            </td>
                            <td>{{ $permissionRequest->type->label() }}</td>
                            <td class="max-w-sm">
                                <p class="text-xs text-army-700">{{ $permissionRequest->reason }}</p>
                                @if ($permissionRequest->attachment_path)
                                    <p class="mt-1 text-xs font-medium text-army-600">Lampiran tersimpan privat ({{ basename($permissionRequest->attachment_path) }})</p>
                                @endif
                                <p class="mt-1 text-[11px] text-army-600">Diajukan {{ $permissionRequest->created_at->format('d M Y H:i') }}</p>
                            </td>
                            <td>
                                <x-status-badge :status="$permissionRequest->status" />
                                @if ($permissionRequest->reviewer)
                                    <p class="mt-1 text-[11px] text-army-600">
                                        oleh {{ $permissionRequest->reviewer->name }}
                                    </p>
                                @endif
                            </td>
                            <td class="min-w-56">
                                @if ($permissionRequest->status === \App\Enums\PermissionRequestStatus::Pending)
                                    @if ($permissionRequest->activity->recap_finalized_at)
                                        <p class="text-xs text-army-600">Rekap sudah difinalisasi. Pengajuan tidak dapat diproses.</p>
                                    @else
                                        <form method="POST" action="{{ route('admin.permission-requests.approve', $permissionRequest) }}" class="space-y-2">
                                            @csrf
                                            <input name="review_notes" class="input text-xs" placeholder="Catatan (opsional)">
                                            <button class="btn btn-primary w-full py-1.5 text-xs">Setujui</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.permission-requests.reject', $permissionRequest) }}" class="mt-2 space-y-2">
                                            @csrf
                                            <input name="review_notes" required class="input text-xs" placeholder="Alasan penolakan (wajib)">
                                            <button class="btn btn-danger w-full py-1.5 text-xs">Tolak</button>
                                        </form>
                                    @endif
                                @else
                                    <p class="text-xs text-army-600">{{ $permissionRequest->review_notes ?: 'Tidak ada catatan.' }}</p>
                                    <p class="mt-1 text-[11px] text-army-600">
                                        {{ $permissionRequest->reviewed_at?->format('d M Y H:i') ?? '-' }}
                                    </p>
                                @endif
                            </td>
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
