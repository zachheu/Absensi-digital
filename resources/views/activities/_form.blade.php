@php
    $isEdit = $activity->exists;
    $action = $isEdit ? route('activities.update', $activity) : route('activities.store');
    $startValue = old('start_at', $activity->start_at?->format('Y-m-d\TH:i'));
    $endValue = old('end_at', $activity->end_at?->format('Y-m-d\TH:i'));
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="label">Nama kegiatan</label>
            <input id="name" name="name" value="{{ old('name', $activity->name) }}" required class="input" placeholder="Contoh: Seminar Teknologi HIMATIF">
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="start_at" class="label">Waktu mulai</label>
            <input id="start_at" type="datetime-local" name="start_at" value="{{ $startValue }}" required class="input">
            @error('start_at') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="end_at" class="label">Waktu selesai</label>
            <input id="end_at" type="datetime-local" name="end_at" value="{{ $endValue }}" required class="input">
            @error('end_at') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="location" class="label">Lokasi</label>
            <input id="location" name="location" value="{{ old('location', $activity->location) }}" class="input" placeholder="Ruang atau tempat kegiatan">
            @error('location') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="late_tolerance_minutes" class="label">Toleransi keterlambatan (menit)</label>
            <input id="late_tolerance_minutes" type="number" min="0" max="240" name="late_tolerance_minutes"
                   value="{{ old('late_tolerance_minutes', $activity->late_tolerance_minutes ?? 15) }}" required class="input">
            <p class="help">Absensi setelah waktu mulai + toleransi ditandai terlambat.</p>
            @error('late_tolerance_minutes') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="label">Deskripsi</label>
            <textarea id="description" name="description" rows="4" class="input" placeholder="Informasi singkat kegiatan">{{ old('description', $activity->description) }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <span class="label">Status</span>
            @if (! $isEdit || $activity->status === \App\Enums\ActivityStatus::Draft)
                <select id="status" name="status" class="input">
                    @foreach ([\App\Enums\ActivityStatus::Draft, \App\Enums\ActivityStatus::Published] as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $activity->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <p class="help">Kegiatan draft tidak terlihat oleh pengurus.</p>
            @else
                <div class="flex items-center gap-2">
                    <x-status-badge :status="$activity->status" />
                </div>
                <p class="help">Status berjalan dan selesai ditetapkan otomatis lewat sesi absensi dan finalisasi rekap.</p>
            @endif
            @error('status') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>

    @if (! $isEdit && isset($sourceActivities) && $sourceActivities->isNotEmpty())
        <div>
            <label for="copy_committee_from" class="label">Salin panitia dari kegiatan (opsional)</label>
            <select id="copy_committee_from" name="copy_committee_from" class="input">
                <option value="">Tidak menyalin</option>
                @foreach ($sourceActivities as $source)
                    <option value="{{ $source->id }}">{{ $source->name }} ({{ $source->start_at?->format('d M Y') }})</option>
                @endforeach
            </select>
            <p class="help">Panitia dan jabatannya akan disalin ke kegiatan baru.</p>
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan perubahan' : 'Buat kegiatan' }}</button>
        <a href="{{ $isEdit ? route('activities.show', $activity) : route('activities.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
