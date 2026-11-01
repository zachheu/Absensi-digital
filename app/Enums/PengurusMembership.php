<?php

namespace App\Enums;

enum PengurusMembership: string
{
    case Sentral = 'sentral';
    case Muda = 'muda';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Sentral => 'Pengurus Sentral',
            self::Muda => 'Pengurus Muda',
            self::Staff => 'Staff',
        };
    }
}
