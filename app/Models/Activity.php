<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Activity extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'location',
        'start_at',
        'end_at',
        'late_tolerance_minutes',
        'status',
        'created_by',
        'recap_finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'late_tolerance_minutes' => 'integer',
            'status' => ActivityStatus::class,
            'recap_finalized_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function permissionRequests(): HasMany
    {
        return $this->hasMany(PermissionRequest::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AttendanceAudit::class);
    }

    public function openSession(): ?AttendanceSession
    {
        return $this->sessions()->where('status', AttendanceSession::STATUS_OPEN)->latest('opened_at')->first();
    }

    /**
     * Batas waktu absensi dinyatakan terlambat.
     */
    public function lateThreshold(): Carbon
    {
        return $this->start_at->copy()->addMinutes($this->late_tolerance_minutes);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ActivityStatus::Published->value,
            ActivityStatus::Ongoing->value,
            ActivityStatus::Completed->value,
        ]);
    }

    public function isRecapFinalized(): bool
    {
        return $this->recap_finalized_at !== null;
    }
}
