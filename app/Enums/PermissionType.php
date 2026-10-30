<?php

namespace App\Enums;

enum PermissionType: string
{
    case Izin = 'izin';
    case Sakit = 'sakit';

    public function label(): string
    {
        return match ($this) {
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
        };
    }

    public function attendanceStatus(): AttendanceStatus
    {
        return match ($this) {
            self::Izin => AttendanceStatus::Izin,
            self::Sakit => AttendanceStatus::Sakit,
        };
    }
}
