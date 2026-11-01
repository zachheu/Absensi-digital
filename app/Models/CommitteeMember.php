<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'activity_id',
        'user_id',
        'committee_position_id',
        'is_head',
    ];

    protected function casts(): array
    {
        return [
            'is_head' => 'boolean',
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

    public function position(): BelongsTo
    {
        return $this->belongsTo(CommitteePosition::class, 'committee_position_id');
    }
}
