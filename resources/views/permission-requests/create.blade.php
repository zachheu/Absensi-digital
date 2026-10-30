@extends('layouts.app')

@section('title', 'Ajukan Izin / Sakit')
@section('subtitle', 'Pengajuan akan ditinjau admin HIMATIF')

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="card p-4 sm:p-6 xl:col-span-2">
            <form method="POST" action="{{ route('permission-requests.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="activity_id" class="label">Kegiatan</label>
                    <select id="activity_id" name="activity_id" required class="input">
                        <option value="">Pilih kegiatan</option>
                        @foreach ($activities as $activity)
                            @php
                                $attendance = $activity->attendances->first();
                                $request = $activity->permissionRequests->first();
                            @endphp
                            <option value="{{ $activity->id }}"
                                    @selected((string) old('activity_id') === (string) $activity->id)
                                    @disabled($attendance !== null || ($request !== null && $request->status->value !== 'rejected'))>
                                {{ $activity->name }} · {{ $activity->start_at->format('d M Y H:i') }}
                                @if ($attendance) (sudah tercatat {{ $attendance->status->label() }})
                                @elseif ($request && $request->status->value === 'pending') (menunggu keputusan)
                                @elseif ($request && $request->status->value === 'approved') (sudah disetujui)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('activity_id') <p class="error">{{ $message }}</p> @enderror
                    @if ($activities->isEmpty())
                        <p class="help">Belum ada kegiatan yang dapat diajukan. Kamu hanya dapat mengajukan izin/sakit untuk kegiatan yang kamu ikuti sebagai panitia.</p>
                    @endif
                </div>

                <div>
                    <span class="label">Jenis pengajuan</span>
                    <div class="flex gap-3">
                        @foreach (\App\Enums\PermissionType::cases() as $type)
                            <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm hover:border-army-300">
                                <input type="radio" name="type" value="{{ $type->value }}" @checked(old('type', 'izin') === $type->value) class="h-4 w-4 text-army-700">
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('type') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="reason" class="label">Alasan</label>
                    <textarea id="reason" name="reason" rows="4" required class="input" placeholder="Jelaskan alasan izin atau sakit secara singkat">{{ old('reason') }}</textarea>
                    @error('reason') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="attachment" class="label">Lampiran (opsional)</label>
                    <input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="input">
                    <p class="help">JPG, PNG, atau PDF maksimal 2 MB. Lampiran disimpan privat dan hanya dapat diakses admin.</p>
                    @error('attachment') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-primary">Kirim pengajuan</button>
                    <a href="{{ route('permission-requests.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>

        <div class="card h-fit p-4 text-sm leading-relaxed text-army-600">
            <h2 class="text-sm font-semibold text-army-900">Alur pengajuan</h2>
            <ol class="mt-3 list-inside list-decimal space-y-1 text-xs">
                <li>Pengajuan dikirim dengan status menunggu.</li>
                <li>Admin meninjau bukti dan alasan.</li>
                <li>Jika disetujui, status absensi menjadi izin atau sakit.</li>
                <li>Jika ditolak, kamu dapat mengajukan ulang dengan perbaikan.</li>
            </ol>
            <p class="mt-3 text-xs">Pengajuan yang masih menunggu belum dianggap sebagai izin resmi.</p>
        </div>
    </div>
@endsection
