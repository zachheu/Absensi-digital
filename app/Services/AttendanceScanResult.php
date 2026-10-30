<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\Attendance;

class AttendanceScanResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly string $message,
        public readonly ?Attendance $attendance = null,
    ) {}

    public static function success(Attendance $attendance, string $message): self
    {
        return new self(true, $message, $attendance);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function isLate(): bool
    {
        return $this->attendance?->is_late ?? false;
    }

    public function statusLabel(): ?string
    {
        $status = $this->attendance?->status;

        return $status instanceof AttendanceStatus ? $status->label() : null;
    }

    public function activity(): ?Activity
    {
        return $this->attendance?->activity;
    }
}
