@php
    $rows = $attendances;
    $statusFilter = isset($filters['status'])
        ? (\App\Enums\AttendanceStatus::tryFrom($filters['status'])?->label() ?? $filters['status'])
        : 'semua';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi HIMATIF</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #23260f; font-size: 10px; margin: 18px; }
        h1 { font-size: 16px; margin: 0; color: #4b5320; }
        p { margin: 2px 0; }
        .muted { color: #6b7280; font-size: 9px; }
        .summary { margin: 10px 0 12px; }
        .summary span { display: inline-block; margin-right: 12px; padding: 4px 8px; background: #f8f3e6; border: 1px solid #e6d7b5; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4b5320; color: #fdfbf5; text-align: left; padding: 6px 5px; font-size: 9px; }
        td { border-bottom: 1px solid #e6d7b5; padding: 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #fdfbf5; }
        .right { text-align: right; }
        .footer { margin-top: 12px; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <table style="border: none;">
        <tr>
            <td style="border: none; padding: 0;">
                <h1>Rekap Absensi HIMATIF</h1>
                <p class="muted">Dicetak {{ $generatedAt->format('d M Y H:i') }} WIB</p>
            </td>
            <td style="border: none; padding: 0;" class="right">
                <p class="muted">Filter tanggal:
                    {{ ($filters['from'] ?? null) ?: 'semua' }} s.d. {{ ($filters['to'] ?? null) ?: 'semua' }}
                </p>
                <p class="muted">Status: {{ $statusFilter }}</p>
            </td>
        </tr>
    </table>

    <div class="summary">
        <span>Total: {{ $summary['total'] }}</span>
        <span>Hadir: {{ $summary['hadir'] }}</span>
        <span>Izin: {{ $summary['izin'] }}</span>
        <span>Sakit: {{ $summary['sakit'] }}</span>
        <span>Alpa: {{ $summary['alpa'] }}</span>
        <span>Terlambat: {{ $summary['late'] }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">NPP</th>
                <th style="width: 9%;">NPM</th>
                <th style="width: 16%;">Nama</th>
                <th style="width: 7%;">Bidang</th>
                <th style="width: 17%;">Kegiatan</th>
                <th style="width: 12%;">Jabatan</th>
                <th style="width: 10%;">Check-in</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 7%;">Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $attendance)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $attendance->user->npp }}</td>
                    <td>{{ $attendance->user->npm }}</td>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->user->organizationalField?->code ?? '-' }}</td>
                    <td>{{ $attendance->activity->name }}</td>
                    <td>{{ $attendance->user->committeeMembers->where('activity_id', $attendance->activity_id)->pluck('position.name')->filter()->implode(', ') ?: '-' }}</td>
                    <td>{{ $attendance->check_in_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $attendance->status->label() }}</td>
                    <td>{{ $attendance->is_late ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 16px;">Tidak ada data absensi sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">
        Satu baris mewakili satu absensi pengurus per kegiatan. Pengurus dengan beberapa jabatan kepanitiaan tetap dihitung satu kali.
    </p>
</body>
</html>
