@extends('layouts.app')

@section('title', $activity->name)
@section('subtitle', $activity->start_at->format('d M Y H:i').' · '.($activity->location ?: 'Lokasi belum diisi'))

@section('content')
    @php
        $admin = auth()->user()->isAdmin();
        $openSession = $activity->sessions->firstWhere('status', \App\Models\AttendanceSession::STATUS_OPEN);
    @endphp

    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card p-4 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$activity->status" />
                        @if ($activity->recap_finalized_at)
                            <span class="text-xs font-medium text-army-600">Rekap difinalisasi {{ $activity->recap_finalized_at->format('d M Y H:i') }}</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-army-600">
                        {{ $activity->start_at->format('d M Y H:i') }} s.d. {{ $activity->end_at->format('d M Y H:i') }}
                    </p>
                    <p class="text-sm text-army-600">Toleransi keterlambatan {{ $activity->late_tolerance_minutes }} menit · Batas tepat waktu {{ $activity->lateThreshold()->format('H:i') }}</p>
                    <p class="mt-1 text-xs text-army-500">Dibuat oleh {{ $activity->creator->name }}</p>
                </div>

                @if ($admin)
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('activities.edit', $activity) }}" class="btn btn-secondary">Edit</a>
                        @if ($openSession)
                            <a href="{{ route('activities.qr', $activity) }}" class="btn btn-primary">Tampilkan QR</a>
                        @elseif (! $activity->recap_finalized_at)
                            <form method="POST" action="{{ route('activities.session.open', $activity) }}">
                                @csrf
                                <button class="btn btn-primary">Buka absensi</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @if ($activity->description)
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-army-700">{{ $activity->description }}</p>
            @endif

            @if ($admin)
                <div class="mt-4 flex flex-wrap gap-2 border-t border-cream-200 pt-4">
                    @if ($openSession)
                        <form method="POST" action="{{ route('activities.session.close', $activity) }}" onsubmit="return confirm('Tutup sesi absensi? QR tidak dapat dipakai lagi.')">
                            @csrf
                            <button class="btn btn-danger">Tutup sesi absensi</button>
                        </form>
                    @endif

                    @if (! $openSession && ! $activity->recap_finalized_at)
                        <form method="POST" action="{{ route('activities.finalize', $activity) }}" onsubmit="return confirm('Finalisasi rekap? Pengurus yang belum hadir dan tanpa izin/sakit disetujui akan ditetapkan alpa.')">
                            @csrf
                            <button class="btn btn-secondary">Finalisasi rekap (tetapkan alpa)</button>
                        </form>
                    @endif

                    <a href="{{ route('admin.permission-requests.index', ['activity' => $activity->id]) }}" class="btn btn-secondary">
                        Persetujuan izin ({{ $pendingRequestCount }} menunggu)
                    </a>
                </div>
            @else
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-cream-200 pt-4">
                    @if ($ownAttendance)
                        <p class="text-sm text-army-700">
                            Status kamu: <x-status-badge :status="$ownAttendance->status" class="ml-1" />
                            @if ($ownAttendance->is_late) <span class="ml-1 text-xs font-medium text-red-600">Terlambat</span> @endif
                        </p>
                    @elseif ($ownPermissionRequest)
                        <p class="text-sm text-army-700">
                            Pengajuan {{ $ownPermissionRequest->type->label() }}:
                            <x-status-badge :status="$ownPermissionRequest->status" class="ml-1" />
                        </p>
                    @elseif ($openSession)
                        <a href="{{ route('scan.index') }}" class="btn btn-primary">Pindai QR absensi</a>
                    @elseif (! $activity->recap_finalized_at)
                        <a href="{{ route('permission-requests.create') }}" class="btn btn-secondary">Ajukan izin / sakit</a>
                    @endif
                </div>
            @endif
        </section>

        <section class="card p-4">
            <h2 class="text-sm font-semibold text-army-900">Ringkasan kehadiran</h2>
            <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg bg-cream-100 p-3">
                    <p class="text-xs text-army-500">Tercatat</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['total'] }}</p>
                </div>
                <div class="rounded-lg bg-army-100 p-3">
                    <p class="text-xs text-army-600">Hadir</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['hadir'] }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-3">
                    <p class="text-xs text-amber-700">Izin</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['izin'] }}</p>
                </div>
                <div class="rounded-lg bg-sky-50 p-3">
                    <p class="text-xs text-sky-700">Sakit</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['sakit'] }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-3">
                    <p class="text-xs text-red-700">Alpa</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['alpa'] }}</p>
                </div>
                <div class="rounded-lg bg-cream-100 p-3">
                    <p class="text-xs text-army-500">Terlambat</p>
                    <p class="text-xl font-semibold text-army-900">{{ $attendanceSummary['late'] }}</p>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Susunan kepanitiaan</h2>
                <span class="text-xs text-army-500">{{ $activity->committeeMembers->count() }} penugasan</span>
            </div>

            <div class="divide-y divide-cream-200">
                @forelse ($activity->committeeMembers->groupBy('position.name') as $positionName => $members)
                    <div class="px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-army-500">{{ $positionName }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($members as $member)
                                <span class="inline-flex items-center gap-2 rounded-full border border-cream-300 bg-cream-50 px-3 py-1 text-xs text-army-800">
                                    {{ $member->user->name }} ({{ $member->user->organizationalField?->code ?? '-' }})
                                    @if ($admin && $activity->status !== \App\Enums\ActivityStatus::Completed)
                                        <form method="POST" action="{{ route('activities.committee.destroy', [$activity, $member]) }}" onsubmit="return confirm('Hapus penugasan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700" aria-label="Hapus penugasan">×</button>
                                        </form>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada panitia yang ditugaskan.</p>
                @endforelse
            </div>

            @if ($admin && ! $activity->recap_finalized_at)
                <form method="POST" action="{{ route('activities.committee.store', $activity) }}" class="grid gap-3 border-t border-cream-200 px-4 py-4 sm:grid-cols-3">
                    @csrf
                    <div class="sm:col-span-1">
                        <label for="user_id" class="label">Pengurus</label>
                        <select id="user_id" name="user_id" class="input" required>
                            <option value="">Pilih pengurus</option>
                            @foreach ($candidateUsers as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->organizationalField?->code ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="committee_position_id" class="label">Jabatan</label>
                        <select id="committee_position_id" name="committee_position_id" class="input" required>
                            <option value="">Pilih jabatan</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="btn btn-primary w-full">Tugaskan</button>
                    </div>
                </form>
            @endif

            @if ($admin && ! $activity->recap_finalized_at && $activity->status !== \App\Enums\ActivityStatus::Completed && $activity->committeeMembers->isNotEmpty())
                <form method="POST" action="{{ route('activities.committee.update', $activity) }}" class="grid gap-3 border-t border-cream-200 px-4 py-4 sm:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-1">
                        <label for="committee_member_id" class="label">Pindahkan panitia</label>
                        <select id="committee_member_id" name="committee_member_id" class="input" required>
                            <option value="">Pilih panitia</option>
                            @foreach ($activity->committeeMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->user->name }} · {{ $member->position->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="new_committee_position_id" class="label">Jabatan baru</label>
                        <select id="new_committee_position_id" name="committee_position_id" class="input" required>
                            <option value="">Pilih jabatan</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="btn btn-secondary w-full">Pindahkan</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="card">
            <div class="flex items-center justify-between border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Absensi</h2>
                <span class="text-xs text-army-500">{{ $attendances->count() }} catatan</span>
            </div>
            <div class="max-h-[28rem] overflow-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Pengurus</th>
                            <th>Check-in</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $attendance)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $attendance->user->name }}</p>
                                    <p class="text-xs text-army-500">{{ $attendance->user->npp }} · {{ $attendance->user->organizationalField?->code ?? '-' }}</p>
                                </td>
                                <td class="text-xs">{{ $attendance->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    <x-status-badge :status="$attendance->status" />
                                    @if ($attendance->is_late) <span class="ml-1 text-[11px] font-medium text-red-600">Terlambat</span> @endif
                                </td>
                                <td class="text-right text-xs">
                                    @if ($canCorrectAttendance)
                                        <a href="{{ route('attendances.correct.edit', $attendance) }}" class="font-medium text-army-700 hover:text-army-900">Koreksi</a>
                                    @endif
                                    @if ($admin)
                                        <a href="{{ route('admin.audits.attendance', $attendance) }}" class="ml-2 font-medium text-army-500 hover:text-army-800">Audit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-sm text-army-500">Belum ada absensi tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
