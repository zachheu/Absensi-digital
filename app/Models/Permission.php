<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    public const ATTENDANCE_CORRECT = 'attendance.correct';
    public const ATTENDANCE_MANAGE = 'attendance.manage';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];
}
