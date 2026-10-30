<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CommitteePosition;
use App\Models\OrganizationalField;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $rows = $this->filteredQuery($filters)->paginate(25)->withQueryString();

        return view('reports.index', [
            ...$this->filterOptions(),
            'attendances' => $rows,
            'filters' => $filters,
            'summary' => $this->summary($filters),
        ]);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $filters = $this->validatedFilters($request);
        $attendances = $this->filteredQuery($filters)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absensi');

        $sheet->fromArray([
            ['NPP', 'NPM', 'Nama Pengurus', 'Bidang', 'Kegiatan', 'Jabatan Kepanitiaan', 'Waktu Mulai', 'Waktu Check-in', 'Status', 'Terlambat', 'Sumber'],
        ], null, 'A1');

        $rowNumber = 2;

        foreach ($attendances as $attendance) {
            $sheet->fromArray([
                $attendance->user->npp,
                $attendance->user->npm,
                $attendance->user->name,
                $attendance->user->organizationalField?->code ?? '-',
                $attendance->activity->name,
                $this->committeePositionsLabel($attendance),
                $attendance->activity->start_at->format('d/m/Y H:i'),
                $attendance->check_in_at?->format('d/m/Y H:i') ?? '-',
                $attendance->status->label(),
                $attendance->is_late ? 'Ya' : 'Tidak',
                $attendance->source,
            ], null, 'A'.$rowNumber);

            $rowNumber++;
        }

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'rekap-absensi-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'rekap-absensi-himatif-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend(true);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $attendances = $this->filteredQuery($filters)->get();

        $pdf = Pdf::loadView('reports.pdf', [
            'attendances' => $attendances,
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekap-absensi-himatif-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'activity' => ['nullable', 'integer', 'exists:activities,id'],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'field' => ['nullable', 'integer', 'exists:organizational_fields,id'],
            'position' => ['nullable', 'integer', 'exists:committee_positions,id'],
            'status' => ['nullable', 'in:hadir,izin,sakit,alpa'],
            'late' => ['nullable', 'in:0,1'],
        ]);
    }

    /**
     * Satu baris per absensi (unik per pengurus dan kegiatan) agar pengurus
     * dengan beberapa jabatan tidak dihitung ganda.
     *
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return Attendance::query()
            ->with(['user.organizationalField', 'user.committeeMembers.position', 'activity'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereHas('activity', fn (Builder $query) => $query->whereDate('start_at', '>=', $from)))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereHas('activity', fn (Builder $query) => $query->whereDate('start_at', '<=', $to)))
            ->when($filters['activity'] ?? null, fn (Builder $query, int $activity) => $query->where('activity_id', $activity))
            ->when($filters['user'] ?? null, fn (Builder $query, int $user) => $query->where('user_id', $user))
            ->when($filters['field'] ?? null, fn (Builder $query, int $field) => $query->whereHas('user', fn (Builder $query) => $query->where('organizational_field_id', $field)))
            ->when($filters['position'] ?? null, function (Builder $query, int $position): void {
                $query->whereExists(function ($query) use ($position): void {
                    $query->selectRaw('1')
                        ->from('committee_members')
                        ->whereColumn('committee_members.user_id', 'attendances.user_id')
                        ->whereColumn('committee_members.activity_id', 'attendances.activity_id')
                        ->where('committee_members.committee_position_id', $position);
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(isset($filters['late']), fn (Builder $query) => $query->where('is_late', (bool) $filters['late']))
            ->orderByDesc('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function summary(array $filters): array
    {
        $attendances = $this->filteredQuery($filters)->get();

        return [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', AttendanceStatus::Hadir)->count(),
            'izin' => $attendances->where('status', AttendanceStatus::Izin)->count(),
            'sakit' => $attendances->where('status', AttendanceStatus::Sakit)->count(),
            'alpa' => $attendances->where('status', AttendanceStatus::Alpa)->count(),
            'late' => $attendances->where('is_late', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'activities' => Activity::query()->orderByDesc('start_at')->get(['id', 'name', 'start_at']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'npp']),
            'fields' => OrganizationalField::query()->orderBy('code')->get(['id', 'code', 'name']),
            'positions' => CommitteePosition::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => AttendanceStatus::cases(),
        ];
    }

    private function committeePositionsLabel(Attendance $attendance): string
    {
        $positions = $attendance->user->committeeMembers()
            ->where('activity_id', $attendance->activity_id)
            ->with('position')
            ->get()
            ->pluck('position.name')
            ->filter()
            ->implode(', ');

        return $positions !== '' ? $positions : '-';
    }
}
