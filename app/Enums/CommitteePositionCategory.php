<?php

namespace App\Enums;

enum CommitteePositionCategory: string
{
    case Structural = 'structural';
    case Division = 'division';

    public function label(): string
    {
        return match ($this) {
            self::Structural => 'Struktural',
            self::Division => 'Divisi',
        };
    }
}
