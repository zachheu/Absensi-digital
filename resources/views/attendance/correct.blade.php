@extends('layouts.app')

@section('title', 'Koreksi Absensi')
@section('subtitle', $attendance->user->name.' · '.$attendance->activity->name)

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card p-4 sm:p-6 xl:col-span-2">
            <div class="rounded-lg bg-cream-100 p-4 text-sm">
                <p class="font-medium text-army-900">Data saat ini</p>
                <div class="mt-2 grid gap-1 text-xs text-army-700 sm:grid-cols-2">
                    <p>Status: {{ $attendance->status->label() }}</p>
                    <p>Check-in: {{ $attendance->check_in_at?->format('d M Y H:i') ?? '-' }}</p>
                    <p>Terlambat: {{ $attendance->is_late ? 'Ya' : 'Tidak' }}</p>
                    <p>Sumber: {{ $attendance->source }}</p>
                    <p>Batas tepat waktu: {{ $attendance->activity->lateThreshold()->format('d M Y H:i') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('attendances.correct.update', $attendance) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="status" class="label">Status kehadiran</label>
                        <select id="status" name="status" class="input" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $attendance->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="check_in_at" class="label">Waktu check-in</label>
                        <input id="check_in_at" type="datetime-local" name="check_in_at"
                               value="{{ old('check_in_at', $attendance->check_in_at?->format('Y-m-d\TH:i')) }}" class="input">
                        <p class="help">Wajib diisi bila status hadir. Penanda terlambat dihitung ulang otomatis.</p>
                        @error('check_in_at') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="label">Catatan absensi (opsional)</label>
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $attendance->notes) }}</textarea>
                    @error('notes') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="reason" class="label">Alasan koreksi</label>
                    <textarea id="reason" name="reason" rows="3" required class="input" placeholder="Contoh: QR gagal dipindai, kehadiran dibuktikan dengan daftar hadir fisik.">{{ old('reason') }}</textarea>
                    <p class="help">Alasan wajib diisi dan akan tersimpan permanen di audit log bersama nilai sebelum dan sesudah.</p>
                    @error('reason') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-primary">Simpan koreksi</button>
                    <a href="{{ route('activities.show', $attendance->activity) }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </section>

        <section class="card h-fit">
            <h2 class="border-b border-cream-200 px-4 py-3 text-sm font-semibold text-army-900">Riwayat audit</h2>
            <div class="divide-y divide-cream-200">
                @forelse ($audits as $audit)
                    <div class="px-4 py-3 text-xs">
                        <p class="font-medium text-army-900">{{ $audit->action }}</p>
                        <p class="text-army-500">{{ $audit->actor?->name ?? 'Sistem' }} · {{ $audit->created_at?->format('d M Y H:i') }}</p>
                        <p class="mt-1 text-army-600">{{ $audit->reason }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-xs text-army-500">Belum ada audit untuk absensi ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
