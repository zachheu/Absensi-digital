<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    public const SOURCE_QR = 'qr';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_PERMISSION = 'permission';

    public const SOURCE_SYSTEM = 'system';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'activity_id',
        'user_id',
        'status',
        'check_in_at',
        'is_late',
        'source',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'check_in_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AttendanceAudit::class);
    }
}
